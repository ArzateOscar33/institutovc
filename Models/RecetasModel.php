<?php
/** Persistencia de recetas externas. No asocia recetas a pedidos ni altera cobros. */
class RecetasModel
{
    protected $db;
    public function __construct()
    {
        $this->db = (new Conexion())->conect();
        if (!$this->db instanceof PDO) throw new RuntimeException('No se pudo conectar a MySQL.');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    protected function queryOne($sql, array $params = [])
    {
        $q = $this->db->prepare($sql); $q->execute($params);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    protected function queryAll($sql, array $params = [])
    {
        $q = $this->db->prepare($sql); $q->execute($params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    public function clienteVigente($id, $correo)
    {
        return $this->queryOne('SELECT id, correo FROM clientes WHERE id=? AND correo=? AND verify=1 LIMIT 1', [$id, $correo]);
    }
    public function usuarioVigente($correo)
    {
        return $this->queryOne('SELECT id FROM usuario WHERE correo=? AND estado=1 LIMIT 1', [$correo]);
    }
    /** Inserta cabecera, graduación por ojo y metadatos de archivo de forma atómica. */
    public function registrar($cliente, array $datos, ?array $archivo)
    {
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare('INSERT INTO recetas_opticas
                  (id_cliente,origen,modalidad,estado_revision,fecha_receta,distancia_pupilar_total_mm,observaciones_cliente)
                  VALUES (?,\'EXTERNA\',?,\'PENDIENTE\',?,?,?)');
            $q->execute([$cliente, $datos['modalidad'], $datos['fecha'], $datos['dp'], $datos['observaciones']]);
            $id = (int)$this->db->lastInsertId();
            if ($datos['modalidad'] === 'MANUAL') {
                $q = $this->db->prepare('INSERT INTO receta_graduacion_ojo
                    (id_receta,ojo,esfera,cilindro,eje,adicion,dp_monocular_mm)
                    VALUES (?,?,?,?,?,?,NULL)');
                foreach (['OD','OI'] as $ojo) {
                    $e = $datos['ojos'][$ojo];
                    $q->execute([$id,$ojo,$e['esfera'],$e['cilindro'],$e['eje'],$e['adicion']]);
                }
            }
            if ($archivo !== null) {
                $q = $this->db->prepare('INSERT INTO receta_archivos
                    (id_receta,nombre_original,ruta_privada,tipo_mime,tamano_bytes,hash_sha256)
                    VALUES (?,?,?,?,?,?)');
                $q->execute([$id,$archivo['original'],$archivo['nombre'],$archivo['mime'],$archivo['size'],$archivo['sha256']]);
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    public function misRecetas($idCliente)
    {
        return $this->queryAll("SELECT r.id_receta,r.modalidad,r.estado_revision,r.fecha_receta,r.creado_en,
          (SELECT COUNT(*) FROM receta_archivos a WHERE a.id_receta=r.id_receta) AS archivos,
          r.observaciones_revision
          FROM recetas_opticas r WHERE r.id_cliente=? ORDER BY r.id_receta DESC LIMIT 50", [$idCliente]);
    }
    public function listarAdmin()
    {
        return $this->queryAll("SELECT r.id_receta,r.modalidad,r.estado_revision,r.fecha_receta,r.creado_en,
          r.observaciones_cliente,r.observaciones_revision,
          CONCAT(c.nombre,' ',c.apellidoP) AS cliente,c.correo,
          (SELECT COUNT(*) FROM receta_archivos a WHERE a.id_receta=r.id_receta) AS archivos
          FROM recetas_opticas r JOIN clientes c ON c.id=r.id_cliente
          ORDER BY CASE WHEN r.estado_revision='PENDIENTE' THEN 0 ELSE 1 END,r.id_receta DESC LIMIT 300");
    }
    public function detalle($id)
    {
        $r = $this->queryOne("SELECT r.*,CONCAT_WS(' ',c.nombre,c.apellidoP,NULLIF(c.apellidoM,'')) AS cliente,c.correo,
            CONCAT(COALESCE(u.nombres,''),' ',COALESCE(u.apellidos,'')) AS revisor
            FROM recetas_opticas r JOIN clientes c ON c.id=r.id_cliente
            LEFT JOIN usuario u ON u.id=r.id_usuario_revisor WHERE r.id_receta=?", [$id]);
        if (!$r) return null;
        $r['ojos'] = $this->queryAll('SELECT ojo,esfera,cilindro,eje,adicion,dp_monocular_mm FROM receta_graduacion_ojo WHERE id_receta=? ORDER BY ojo', [$id]);
        $r['archivos'] = $this->queryAll('SELECT id_archivo,nombre_original,tipo_mime,tamano_bytes,creado_en FROM receta_archivos WHERE id_receta=? ORDER BY id_archivo', [$id]);
        return $r;
    }
    public function archivo($id)
    {
        return $this->queryOne('SELECT a.*,r.id_cliente FROM receta_archivos a JOIN recetas_opticas r ON r.id_receta=a.id_receta WHERE a.id_archivo=?', [$id]);
    }
    public function revisar($id, $estado, $nota, $idUsuario)
    {
        $this->db->beginTransaction();
        try {
            $r = $this->queryOne('SELECT estado_revision FROM recetas_opticas WHERE id_receta=? FOR UPDATE', [$id]);
            if (!$r) throw new InvalidArgumentException('La receta no existe.');
            if ($r['estado_revision'] !== 'PENDIENTE') throw new DomainException('Esta receta ya fue revisada. Recarga el listado.');
            $q = $this->db->prepare('UPDATE recetas_opticas SET estado_revision=?,observaciones_revision=?,id_usuario_revisor=?,fecha_revision=NOW() WHERE id_receta=?');
            $q->execute([$estado,$nota,$idUsuario,$id]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
