<?php
/** Persistencia de roles administrados únicamente por ROOT. */
class RolesModel extends Query
{
    private function db(): PDO { return (new Conexion())->conect(); }

    public function listar(): array
    {
        $sql = 'SELECT r.id_rol,r.codigo,r.nombre,r.descripcion,r.activo,
                (SELECT COUNT(*) FROM usuario u WHERE u.id_rol=r.id_rol AND u.estado=1) AS usuarios,
                (SELECT COUNT(*) FROM rol_permisos p WHERE p.id_rol=r.id_rol) AS permisos
                FROM roles r ORDER BY CASE r.id_rol WHEN 1 THEN 0 WHEN 2 THEN 1 WHEN 3 THEN 2 ELSE 3 END,r.nombre';
        return $this->db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtener(int $id): ?array
    {
        $db=$this->db();
        $q=$db->prepare('SELECT id_rol,codigo,nombre,descripcion,activo FROM roles WHERE id_rol=?');$q->execute([$id]);
        $rol=$q->fetch(PDO::FETCH_ASSOC);
        if (!$rol) return null;
        $q=$db->prepare('SELECT permiso FROM rol_permisos WHERE id_rol=?');$q->execute([$id]);
        $rol['permisos']=$q->fetchAll(PDO::FETCH_COLUMN);
        return $rol;
    }
    public function guardar(int $id, string $codigo, string $nombre, string $descripcion, array $permisos): int
    {
        $catalogo=RolesAcceso::catalogo();
        if ($nombre==='' || strlen($nombre)>70 || strlen($descripcion)>220) throw new DomainException('Revisa el nombre y la descripción.');
        $codigos=array_values(array_unique($permisos));
        if (!$codigos || count($codigos)>count($catalogo)) throw new DomainException('Selecciona al menos un permiso.');
        foreach ($codigos as $p) if (!is_string($p) || !array_key_exists($p,$catalogo)) throw new DomainException('Permiso no reconocido.');
        $deps = [
            'pedidos.detalle'=>['pedidos.listar'], 'pedidos.administrar'=>['pedidos.listar','pedidos.detalle'],
            'recetas.administrar'=>['recetas.leer'], 'citas.crear'=>['citas.leer'], 'citas.administrar'=>['citas.leer'],
            'productos.administrar'=>['productos.leer'],'categorias.administrar'=>['categorias.leer'],'micas.administrar'=>['micas.leer'],
        ];
        foreach ($codigos as $p) foreach ($deps[$p]??[] as $d) $codigos[]=$d;
        $codigos=array_values(array_unique($codigos));
        $db=$this->db(); $db->beginTransaction();
        try {
            if ($id>0) {
                $q=$db->prepare('SELECT id_rol,codigo,activo FROM roles WHERE id_rol=? FOR UPDATE');$q->execute([$id]);
                $old=$q->fetch(PDO::FETCH_ASSOC);
                if (!$old) throw new DomainException('Rol no encontrado.');
                if ($old['codigo']==='ROOT') throw new DomainException('El rol ROOT no puede modificarse.');
                if (!(int)$old['activo']) throw new DomainException('Reactiva el rol antes de modificarlo.');
                if (in_array($old['codigo'],['ADMINISTRADOR','VENDEDOR'],true)) {
                    $q=$db->prepare('UPDATE roles SET descripcion=? WHERE id_rol=?');$q->execute([$descripcion,$id]);
                } else {
                    $q=$db->prepare('UPDATE roles SET nombre=?,descripcion=? WHERE id_rol=?');$q->execute([$nombre,$descripcion,$id]);
                }
                $q=$db->prepare('DELETE FROM rol_permisos WHERE id_rol=?');$q->execute([$id]);
            } else {
                $codigo=strtoupper(trim($codigo));
                if (!preg_match('/^[A-Z][A-Z0-9_]{2,29}$/D',$codigo)) throw new DomainException('El código debe tener de 3 a 30 letras, números o guiones bajos.');
                if (in_array($codigo,['ROOT','ADMINISTRADOR','VENDEDOR'],true)) throw new DomainException('Ese código está reservado.');
                // La tabla roles de 4.9.4 tiene PK manual: serializamos la asignación del siguiente ID.
                $ultimo=$db->query('SELECT id_rol FROM roles ORDER BY id_rol DESC LIMIT 1 FOR UPDATE')->fetchColumn();
                $nuevoId=((int)$ultimo)+1;
                $q=$db->prepare('INSERT INTO roles(id_rol,codigo,nombre,descripcion,activo) VALUES(?,?,?,?,1)');
                try {$q->execute([$nuevoId,$codigo,$nombre,$descripcion]);}
                catch(PDOException $e) { if ($e->getCode()==='23000') throw new DomainException('Ya existe un rol con ese código.');throw $e; }
                $id=$nuevoId;
            }
            $q=$db->prepare('INSERT INTO rol_permisos(id_rol,permiso) VALUES(?,?)');
            foreach ($codigos as $p) $q->execute([$id,$p]);
            $db->commit();return $id;
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack();throw $e; }
    }
    public function cambiarEstado(int $id, bool $activo): void
    {
        $db=$this->db();$db->beginTransaction();
        try {
            $q=$db->prepare('SELECT codigo,activo FROM roles WHERE id_rol=? FOR UPDATE');$q->execute([$id]);$r=$q->fetch(PDO::FETCH_ASSOC);
            if (!$r) throw new DomainException('Rol no encontrado.');
            if (in_array($r['codigo'],['ROOT','ADMINISTRADOR','VENDEDOR'],true)) throw new DomainException('No se pueden desactivar los tres roles principales.');
            if (!$activo) {
                $q=$db->prepare('SELECT id FROM usuario WHERE id_rol=? AND estado=1 LIMIT 1 FOR UPDATE');$q->execute([$id]);
                if ($q->fetch()) throw new DomainException('Reasigna los usuarios activos antes de desactivar el rol.');
            }
            $q=$db->prepare('UPDATE roles SET activo=? WHERE id_rol=?');$q->execute([(int)$activo,$id]);
            $db->commit();
        } catch (Throwable $e) {if ($db->inTransaction())$db->rollBack();throw $e;}
    }
}
