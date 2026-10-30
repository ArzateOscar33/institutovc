<?php
/**
 * Catálogo de micas del panel de administración.
 * Reutiliza las tablas existentes de la migración V1.
 * Las escrituras y el historial de precios se ejecutan en la MISMA transacción.
 */
class MicasModel
{
    private $db;

    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->conect();
        if (!$this->db instanceof PDO) {
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function consultarTodo($sql, array $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function consultarUno($sql, array $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function usuarioActual($correo)
    {
        $registro = $this->consultarUno('SELECT id FROM usuario WHERE correo = ? AND estado = 1 LIMIT 1', [$correo]);
        return $registro ? (int)$registro['id'] : null;
    }

    public function catalogos()
    {
        return [
            'tipos' => $this->consultarTodo('SELECT id_tipo_lente AS id, nombre FROM tipos_lente WHERE activo=1 ORDER BY id_tipo_lente'),
            'materiales' => $this->consultarTodo('SELECT id_material AS id, nombre FROM materiales_mica ORDER BY nombre'),
            'tratamientos' => $this->consultarTodo('SELECT id_tratamiento AS id, nombre FROM tratamientos_mica ORDER BY nombre')
        ];
    }

    public function listar()
    {
        $sql = "SELECT m.id_mica, m.sku, m.id_tipo_lente, t.nombre AS tipo_lente,
                       m.nombre_comercial, m.precio_venta, m.unidad_cobro, m.moneda,
                       m.activo, m.requiere_revision, m.notas_internas, m.actualizado_en,
                       COALESCE((SELECT GROUP_CONCAT(DISTINCT ma.nombre ORDER BY ma.nombre SEPARATOR ', ')
                           FROM catalogo_micas_materiales cm
                           JOIN materiales_mica ma ON ma.id_material=cm.id_material
                           WHERE cm.id_mica=m.id_mica), '') AS materiales,
                       COALESCE((SELECT GROUP_CONCAT(DISTINCT tr.nombre ORDER BY tr.nombre SEPARATOR ', ')
                           FROM catalogo_micas_tratamientos ct
                           JOIN tratamientos_mica tr ON tr.id_tratamiento=ct.id_tratamiento
                           WHERE ct.id_mica=m.id_mica), '') AS tratamientos
                FROM catalogo_micas m JOIN tipos_lente t ON t.id_tipo_lente=m.id_tipo_lente
                ORDER BY m.id_tipo_lente, m.nombre_comercial, m.id_mica";
        return $this->consultarTodo($sql);
    }

    public function obtener($id)
    {
        $item = $this->consultarUno('SELECT id_mica,sku,id_tipo_lente,nombre_comercial,precio_venta,
                    unidad_cobro,moneda,activo,requiere_revision,notas_internas
                    FROM catalogo_micas WHERE id_mica = ?', [$id]);
        if (!$item) return null;
        $mat = $this->consultarTodo('SELECT id_material FROM catalogo_micas_materiales WHERE id_mica=? ORDER BY id_material', [$id]);
        $tra = $this->consultarTodo('SELECT id_tratamiento FROM catalogo_micas_tratamientos WHERE id_mica=? ORDER BY id_tratamiento', [$id]);
        $item['materiales'] = array_map('intval', array_column($mat, 'id_material'));
        $item['tratamientos'] = array_map('intval', array_column($tra, 'id_tratamiento'));
        return $item;
    }

    public function historial($id)
    {
        return $this->consultarTodo("SELECT h.id_historial, h.precio_anterior, h.precio_nuevo,
                    h.motivo, h.fecha_cambio,
                    COALESCE(CONCAT(u.nombres, ' ', u.apellidos), 'Sistema') AS usuario
                    FROM historial_precios_micas h LEFT JOIN usuario u ON u.id=h.id_usuario
                    WHERE h.id_mica=? ORDER BY h.fecha_cambio DESC,h.id_historial DESC LIMIT 100", [$id]);
    }

    private function validarAsociaciones($tipo, array $materiales, array $tratamientos)
    {
        if (!$this->consultarUno('SELECT id_tipo_lente FROM tipos_lente WHERE id_tipo_lente=? AND activo=1', [$tipo])) {
            throw new InvalidArgumentException('Selecciona un tipo de lente válido.');
        }
        if (!$materiales) throw new InvalidArgumentException('Selecciona al menos un material.');
        foreach ($materiales as $id) {
            if (!$this->consultarUno('SELECT id_material FROM materiales_mica WHERE id_material=?', [$id])) {
                throw new InvalidArgumentException('Hay un material no válido.');
            }
        }
        foreach ($tratamientos as $id) {
            if (!$this->consultarUno('SELECT id_tratamiento FROM tratamientos_mica WHERE id_tratamiento=?', [$id])) {
                throw new InvalidArgumentException('Hay un tratamiento no válido.');
            }
        }
    }

    /** $datos ya validados por el controlador. */
    public function guardar(array $datos, $idUsuario)
    {
        $this->db->beginTransaction();
        try {
            $id = (int)$datos['id_mica'];
            $this->validarAsociaciones((int)$datos['id_tipo_lente'], $datos['materiales'], $datos['tratamientos']);
            $old = null;
            if ($id > 0) {
                // Bloqueo contra ediciones simultáneas.
                $old = $this->consultarUno('SELECT id_mica, precio_venta FROM catalogo_micas WHERE id_mica=? FOR UPDATE', [$id]);
                if (!$old) throw new InvalidArgumentException('La mica que deseas editar no existe.');
                if (number_format((float)$old['precio_venta'], 2, '.', '') !== $datos['precio_anterior']) {
                    throw new DomainException('El precio cambió desde que abriste este formulario. Actualiza la lista e inténtalo de nuevo.');
                }
                $stmt = $this->db->prepare('UPDATE catalogo_micas
                    SET sku=?, id_tipo_lente=?, nombre_comercial=?, precio_venta=?,
                        unidad_cobro=\'PAR\', moneda=\'MXN\', requiere_revision=1, notas_internas=?
                    WHERE id_mica=?');
                $stmt->execute([$datos['sku'],$datos['id_tipo_lente'],$datos['nombre_comercial'],
                    $datos['precio_venta'],$datos['notas_internas'],$id]);
            } else {
                $stmt = $this->db->prepare('INSERT INTO catalogo_micas
                    (sku,id_tipo_lente,nombre_comercial,precio_venta,unidad_cobro,moneda,activo,requiere_revision,notas_internas)
                    VALUES (?,?,?, ?,\'PAR\',\'MXN\',1,1,?)');
                $stmt->execute([$datos['sku'],$datos['id_tipo_lente'],$datos['nombre_comercial'],
                    $datos['precio_venta'],$datos['notas_internas']]);
                $id = (int)$this->db->lastInsertId();
            }
            $this->db->prepare('DELETE FROM catalogo_micas_materiales WHERE id_mica=?')->execute([$id]);
            $this->db->prepare('DELETE FROM catalogo_micas_tratamientos WHERE id_mica=?')->execute([$id]);
            $asocMat = $this->db->prepare('INSERT INTO catalogo_micas_materiales (id_mica,id_material) VALUES (?,?)');
            foreach ($datos['materiales'] as $material) $asocMat->execute([$id, $material]);
            $asocTra = $this->db->prepare('INSERT INTO catalogo_micas_tratamientos (id_mica,id_tratamiento) VALUES (?,?)');
            foreach ($datos['tratamientos'] as $tratamiento) $asocTra->execute([$id, $tratamiento]);

            $anterior = $old ? number_format((float)$old['precio_venta'], 2, '.', '') : '0.00';
            if (!$old || $anterior !== $datos['precio_venta']) {
                $motivo = $old ? ($datos['motivo_precio'] ?: 'Actualización del precio de venta') : 'Alta de mica';
                $stmt = $this->db->prepare('INSERT INTO historial_precios_micas
                    (id_mica,precio_anterior,precio_nuevo,id_usuario,motivo) VALUES (?,?,?,?,?)');
                $stmt->execute([$id,$anterior,$datos['precio_venta'],$idUsuario,$motivo]);
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function cambiarEstado($id, $activo)
    {
        $stmt = $this->db->prepare('UPDATE catalogo_micas SET activo=? WHERE id_mica=?');
        $stmt->execute([$activo, $id]);
        return $stmt->rowCount() > 0;
    }
}
