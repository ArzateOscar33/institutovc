<?php
/** Motor de cotizaciones: todos los precios provienen de MySQL, nunca del navegador. */
class CotizacionesModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Conexion())->conect();
        if (!$this->db instanceof PDO) {
            throw new RuntimeException('No fue posible conectar con MySQL.');
        }
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function uno(string $sql, array $parametros = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function varios(string $sql, array $parametros = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clienteVigente(int $id, string $correo): bool
    {
        return (bool)$this->uno('SELECT id FROM clientes WHERE id=? AND correo=? AND verify=1', [$id, $correo]);
    }

    public function catalogo(): array
    {
        return $this->varios("SELECT m.id_mica, m.sku, m.nombre_comercial, m.precio_venta,
                       m.unidad_cobro, m.moneda, t.nombre AS tipo_lente, m.requiere_revision
                FROM catalogo_micas m
                INNER JOIN tipos_lente t ON t.id_tipo_lente=m.id_tipo_lente
                WHERE m.activo=1 AND m.unidad_cobro='PAR' AND m.moneda='MXN' AND t.activo=1
                ORDER BY m.id_tipo_lente, m.precio_venta, m.id_mica");
    }

    /** @throws InvalidArgumentException|DomainException */
    public function crear(int $idCliente, array $selecciones): array
    {
        if (count($selecciones) < 1 || count($selecciones) > 30) {
            throw new InvalidArgumentException('Selecciona entre 1 y 30 líneas de productos.');
        }

        // Normalización de la solicitud (sin importes, nombres ni descuentos desde el cliente).
        $normalizadas = [];
        $cantidadesPorProducto = [];
        $unidades = 0;
        foreach ($selecciones as $i => $sel) {
            if (!is_array($sel) || $this->esLista($sel)) {
                throw new InvalidArgumentException('Formato inválido en la línea ' . ($i + 1) . '.');
            }
            $idProducto = $this->entero($sel['id_producto'] ?? null, 1, 2147483647, 'Producto');
            $cantidad = $this->entero($sel['cantidad'] ?? null, 1, 10, 'Cantidad');
            $idMica = $this->enteroOpcional($sel['id_mica'] ?? null, 'Mica');
            $idReceta = $this->enteroOpcional($sel['id_receta'] ?? null, 'Receta');
            $valoracion = $sel['solicita_valoracion'] ?? false;
            if (!in_array($valoracion, [true, false, 0, 1, '0', '1'], true)) {
                throw new InvalidArgumentException('Indicación de valoración inválida.');
            }
            $valoracion = in_array($valoracion, [true, 1, '1'], true);
            if ($idMica === null && $idReceta !== null) {
                throw new InvalidArgumentException('No es posible asociar una receta sin seleccionar micas.');
            }
            if ($valoracion && $idReceta !== null) {
                throw new InvalidArgumentException('Elige receta registrada o valoración pendiente; no ambas.');
            }
            $normalizadas[] = [
                'id_producto'=>$idProducto, 'cantidad'=>$cantidad,
                'id_mica'=>$idMica, 'id_receta'=>$idReceta,
                'solicita_valoracion'=>$valoracion,
            ];
            $unidades += $cantidad;
            $cantidadesPorProducto[$idProducto] = ($cantidadesPorProducto[$idProducto] ?? 0) + $cantidad;
        }
        if ($unidades > 30) {
            throw new InvalidArgumentException('Máximo 30 unidades por cotización.');
        }

        // Bloqueo ordenado, para evitar leer precios mientras el administrador los actualiza.
        $idsProductos = array_keys($cantidadesPorProducto);
        sort($idsProductos, SORT_NUMERIC);
        $idsMicas = array_values(array_unique(array_filter(array_column($normalizadas, 'id_mica'), static function ($v) {
            return $v !== null;
        })));
        sort($idsMicas, SORT_NUMERIC);
        $idsRecetas = array_values(array_unique(array_filter(array_column($normalizadas, 'id_receta'), static function ($v) {
            return $v !== null;
        })));
        sort($idsRecetas, SORT_NUMERIC);

        $this->db->beginTransaction();
        try {
            $productos = [];
            foreach ($idsProductos as $id) {
                $p = $this->uno('SELECT id, nombre, precio, cantidad, estado FROM productos WHERE id=? FOR UPDATE', [$id]);
                if (!$p || (int)$p['estado'] !== 1 || (int)$p['cantidad'] < $cantidadesPorProducto[$id]) {
                    throw new DomainException('Un producto no está disponible o no tiene existencias suficientes (ID ' . $id . ').');
                }
                if (CotizadorMontos::centavos($p['precio']) <= 0) {
                    throw new DomainException('El producto ' . $id . ' no tiene un precio de venta válido.');
                }
                $productos[$id] = $p;
            }

            $micas = [];
            foreach ($idsMicas as $id) {
                $m = $this->uno("SELECT m.id_mica, m.nombre_comercial, m.precio_venta, m.moneda,
                                m.unidad_cobro, m.activo, t.activo AS tipo_activo
                                FROM catalogo_micas m JOIN tipos_lente t ON t.id_tipo_lente=m.id_tipo_lente
                                WHERE m.id_mica=? FOR UPDATE", [$id]);
                if (!$m || (int)$m['activo'] !== 1 || (int)$m['tipo_activo'] !== 1 || $m['unidad_cobro'] !== 'PAR' || $m['moneda'] !== 'MXN') {
                    throw new DomainException('La mica ' . $id . ' no está disponible para cotizar por par en MXN.');
                }
                if (CotizadorMontos::centavos($m['precio_venta']) <= 0) {
                    throw new DomainException('La mica ' . $id . ' no tiene precio válido.');
                }
                $micas[$id] = $m;
            }

            $recetas = [];
            foreach ($idsRecetas as $id) {
                $r = $this->uno('SELECT id_receta, id_cliente, estado_revision FROM recetas_opticas WHERE id_receta=? FOR UPDATE', [$id]);
                if (!$r || (int)$r['id_cliente'] !== $idCliente || !in_array($r['estado_revision'], ['PENDIENTE', 'APROBADA'], true)) {
                    throw new DomainException('La receta ' . $id . ' no pertenece al cliente o no está disponible para esta compra.');
                }
                $recetas[$id] = $r;
            }

            $lineas = [];
            foreach ($normalizadas as $sel) {
                $p = $productos[$sel['id_producto']];
                $m = $sel['id_mica'] !== null ? $micas[$sel['id_mica']] : null;
                $r = $sel['id_receta'] !== null ? $recetas[$sel['id_receta']] : null;
                $estado = 'SIN_MICAS';
                if ($m !== null) {
                    $estado = $sel['solicita_valoracion'] ? 'PENDIENTE_VALORACION'
                            : ($r === null ? 'PENDIENTE_RECETA'
                            : 'PENDIENTE_REVISION');
                }
                $lineas[] = [
                    'id_producto'=>$sel['id_producto'], 'cantidad'=>$sel['cantidad'],
                    'nombre_producto_snapshot'=>function_exists('mb_substr') ? mb_substr((string)$p['nombre'], 0, 255, 'UTF-8') : substr((string)$p['nombre'], 0, 255),
                    'precio_unitario_armazon'=>(string)$p['precio'],
                    'id_mica'=>$sel['id_mica'],
                    'nombre_mica_snapshot'=>$m !== null ? $m['nombre_comercial'] : null,
                    'precio_unitario_mica'=>$m !== null ? (string)$m['precio_venta'] : '0.00',
                    'id_receta'=>$sel['id_receta'],
                    'estado_optico'=>$estado,
                    'solicita_valoracion'=>$sel['solicita_valoracion'] ? 1 : 0,
                ];
            }
            $montos = CotizadorMontos::totalizar($lineas);
            if ($montos['total'] === '0.00') throw new DomainException('El total de la compra debe ser mayor a cero.');

            $stmt = $this->db->prepare("INSERT INTO checkout_cotizaciones
                    (id_cliente, estado, moneda, subtotal_armazones, subtotal_micas,
                     descuento, impuestos, total, expira_en)
                    VALUES (?, 'ABIERTA', 'MXN', ?, ?, '0.00', '0.00', ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
            $stmt->execute([$idCliente, $montos['subtotal_armazones'], $montos['subtotal_micas'], $montos['total']]);
            $idCotizacion = (int)$this->db->lastInsertId();
            $stmt = $this->db->prepare('INSERT INTO checkout_cotizacion_items
                (id_cotizacion, id_producto, cantidad, nombre_producto_snapshot,
                 precio_unitario_armazon, id_mica, nombre_mica_snapshot, precio_unitario_mica,
                 id_receta, estado_optico, solicita_valoracion)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            foreach ($lineas as $linea) {
                $stmt->execute([
                    $idCotizacion, $linea['id_producto'], $linea['cantidad'], $linea['nombre_producto_snapshot'],
                    $linea['precio_unitario_armazon'], $linea['id_mica'], $linea['nombre_mica_snapshot'],
                    $linea['precio_unitario_mica'], $linea['id_receta'], $linea['estado_optico'],
                    $linea['solicita_valoracion'],
                ]);
            }
            $this->db->commit();
            return $this->obtener($idCotizacion, $idCliente);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function obtener(int $idCotizacion, int $idCliente)
    {
        $cotizacion = $this->uno('SELECT id_cotizacion, estado, moneda, subtotal_armazones,
            subtotal_micas, descuento, impuestos, total, expira_en, creado_en,
            (expira_en <= NOW()) AS vencida
            FROM checkout_cotizaciones WHERE id_cotizacion=? AND id_cliente=? LIMIT 1',
            [$idCotizacion, $idCliente]);
        if (!$cotizacion) return null;
        $cotizacion['items'] = $this->varios('SELECT id_item, id_producto, cantidad,
            nombre_producto_snapshot, precio_unitario_armazon, id_mica,
            nombre_mica_snapshot, precio_unitario_mica, id_receta,
            estado_optico, solicita_valoracion
            FROM checkout_cotizacion_items WHERE id_cotizacion=? ORDER BY id_item', [$idCotizacion]);
        $cotizacion['vencida'] = (bool)$cotizacion['vencida'];
        $cotizacion['puede_pagarse'] = false; // Se habilita en la fase PayPal, nunca desde este endpoint.
        return $cotizacion;
    }

    private function esLista(array $a): bool
    {
        return array_keys($a) === range(0, count($a) - 1);
    }

    private function entero($valor, int $min, int $max, string $campo): int
    {
        if (!is_int($valor) && !(is_string($valor) && preg_match('/^[0-9]{1,10}$/D', $valor))) {
            throw new InvalidArgumentException($campo . ' debe ser un entero.');
        }
        $numero = filter_var($valor, FILTER_VALIDATE_INT, ['options'=>['min_range'=>$min,'max_range'=>$max]]);
        if ($numero === false) throw new InvalidArgumentException($campo . ' fuera de rango.');
        return (int)$numero;
    }

    private function enteroOpcional($valor, string $campo)
    {
        return $valor === null ? null : $this->entero($valor, 1, 2147483647, $campo);
    }
}
