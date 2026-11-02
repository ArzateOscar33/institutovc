<?php
class UsuariosModel extends Query
{
    private function db(): PDO { return (new Conexion())->conect(); }

    public function getUsuarios($estado=1): array
    {
        $stmt=$this->db()->prepare('SELECT u.id,u.nombres,u.apellidos,u.correo,u.perfil,u.estado,r.codigo AS rol,r.nombre AS rol_nombre FROM usuario u INNER JOIN roles r ON r.id_rol=u.id_rol WHERE u.estado=? ORDER BY u.id');
        $stmt->execute([(int)$estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsuario($id): ?array
    {
        $q=$this->db()->prepare('SELECT u.id,u.nombres,u.apellidos,u.correo,u.estado,u.id_rol,r.codigo AS rol FROM usuario u INNER JOIN roles r ON r.id_rol=u.id_rol WHERE u.id=?');
        $q->execute([(int)$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function rolesDisponibles(string $rolActor): array
    {
        $sql=$rolActor==='ROOT' ? 'SELECT id_rol,nombre,codigo FROM roles WHERE activo=1 ORDER BY id_rol' : 'SELECT id_rol,nombre,codigo FROM roles WHERE activo=1 AND codigo IN (\'ADMINISTRADOR\',\'VENDEDOR\') ORDER BY id_rol';
        return $this->db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(int $actorId, string $rolActor, int $id, string $nombres, string $apellidos, string $correo, string $clave, int $rolId): string
    {
        $db=$this->db(); $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT id, id_rol, estado FROM usuario WHERE id=? FOR UPDATE');
            if ($id>0) { $q->execute([$id]); $actual=$q->fetch(PDO::FETCH_ASSOC); if (!$actual) throw new DomainException('Usuario no encontrado.'); }
            else $actual=null;
            $q=$db->prepare('SELECT codigo FROM roles WHERE id_rol=? AND activo=1'); $q->execute([$rolId]);
            $rolNuevo=$q->fetchColumn();
            if (!$rolNuevo) throw new DomainException('Selecciona un rol válido.');
            if ($rolActor!=='ROOT' && !in_array($rolNuevo,['ADMINISTRADOR','VENDEDOR'],true)) throw new DomainException('Solo ROOT puede asignar roles personalizados o ROOT.');
            if ($actual) {
                $q=$db->prepare('SELECT codigo FROM roles WHERE id_rol=?'); $q->execute([(int)$actual['id_rol']]);
                $rolAnterior=$q->fetchColumn();
                if ($rolAnterior==='ROOT' && $rolActor!=='ROOT') throw new DomainException('La cuenta ROOT está protegida.');
                if ($id===$actorId && $rolAnterior!==$rolNuevo) throw new DomainException('No puedes modificar tu propio rol.');
                if ($rolAnterior==='ROOT' && $rolNuevo!=='ROOT') {
                    $q=$db->query("SELECT COUNT(*) FROM usuario WHERE estado=1 AND id_rol=1");
                    if ((int)$q->fetchColumn()<=1) throw new DomainException('No puedes retirar el último usuario ROOT.');
                }
            }
            $q=$db->prepare('SELECT id FROM usuario WHERE correo=? AND id<>? LIMIT 1');
            $q->execute([$correo,$id]);
            if ($q->fetch()) throw new DomainException('Ese correo ya está registrado.');
            if ($actual) {
                $q=$db->prepare('UPDATE usuario SET nombres=?,apellidos=?,correo=?,id_rol=? WHERE id=?');
                $q->execute([$nombres,$apellidos,$correo,$rolId,$id]);
                $msg='Usuario actualizado.';
            } else {
                if (strlen($clave)<10) throw new DomainException('La contraseña debe tener al menos 10 caracteres.');
                $q=$db->prepare('INSERT INTO usuario(nombres,apellidos,correo,clave,id_rol,estado) VALUES(?,?,?,?,?,1)');
                $q->execute([$nombres,$apellidos,$correo,password_hash($clave,PASSWORD_DEFAULT),$rolId]);
                $msg='Usuario registrado.';
            }
            $db->commit(); return $msg;
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
    }

    public function desactivar(int $actorId, string $rolActor, int $id): void
    {
        $db=$this->db(); $db->beginTransaction();
        try {
            if ($id===$actorId) throw new DomainException('No puedes desactivar tu propia cuenta.');
            $q=$db->prepare('SELECT u.id,u.estado,r.codigo AS rol FROM usuario u INNER JOIN roles r ON r.id_rol=u.id_rol WHERE u.id=? FOR UPDATE');
            $q->execute([$id]);$u=$q->fetch(PDO::FETCH_ASSOC);
            if (!$u) throw new DomainException('Usuario no encontrado.');
            if ($u['rol']==='ROOT' && $rolActor!=='ROOT') throw new DomainException('La cuenta ROOT está protegida.');
            if ($u['rol']==='ROOT') {
                $q=$db->query('SELECT COUNT(*) FROM usuario WHERE estado=1 AND id_rol=1');
                if ((int)$q->fetchColumn()<=1) throw new DomainException('Debe existir al menos un ROOT activo.');
            }
            $q=$db->prepare('UPDATE usuario SET estado=0 WHERE id=?');$q->execute([$id]);
            $db->commit();
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
    }
}
