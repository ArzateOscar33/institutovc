<?php
require_once __DIR__ . '/RecetasModel.php';
class RecetasAdminModel extends RecetasModel
{
    /** Consulta limitada para seleccionar persona; nunca devuelve contraseñas, tokens o recetas ajenas. */
    public function buscarClientesParaCarga(string $busqueda): array
    {
        $busqueda = trim($busqueda);
        if ((function_exists('mb_strlen') ? mb_strlen($busqueda, 'UTF-8') : strlen($busqueda)) < 2
            || (function_exists('mb_strlen') ? mb_strlen($busqueda, 'UTF-8') : strlen($busqueda)) > 90) {
            throw new InvalidArgumentException('Escribe entre 2 y 90 caracteres para buscar clientes.');
        }
        $patron = '%' . str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $busqueda) . '%';
        $sql = "SELECT id, CONCAT_WS(' ',nombre,apellidoP,NULLIF(apellidoM,'')) AS nombre_completo,
                       correo,telefono,origen
                FROM clientes
                WHERE CONCAT_WS(' ',nombre,apellidoP,NULLIF(apellidoM,'')) LIKE ?
                   OR COALESCE(correo,'') LIKE ? OR COALESCE(telefono,'') LIKE ?
                   OR CAST(id AS CHAR) = ?
                ORDER BY id DESC LIMIT 20";
        return $this->queryAll($sql,[$patron,$patron,$patron,$busqueda]);
    }

    /** Opciones para enlazar receta nueva con la mica del pedido que AÚN no tiene receta. */
    public function detallesPendientesCliente(int $idCliente): array
    {
        if ($idCliente < 1) return [];
        return $this->queryAll("SELECT d.id AS id_detalle, d.id_pedido, d.producto, m.nombre_mica_snapshot,
                                       m.estado_optico, p.fecha
            FROM detalle_pedido_micas m
            JOIN detalle_pedidos d ON d.id = m.id_detalle_pedido
            JOIN pedidos p ON p.id = d.id_pedido
            WHERE p.id_cliente = ? AND p.estado = 'COMPLETED' AND p.proceso = '1'
              AND m.id_receta IS NULL AND m.estado_optico IN ('PENDIENTE_RECETA','PENDIENTE_REVISION')
            ORDER BY d.id_pedido DESC,d.id DESC LIMIT 40", [$idCliente]);
    }

    /** Inserta receta y archivo; opcionalmente enlaza una sola mica pendiente de un pedido pagado. */
    public function cargarDesdeMostrador(int $idCliente, ?int $idDetalle, int $idUsuario,
                                          ?string $fecha, ?string $observaciones, array $archivo): int
    {
        if ($idCliente < 1 || $idUsuario < 1) throw new InvalidArgumentException('Cliente o usuario incorrecto.');
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare('SELECT id FROM clientes WHERE id=? FOR UPDATE');
            $s->execute([$idCliente]);
            if (!$s->fetchColumn()) throw new DomainException('El cliente ya no existe.');
            if ($idDetalle !== null) {
                $s = $this->db->prepare("SELECT p.id_cliente,p.estado,p.proceso,m.id_receta,m.estado_optico
                    FROM detalle_pedido_micas m
                    JOIN detalle_pedidos d ON d.id=m.id_detalle_pedido
                    JOIN pedidos p ON p.id=d.id_pedido
                    WHERE m.id_detalle_pedido=? FOR UPDATE");
                $s->execute([$idDetalle]);
                $linea=$s->fetch(PDO::FETCH_ASSOC);
                if (!$linea || (int)$linea['id_cliente']!==$idCliente
                    || $linea['estado']!=='COMPLETED' || $linea['proceso']!=='1'
                    || $linea['id_receta']!==null
                    || !in_array($linea['estado_optico'],['PENDIENTE_RECETA','PENDIENTE_REVISION'],true)) {
                    throw new DomainException('El renglón ya cambió o no corresponde a una mica pendiente de este cliente.');
                }
            }
            $s=$this->db->prepare("INSERT INTO recetas_opticas
                (id_cliente,origen,modalidad,estado_revision,fecha_receta,observaciones_cliente)
                VALUES (?,'EXTERNA','ARCHIVO','PENDIENTE',?,?)");
            $s->execute([$idCliente,$fecha,$observaciones]);
            $idReceta=(int)$this->db->lastInsertId();
            $s=$this->db->prepare('INSERT INTO receta_archivos
                (id_receta,nombre_original,ruta_privada,tipo_mime,tamano_bytes,hash_sha256)
                VALUES (?,?,?,?,?,?)');
            $s->execute([$idReceta,$archivo['original'],$archivo['nombre'],$archivo['mime'],$archivo['size'],$archivo['sha256']]);
            $s=$this->db->prepare('INSERT INTO recetas_cargas_admin
                (id_receta,id_usuario,id_detalle_pedido) VALUES (?,?,?)');
            $s->execute([$idReceta,$idUsuario,$idDetalle]);
            if ($idDetalle !== null) {
                $s=$this->db->prepare("UPDATE detalle_pedido_micas SET
                    id_receta=?,estado_optico='PENDIENTE_REVISION',
                    id_usuario_revisor=NULL,fecha_revision=NULL,
                    observaciones='Receta recibida en mostrador; pendiente de revisión óptica.'
                    WHERE id_detalle_pedido=? AND id_receta IS NULL
                      AND estado_optico IN ('PENDIENTE_RECETA','PENDIENTE_REVISION')");
                $s->execute([$idReceta,$idDetalle]);
                if ($s->rowCount()!==1) throw new DomainException('El artículo cambió de estado. No se asoció la receta.');
            }
            $this->db->commit();
            return $idReceta;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

}

