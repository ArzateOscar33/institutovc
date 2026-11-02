<?php
/**
 * IVC 4.6 - Conversión transaccional e idempotente de una captura PayPal
 * YA verificada y registrada en MySQL. NO consume la API de PayPal.
 * La cotización es un snapshot histórico, no se consultan precios vigentes.
 */
require_once __DIR__ . '/CotizadorMontos.php';

final class RegistroPedidos
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Conexion())->conect();
        if (!$this->db instanceof PDO) {
            throw new RuntimeException('No hay conexión con MySQL.');
        }
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function uno(string $sql, array $params = []): ?array
    {
        $q = $this->db->prepare($sql);
        $q->execute($params);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function varios(string $sql, array $params = []): array
    {
        $q = $this->db->prepare($sql);
        $q->execute($params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{pedido_id:int,ya_existia:bool,requiere_valoracion:bool} */
    public function registrar(int $idCotizacion, int $idCliente): array
    {
        if ($idCotizacion < 1 || $idCliente < 1) {
            throw new DomainException('Cotización o cliente incorrectos.');
        }
        $this->db->beginTransaction();
        try {
            // Orden de bloqueos determinista: cotización -> PayPal -> productos.
            $c = $this->uno('SELECT * FROM checkout_cotizaciones
                WHERE id_cotizacion=? AND id_cliente=? FOR UPDATE', [$idCotizacion, $idCliente]);
            if (!$c) throw new DomainException('La cotización no corresponde al cliente.');

            $pagos = $this->varios('SELECT * FROM paypal_ordenes
                WHERE id_cotizacion=? ORDER BY id FOR UPDATE', [$idCotizacion]);
            if (count($pagos) !== 1) {
                throw new DomainException('La cotización necesita revisión: número inesperado de órdenes PayPal.');
            }
            $p = $pagos[0];
            if ($p['estado'] !== 'CAPTURADA' || $c['estado'] !== 'PAGADA'
                || $p['moneda'] !== 'MXN' || $c['moneda'] !== 'MXN'
                || !is_string($p['paypal_capture_id'])
                || !preg_match('/^[A-Z0-9]{8,100}$/D', $p['paypal_capture_id'])) {
                throw new DomainException('La captura no está confirmada; no se generó ningún pedido.');
            }
            $importe = CotizadorMontos::centavos($c['total']);
            if ($importe <= 0 || $importe > 9999999999 || $importe !== CotizadorMontos::centavos($p['monto_esperado'])
                || $p['monto_capturado'] === null
                || $importe !== CotizadorMontos::centavos($p['monto_capturado'])) {
                throw new DomainException('El importe de la captura no coincide con la cotización.');
            }

            // Una captura confirmada que ya se convirtió no puede volver a afectar stock.
            if ($c['id_pedido'] !== null) {
                $pedido = $this->uno('SELECT id,id_cliente,id_transaccion,monto FROM pedidos WHERE id=?', [(int)$c['id_pedido']]);
                if (!$pedido || (int)$pedido['id_cliente'] !== $idCliente
                    || $pedido['id_transaccion'] !== $p['paypal_capture_id']
                    || CotizadorMontos::centavos($pedido['monto']) !== $importe) {
                    throw new DomainException('Asociación de pedido inconsistente. Revisión manual necesaria.');
                }
                $valoracion = (bool)$this->uno('SELECT id_pedido FROM pedido_valoraciones WHERE id_pedido=?', [(int)$pedido['id']]);
                $this->db->commit();
                return ['pedido_id'=>(int)$pedido['id'], 'ya_existia'=>true, 'requiere_valoracion'=>$valoracion];
            }

            // Defensa adicional ante registros antiguos/ajenos con el mismo ID de captura.
            $previo = $this->uno('SELECT id,id_cliente FROM pedidos WHERE id_transaccion=? FOR UPDATE', [$p['paypal_capture_id']]);
            if ($previo) throw new DomainException('La captura está asociada a otro pedido. Solicita conciliación manual.');

            $cliente = $this->uno('SELECT id,correo,nombre,apellidoP,apellidoM,verify
                FROM clientes WHERE id=?', [$idCliente]);
            if (!$cliente || (int)$cliente['verify'] !== 1) {
                throw new DomainException('Cliente no disponible o cuenta sin verificar.');
            }
            if (strlen((string)$cliente['correo']) > 80) {
                throw new DomainException('El correo del cliente excede el campo histórico pedidos.email; requiere intervención.');
            }

            $items = $this->varios('SELECT * FROM checkout_cotizacion_items
                WHERE id_cotizacion=? ORDER BY id_item FOR UPDATE', [$idCotizacion]);
            if (!$items || count($items) > 30) throw new DomainException('La cotización no contiene líneas válidas.');
            $unidades = 0;
            $necesita = [];
            $importeLineas = [];
            $valorar = false;
            $recetas = [];
            foreach ($items as $item) {
                $idProducto = (int)$item['id_producto'];
                $cantidad = (int)$item['cantidad'];
                if ($idProducto < 1 || $cantidad < 1 || $cantidad > 10) {
                    throw new DomainException('Cantidad incorrecta en la cotización guardada.');
                }
                $unidades += $cantidad;
                $necesita[$idProducto] = ($necesita[$idProducto] ?? 0) + $cantidad;
                $idMica = $item['id_mica'] !== null ? (int)$item['id_mica'] : null;
                $idReceta = $item['id_receta'] !== null ? (int)$item['id_receta'] : null;
                if ($idMica === null && ($idReceta !== null
                        || CotizadorMontos::centavos($item['precio_unitario_mica']) !== 0
                        || $item['estado_optico'] !== 'SIN_MICAS')) {
                    throw new DomainException('Se detectó una línea óptica inconsistente.');
                }
                if ($idMica !== null && ($idMica < 1
                        || CotizadorMontos::centavos($item['precio_unitario_mica']) <= 0)) {
                    throw new DomainException('Mica o importe guardado inválido.');
                }
                if ($idReceta !== null) $recetas[$idReceta] = true;
                $valorar = $valorar || (int)$item['solicita_valoracion'] === 1;
                if ($idReceta !== null && (int)$item['solicita_valoracion'] === 1) {
                    throw new DomainException('Receta y valoración contradictorias.');
                }
                $importeLineas[] = [
                    'cantidad'=>$cantidad,
                    'precio_unitario_armazon'=>$item['precio_unitario_armazon'],
                    'precio_unitario_mica'=>$item['precio_unitario_mica'],
                ];
            }
            if ($unidades > 30) throw new DomainException('Exceso de unidades en la cotización.');
            $montos = CotizadorMontos::totalizar($importeLineas);
            foreach (['subtotal_armazones','subtotal_micas','descuento','impuestos','total'] as $campo) {
                if (CotizadorMontos::centavos($montos[$campo]) !== CotizadorMontos::centavos($c[$campo])) {
                    throw new DomainException('El desglose de la cotización no coincide con el importe capturado.');
                }
            }

            // En 4.6.1 la reserva ya descontó inventario al iniciar PayPal.
            // Capturas históricas anteriores a la migración se concilian con la lógica antigua.
            $reserva = $this->uno('SELECT * FROM inventario_reservas
                WHERE id_cotizacion=? FOR UPDATE',[$idCotizacion]);
            $nuevaReserva = $reserva !== null;
            $idsProductos = array_keys($necesita);
            sort($idsProductos, SORT_NUMERIC);
            if ($nuevaReserva) {
                if ((int)$reserva['id_cliente']!==$idCliente ||
                    !in_array($reserva['estado'],['PROCESANDO','REVISAR'],true)) {
                    throw new DomainException('Reserva de inventario no disponible. Revisión manual necesaria.');
                }
                $guardadas = $this->varios('SELECT id_producto,cantidad FROM inventario_reserva_items
                    WHERE id_reserva=? ORDER BY id_producto',[(int)$reserva['id_reserva']]);
                if (count($guardadas)!==count($idsProductos)) {
                    throw new DomainException('Reserva incompatible con las líneas de la cotización.');
                }
                foreach ($guardadas as $index=>$guardada) {
                    $id=$idsProductos[$index];
                    if ((int)$guardada['id_producto']!==$id ||
                        (int)$guardada['cantidad']!==$necesita[$id]) {
                        throw new DomainException('Las cantidades reservadas no coinciden con la cotización.');
                    }
                }
            } else {
                // Compras capturadas ANTES de instalar 4.6.1 (p. ej., cotización #7).
                foreach ($idsProductos as $id) {
                    $stock=$this->uno('SELECT id,cantidad,estado FROM productos WHERE id=? FOR UPDATE',[$id]);
                    if (!$stock || (int)$stock['estado']!==1 || (int)$stock['cantidad']<$necesita[$id]) {
                        throw new DomainException('PAGO HISTÓRICO CAPTURADO, PEDIDO PENDIENTE: inventario insuficiente para producto #'.$id.'. Solicita conciliación; no vuelvas a pagar.');
                    }
                }
            }
            // La receta no necesita estar aprobada para PAGAR, pero sí debe pertenecer al comprador.
            // Los cambios de revisión posteriores al pago nunca autorizan fabricación automáticamente.
            $estadosRecetas = [];
            $idsRecetas = array_keys($recetas);
            sort($idsRecetas, SORT_NUMERIC);
            foreach ($idsRecetas as $id) {
                $r = $this->uno('SELECT id_receta,id_cliente,estado_revision FROM recetas_opticas
                    WHERE id_receta=? FOR UPDATE', [$id]);
                if (!$r || (int)$r['id_cliente'] !== $idCliente) {
                    throw new DomainException('La receta no pertenece al comprador.');
                }
                $estadosRecetas[$id] = $r['estado_revision'];
            }

            if (!$nuevaReserva) {
                foreach ($idsProductos as $id) {
                    $q=$this->db->prepare('UPDATE productos SET cantidad=cantidad-?
                        WHERE id=? AND estado=1 AND cantidad>=?');
                    $q->execute([$necesita[$id],$id,$necesita[$id]]);
                    if ($q->rowCount()!==1) throw new DomainException('Inventario histórico insuficiente; conciliación requerida.');
                }
            }

            $corto = static function (string $texto, int $max): string {
                return function_exists('mb_substr') ? mb_substr($texto, 0, $max, 'UTF-8') : substr($texto, 0, $max);
            };
            $apellido = trim((string)$cliente['apellidoP'] . ' ' . (string)$cliente['apellidoM']);
            $crear = $this->db->prepare("INSERT INTO pedidos
                (id_transaccion,monto,estado,fecha,email,nombre,apellido,id_cliente,proceso)
                VALUES (?,?,'COMPLETED',CURDATE(),?,?,?,?,'1')");
            $crear->execute([
                $p['paypal_capture_id'],$c['total'],(string)$cliente['correo'],
                $corto((string)$cliente['nombre'],80),$corto($apellido,80),$idCliente,
            ]);
            $idPedido = (int)$this->db->lastInsertId();
            if ($idPedido <= 0) throw new RuntimeException('No se pudo obtener el ID del pedido.');

            $detalle = $this->db->prepare('INSERT INTO detalle_pedidos
                (producto,precio,cantidad,id_pedido,id_producto) VALUES (?,?,?,?,?)');
            $mica = $this->db->prepare('INSERT INTO detalle_pedido_micas
                (id_detalle_pedido,id_mica,id_receta,nombre_mica_snapshot,precio_unitario_mica,estado_optico)
                VALUES (?,?,?,?,?,?)');
            foreach ($items as $item) {
                // Nunca se fusionan líneas con el mismo armazón; cada receta/mica tiene su detalle.
                $detalle->execute([
                    $item['nombre_producto_snapshot'],$item['precio_unitario_armazon'],
                    $item['cantidad'],$idPedido,$item['id_producto'],
                ]);
                $idDetalle = (int)$this->db->lastInsertId();
                if ($idDetalle <= 0) throw new RuntimeException('No se pudo guardar el detalle del pedido.');
                if ($item['id_mica'] !== null) {
                    $estado = (int)$item['solicita_valoracion'] === 1 ? 'PENDIENTE_VALORACION'
                        : ($item['id_receta'] === null ? 'PENDIENTE_RECETA' : 'PENDIENTE_REVISION');
                    if ($item['id_receta'] !== null && in_array($estadosRecetas[(int)$item['id_receta']],
                        ['REQUIERE_CORRECCION','RECHAZADA'], true)) {
                        $estado = 'REQUIERE_CORRECCION';
                    }
                    $mica->execute([
                        $idDetalle,$item['id_mica'],$item['id_receta'],
                        $item['nombre_mica_snapshot'],$item['precio_unitario_mica'],$estado,
                    ]);
                }
            }
            if ($valorar) {
                $this->db->prepare("INSERT INTO pedido_valoraciones (id_pedido,estado)
                    VALUES (?,'PENDIENTE_AGENDAR')")->execute([$idPedido]);
            }
            $asociar = $this->db->prepare('UPDATE checkout_cotizaciones SET id_pedido=?
                WHERE id_cotizacion=? AND id_pedido IS NULL AND estado=\'PAGADA\'');
            $asociar->execute([$idPedido, $idCotizacion]);
            if ($asociar->rowCount() !== 1) throw new RuntimeException('Conflicto al asociar el pedido con la cotización.');
            if ($nuevaReserva) {
                $u=$this->db->prepare("UPDATE inventario_reservas SET estado='CONSUMIDA',id_pedido=?
                    WHERE id_reserva=? AND estado IN ('PROCESANDO','REVISAR')");
                $u->execute([$idPedido,(int)$reserva['id_reserva']]);
                if ($u->rowCount()!==1) throw new RuntimeException('No se consumió la reserva.');
            }
            // Outbox de confirmación en la MISMA transacción que el pedido.
            // Un problema opcional de correo jamás debe impedir guardar una venta pagada.
            try {
                $notificar = $this->db->prepare("INSERT IGNORE INTO pedido_correos
                    (id_pedido,tipo,estado,proximo_intento)
                    VALUES (?,'CONFIRMACION_COMPRA','PENDIENTE',NOW())");
                $notificar->execute([$idPedido]);
            } catch (PDOException $correoError) {
                error_log('[IVC] Cola de correo no disponible al registrar el pedido #'.$idPedido);
            }
            $this->db->commit();
            return ['pedido_id'=>$idPedido,'ya_existia'=>false,'requiere_valoracion'=>$valorar];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
