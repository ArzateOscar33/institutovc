<?php
/** Persistencia de PayPal Sandbox. Los pedidos se crean mediante RegistroPedidos. */
class PagosModel
{
    private $db;
    public function __construct()
    {
        $this->db = (new Conexion())->conect();
        if (!$this->db instanceof PDO) throw new RuntimeException('No fue posible conectar con MySQL.');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    private function uno(string $sql, array $params = [])
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function clienteVigente(int $id, string $correo): bool
    {
        return (bool)$this->uno('SELECT id FROM clientes WHERE id=? AND correo=? AND verify=1', [$id,$correo]);
    }
    /** 4.6.1: aparta inventario REAL antes de abrir PayPal. Idempotente por cotización. */
    public function reservar(int $cotId, int $clienteId): array
    {
        // La limpieza nunca afecta reservas PROCESANDO ni pagos capturados.
        ReservasInventario::limpiarVencidas($this->db, 25);
        $this->db->beginTransaction();
        try {
            $quote = $this->uno('SELECT *, (expira_en<=NOW()) AS vencida
                FROM checkout_cotizaciones WHERE id_cotizacion=? AND id_cliente=? FOR UPDATE', [$cotId,$clienteId]);
            if (!$quote) throw new DomainException('Cotización inexistente o de otro cliente.');
            if ($quote['moneda'] !== 'MXN' || (float)$quote['total'] <= 0 || $quote['vencida']) {
                throw new DomainException('La cotización está vencida. Genera una nueva.');
            }
            if (!in_array($quote['estado'], ['ABIERTA','CONFIRMADA'],true)) {
                throw new DomainException('Esta cotización no admite un nuevo pago.');
            }
            $items=$this->db->prepare('SELECT id_producto,SUM(cantidad) AS unidades
                FROM checkout_cotizacion_items WHERE id_cotizacion=?
                GROUP BY id_producto ORDER BY id_producto');
            $items->execute([$cotId]);
            $lineas=$items->fetchAll(PDO::FETCH_ASSOC);
            if (!$lineas) throw new DomainException('La cotización no contiene productos.');
            $prev=$this->uno('SELECT * FROM paypal_ordenes WHERE id_cotizacion=? ORDER BY id DESC LIMIT 1 FOR UPDATE',[$cotId]);
            if ($prev && $prev['estado']!=='CREADA') {
                throw new DomainException('La orden ya fue procesada o cancelada; no vuelvas a pagar.');
            }
            // No aceptar órdenes heredadas sin reserva ni reutilizar una liberada.
            $res=$this->uno('SELECT *, (expira_en<=NOW()) AS vencida
                FROM inventario_reservas WHERE id_cotizacion=? FOR UPDATE',[$cotId]);
            if ($res) {
                if (!$prev || $res['estado']!=='RESERVADA' || $res['vencida'] ||
                    (int)$res['id_cliente']!==$clienteId) {
                    throw new DomainException('La reserva ya no está disponible. Genera una nueva cotización.');
                }
                $existentes=$this->db->prepare('SELECT id_producto,cantidad FROM inventario_reserva_items
                    WHERE id_reserva=? ORDER BY id_producto');
                $existentes->execute([$res['id_reserva']]);
                $actual=$existentes->fetchAll(PDO::FETCH_ASSOC);
                if (count($actual)!==count($lineas)) throw new DomainException('Reserva y cotización inconsistentes.');
                foreach ($lineas as $i=>$l) {
                    if ((int)$actual[$i]['id_producto']!==(int)$l['id_producto'] ||
                        (int)$actual[$i]['cantidad']!==(int)$l['unidades']) {
                        throw new DomainException('La composición de la reserva cambió.');
                    }
                }
                $this->db->commit();
                return ['quote'=>$quote,'order'=>$prev,'reserva_expira_en'=>$res['expira_en']];
            }
            if ($prev) throw new DomainException('Orden antigua sin reserva. Genera una cotización nueva.');
            // Productos en orden fijo; UPDATE atómico con cantidad >= solicitada.
            foreach ($lineas as $l) {
                $qty=(int)$l['unidades']; $id=(int)$l['id_producto'];
                if ($id<1 || $qty<1 || $qty>30) throw new DomainException('Cantidad de armazones inválida.');
                $stock=$this->uno('SELECT cantidad,estado FROM productos WHERE id=? FOR UPDATE',[$id]);
                if (!$stock || (int)$stock['estado']!==1 || (int)$stock['cantidad']<$qty) {
                    throw new DomainException('Producto #'.$id.' agotado o sin existencias suficientes. No se realizó ningún cobro.');
                }
            }
            $ins=$this->db->prepare("INSERT INTO inventario_reservas
                (id_cotizacion,id_cliente,estado,expira_en)
                VALUES (?,?,'RESERVADA',LEAST(DATE_ADD(NOW(),INTERVAL 10 MINUTE),?))");
            $ins->execute([$cotId,$clienteId,$quote['expira_en']]);
            $resId=(int)$this->db->lastInsertId();
            $line=$this->db->prepare('INSERT INTO inventario_reserva_items (id_reserva,id_producto,cantidad)
                VALUES (?,?,?)');
            $update=$this->db->prepare('UPDATE productos SET cantidad=cantidad-?
                WHERE id=? AND estado=1 AND cantidad>=?');
            foreach ($lineas as $l) {
                $qty=(int)$l['unidades'];$id=(int)$l['id_producto'];
                $update->execute([$qty,$id,$qty]);
                if ($update->rowCount()!==1) throw new DomainException('Existencias insuficientes al reservar.');
                $line->execute([$resId,$id,$qty]);
            }
            $temp='IVC-PENDING-'.bin2hex(random_bytes(12));
            $q=$this->db->prepare("INSERT INTO paypal_ordenes
                (id_cotizacion,paypal_order_id,estado,moneda,monto_esperado)
                VALUES (?,?,'CREADA','MXN',?)");
            $q->execute([$cotId,$temp,$quote['total']]);
            $prev=$this->uno('SELECT * FROM paypal_ordenes WHERE id=?',[(int)$this->db->lastInsertId()]);
            if ($quote['estado']==='ABIERTA') {
                $this->db->prepare("UPDATE checkout_cotizaciones SET estado='CONFIRMADA' WHERE id_cotizacion=?")
                    ->execute([$cotId]);
            }
            $res=$this->uno('SELECT expira_en FROM inventario_reservas WHERE id_reserva=?',[$resId]);
            $this->db->commit();
            return ['quote'=>$quote,'order'=>$prev,'reserva_expira_en'=>$res['expira_en']];
        } catch (Throwable $e) {if ($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function iniciarCaptura(int $cotId,int $clienteId,string $orderId): void
    {
        ReservasInventario::iniciarCaptura($this->db,$cotId,$clienteId,$orderId);
    }

    public function registrarOrden(int $idRow, string $paypalId): void
    {
        if (!preg_match('/^[A-Z0-9]{8,40}$/D',$paypalId)) throw new DomainException('PayPal devolvió un ID de orden inválido.');
        $q=$this->db->prepare("UPDATE paypal_ordenes SET paypal_order_id=? WHERE id=? AND estado='CREADA' AND paypal_order_id LIKE 'IVC-PENDING-%' AND EXISTS (SELECT 1 FROM inventario_reservas r WHERE r.id_cotizacion=paypal_ordenes.id_cotizacion AND r.estado='RESERVADA' AND r.expira_en>NOW())");
        $q->execute([$paypalId,$idRow]);
        if ($q->rowCount()!==1) {
            $existing=$this->uno('SELECT paypal_order_id FROM paypal_ordenes WHERE id=?', [$idRow]);
            if (!$existing || $existing['paypal_order_id']!==$paypalId) throw new DomainException('Conflicto al registrar la orden PayPal.');
        }
    }
    public function obtenerCaptura(int $quoteId, int $clienteId, string $orderId): array
    {
        $row=$this->uno('SELECT p.*, c.total, c.moneda AS quote_moneda, c.estado AS cotizacion_estado,
            (c.expira_en<=NOW()) AS vencida FROM paypal_ordenes p
            INNER JOIN checkout_cotizaciones c ON c.id_cotizacion=p.id_cotizacion
            WHERE p.id_cotizacion=? AND c.id_cliente=? AND p.paypal_order_id=? LIMIT 1',[$quoteId,$clienteId,$orderId]);
        if (!$row) throw new DomainException('Orden PayPal ajena o no registrada en tu cotización.');
        if ((string)$row['monto_esperado']!==(string)$row['total'] || $row['moneda']!=='MXN' || $row['quote_moneda']!=='MXN') {
            throw new DomainException('La moneda o el monto de la orden no coinciden.');
        }
        if ($row['estado']!=='CAPTURADA' && $row['cotizacion_estado']!=='CONFIRMADA') {
            throw new DomainException('Cotización no confirmada. No se capturó el pago.');
        }
        return $row;
    }
    /** Órdenes sin pedido: capturadas o inciertas. Recuperar solo verifica, no vuelve a cobrar. */
    public function pendientes(int $clienteId): array
    {
        $q = $this->db->prepare("SELECT c.id_cotizacion,c.total,c.creado_en,p.paypal_order_id
            FROM checkout_cotizaciones c
            JOIN paypal_ordenes p ON p.id_cotizacion=c.id_cotizacion
            WHERE c.id_cliente=? AND c.id_pedido IS NULL
              AND c.estado IN ('CONFIRMADA','PAGADA')
              AND p.estado IN ('CREADA','CAPTURADA')
              AND p.paypal_order_id NOT LIKE 'IVC-PENDING-%'
            ORDER BY c.id_cotizacion DESC LIMIT 10");
        $q->execute([$clienteId]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Resuelve una orden creada para una cotización propia, sin iniciar otra captura. */
    public function obtenerParaConciliar(int $cotId, int $clienteId): array
    {
        $row=$this->uno('SELECT p.*,c.estado AS cotizacion_estado,c.total,
            c.moneda AS quote_moneda, (c.expira_en<=NOW()) AS vencida
            FROM paypal_ordenes p JOIN checkout_cotizaciones c ON c.id_cotizacion=p.id_cotizacion
            WHERE p.id_cotizacion=? AND c.id_cliente=? LIMIT 1', [$cotId,$clienteId]);
        if (!$row || !preg_match('/^[A-Z0-9]{8,40}$/D', (string)$row['paypal_order_id'])) {
            throw new DomainException('Cotización u orden PayPal no encontrada.');
        }
        if ($row['moneda']!=='MXN' || $row['quote_moneda']!=='MXN'
            || (string)$row['total']!==(string)$row['monto_esperado']) {
            throw new DomainException('No coincide el monto o la moneda en la cotización.');
        }
        if (!in_array($row['estado'], ['CREADA','CAPTURADA'], true)
            || !in_array($row['cotizacion_estado'], ['CONFIRMADA','PAGADA'], true)) {
            throw new DomainException('La orden no está disponible para conciliación.');
        }
        return $row;
    }

    public function guardarCaptura(int $rowId, string $captureId, string $monto): void
    {
        if (!preg_match('/^[A-Z0-9]{8,100}$/D',$captureId)) throw new DomainException('Identificador de captura inválido.');
        $this->db->beginTransaction();
        try {
            // Se bloquea primero la cotización y después la orden, mismo orden que RegistroPedidos.
            $ref=$this->uno('SELECT id_cotizacion FROM paypal_ordenes WHERE id=?', [$rowId]);
            if (!$ref) throw new DomainException('No existe la orden PayPal.');
            $quote=$this->uno('SELECT id_cotizacion,estado,total,moneda
                FROM checkout_cotizaciones WHERE id_cotizacion=? FOR UPDATE', [$ref['id_cotizacion']]);
            $row=$this->uno('SELECT * FROM paypal_ordenes WHERE id=? FOR UPDATE', [$rowId]);
            if (!$quote || !$row || (int)$row['id_cotizacion'] !== (int)$quote['id_cotizacion']) {
                throw new DomainException('Conflicto en la relación PayPal/cotización.');
            }
            if ($row['estado']==='CAPTURADA') {
                if ($row['paypal_capture_id']!==$captureId || (string)$row['monto_capturado']!==$monto
                    || $quote['estado']!=='PAGADA') {
                    throw new DomainException('Conflicto con una captura anterior.');
                }
                $this->db->commit();return;
            }
            if ($row['estado']!=='CREADA' || $quote['estado']!=='CONFIRMADA'
                || $quote['moneda']!=='MXN' || $row['moneda']!=='MXN'
                || (string)$row['monto_esperado']!==$monto
                || (string)$quote['total']!==$monto) {
                throw new DomainException('Estado o importe inesperado al registrar la captura.');
            }
            $q=$this->db->prepare("UPDATE paypal_ordenes
                SET estado='CAPTURADA',paypal_capture_id=?,monto_capturado=?,fecha_captura=NOW()
                WHERE id=? AND estado='CREADA'");
            $q->execute([$captureId,$monto,$rowId]);
            if ($q->rowCount()!==1) throw new RuntimeException('No se actualizó la captura PayPal.');
            $q=$this->db->prepare("UPDATE checkout_cotizaciones SET estado='PAGADA'
                WHERE id_cotizacion=? AND estado='CONFIRMADA'");
            $q->execute([$row['id_cotizacion']]);
            if ($q->rowCount()!==1) throw new RuntimeException('No se confirmó la cotización pagada.');
            $this->db->commit();
        } catch (Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
}
