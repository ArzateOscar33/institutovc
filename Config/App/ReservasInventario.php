<?php
/** IVC 4.6.1: reservas seguras. Una reserva por cotización, cantidades agrupadas por producto.
 * Invariante: productos.cantidad = existencias que NO están reservadas ni vendidas.
 * Toda entrada se protege en MySQL; ningún estado procede de JavaScript.
 */
final class ReservasInventario
{
    /** Limpieza prudente de reservas vencidas. NUNCA libera una captura en progreso o incierta.
     * Debe invocarse desde cron además de los checkouts. No llama a PayPal ni crea cobros.
     */
    public static function limpiarVencidas(PDO $db, int $limite = 25): int
    {
        $limite = max(1, min(100, $limite));
        // Lectura preliminar sin bloqueo; validamos todo DE NUEVO dentro de la transacción.
        $ids = $db->query("SELECT id_cotizacion FROM inventario_reservas
            WHERE estado='RESERVADA' AND expira_en<=NOW()
            ORDER BY expira_en ASC LIMIT {$limite}")->fetchAll(PDO::FETCH_COLUMN);
        $liberadas = 0;
        foreach ($ids as $id) {
            $db->beginTransaction();
            try {
                // Orden común: cotización -> orden PayPal -> reserva -> productos.
                $q = self::uno($db,'SELECT id_cotizacion,estado FROM checkout_cotizaciones
                    WHERE id_cotizacion=? FOR UPDATE',[(int)$id]);
                if (!$q) throw new RuntimeException('Cotización faltante para reserva.');
                $pagos = self::todos($db,'SELECT id,estado FROM paypal_ordenes
                    WHERE id_cotizacion=? ORDER BY id FOR UPDATE',[(int)$id]);
                $r = self::uno($db,'SELECT *, (expira_en<=NOW()) AS vencida FROM inventario_reservas
                    WHERE id_cotizacion=? FOR UPDATE',[(int)$id]);
                if (!$r || $r['estado']!=='RESERVADA' || !(int)$r['vencida']) {
                    $db->commit(); continue;
                }
                // Una captura registrada o un pago de estado inesperado requiere revisión humana.
                if ($q['estado']==='PAGADA' || count($pagos)>1 ||
                    ($pagos && !in_array($pagos[0]['estado'], ['CREADA','CANCELADA','FALLIDA'],true))) {
                    $db->prepare("UPDATE inventario_reservas SET estado='REVISAR' WHERE id_reserva=?")
                        ->execute([(int)$r['id_reserva']]);
                    $db->commit(); continue;
                }
                $items = self::todos($db,'SELECT id_producto,cantidad FROM inventario_reserva_items
                    WHERE id_reserva=? ORDER BY id_producto',[(int)$r['id_reserva']]);
                if (!$items) throw new RuntimeException('Reserva sin artículos; revisión requerida.');
                // Bloquear productos para serializar con otras reservas.
                foreach ($items as $item) {
                    $p = self::uno($db,'SELECT id FROM productos WHERE id=? FOR UPDATE',[(int)$item['id_producto']]);
                    if (!$p) throw new RuntimeException('Producto de reserva inexistente.');
                    $db->prepare('UPDATE productos SET cantidad=cantidad+? WHERE id=?')
                        ->execute([(int)$item['cantidad'],(int)$item['id_producto']]);
                }
                $db->prepare("UPDATE paypal_ordenes SET estado='CANCELADA'
                    WHERE id_cotizacion=? AND estado='CREADA'")->execute([(int)$id]);
                $db->prepare("UPDATE checkout_cotizaciones SET estado='EXPIRADA'
                    WHERE id_cotizacion=? AND estado IN ('ABIERTA','CONFIRMADA')")->execute([(int)$id]);
                $db->prepare("UPDATE inventario_reservas SET estado='LIBERADA' WHERE id_reserva=? AND estado='RESERVADA'")
                    ->execute([(int)$r['id_reserva']]);
                $db->commit(); $liberadas++;
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log('IVC reserva expirada cot#'.(int)$id.': '.$e->getMessage());
            }
        }
        return $liberadas;
    }

    /** Marca la reserva en proceso ANTES de solicitar captura externa. PROCESANDO se
     * mantiene retenida tras timeouts: evita devolver unidades que quizá ya fueron cobradas.
     */
    public static function iniciarCaptura(PDO $db, int $cotId, int $clienteId, string $orderId): void
    {
        $db->beginTransaction();
        try {
            $q=self::uno($db,'SELECT estado,(expira_en<=NOW()) AS vencida FROM checkout_cotizaciones
                WHERE id_cotizacion=? AND id_cliente=? FOR UPDATE',[$cotId,$clienteId]);
            $p=self::uno($db,'SELECT estado,paypal_order_id FROM paypal_ordenes
                WHERE id_cotizacion=? FOR UPDATE',[$cotId]);
            $r=self::uno($db,'SELECT estado,(expira_en<=NOW()) AS vencida FROM inventario_reservas
                WHERE id_cotizacion=? AND id_cliente=? FOR UPDATE',[$cotId,$clienteId]);
            if (!$q || !$p || !$r || $q['estado']!=='CONFIRMADA' || $p['estado']!=='CREADA'
                || $p['paypal_order_id']!==$orderId || $r['estado']!=='RESERVADA') {
                throw new DomainException('Reserva no disponible o captura ya en verificación. Usa Recuperar pedido.');
            }
            if ($q['vencida'] || $r['vencida']) {
                throw new DomainException('La reserva venció. No se iniciará ninguna captura. Genera otra cotización.');
            }
            $u=$db->prepare("UPDATE inventario_reservas SET estado='PROCESANDO'
                WHERE id_cotizacion=? AND estado='RESERVADA'");
            $u->execute([$cotId]);
            if ($u->rowCount()!==1) throw new RuntimeException('Conflicto al iniciar la captura.');
            $db->commit();
        } catch (Throwable $e) {if ($db->inTransaction()) $db->rollBack();throw $e;}
    }

    private static function uno(PDO $db,string $sql,array $params): ?array
    {
        $s=$db->prepare($sql);$s->execute($params);return $s->fetch(PDO::FETCH_ASSOC)?:null;
    }
    private static function todos(PDO $db,string $sql,array $params): array
    {
        $s=$db->prepare($sql);$s->execute($params);return $s->fetchAll(PDO::FETCH_ASSOC);
    }
}
