<?php
/**
 * IVC 4.9.5.3. Catálogo óptico para POS: solamente lectura/validación.
 * Los materiales y tratamientos son DESCRIPCIONES de la mica comercial,
 * NO recargos independientes. No interpreta ni valida la graduación médica.
 */
final class PosConfiguracionOptica
{
    private static function uno(PDO $db, string $sql, array $args): ?array
    {
        $st = $db->prepare($sql);
        $st->execute($args);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function catalogo(PDO $db): array
    {
        $sql = "SELECT m.id_mica, m.sku, m.nombre_comercial, m.precio_venta,
                       m.unidad_cobro, m.requiere_revision,
                       t.id_tipo_lente, t.nombre AS tipo_lente,
                       COALESCE((SELECT GROUP_CONCAT(DISTINCT mm.nombre ORDER BY mm.nombre SEPARATOR ', ')
                          FROM catalogo_micas_materiales cmm
                          JOIN materiales_mica mm ON mm.id_material=cmm.id_material
                         WHERE cmm.id_mica=m.id_mica), '') AS materiales,
                       COALESCE((SELECT GROUP_CONCAT(DISTINCT tm.nombre ORDER BY tm.nombre SEPARATOR ', ')
                          FROM catalogo_micas_tratamientos cmt
                          JOIN tratamientos_mica tm ON tm.id_tratamiento=cmt.id_tratamiento
                         WHERE cmt.id_mica=m.id_mica), '') AS tratamientos
                  FROM catalogo_micas m
                  JOIN tipos_lente t ON t.id_tipo_lente=m.id_tipo_lente
                 WHERE m.activo=1 AND t.activo=1 AND m.moneda='MXN'
                   AND m.unidad_cobro='PAR'
                 ORDER BY t.id_tipo_lente, m.precio_venta, m.id_mica";
        $q=$db->query($sql);
        return array_map(static function (array $r): array {
            $r['id_mica']=(int)$r['id_mica'];
            $r['id_tipo_lente']=(int)$r['id_tipo_lente'];
            $r['requiere_revision']=(int)$r['requiere_revision'];
            return $r;
        },$q->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function recetas(PDO $db, int $cliente): array
    {
        if ($cliente < 1) throw new InvalidArgumentException('Cliente inválido.');
        if (!self::uno($db,'SELECT id FROM clientes WHERE id=?',[$cliente])) {
            throw new InvalidArgumentException('El cliente ya no existe.');
        }
        $q=$db->prepare("SELECT id_receta,origen,modalidad,estado_revision,fecha_receta,creado_en
                           FROM recetas_opticas
                          WHERE id_cliente=? AND estado_revision IN ('PENDIENTE','APROBADA')
                          ORDER BY id_receta DESC LIMIT 30");
        $q->execute([$cliente]);
        return array_map(static function (array $r): array {
            $r['id_receta']=(int)$r['id_receta'];
            return $r;
        },$q->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Se verifica contra MySQL EN CADA previsualización; jamás confía en precios del navegador. */
    public static function validar(PDO $db, $config, int $cliente, int $cantidad): ?array
    {
        if ($config === null) return null;
        if (!is_array($config) || array_keys($config)!==['id_mica','modo','id_receta']) {
            throw new InvalidArgumentException('Configuración óptica inválida.');
        }
        if ($cantidad !== 1) {
            throw new InvalidArgumentException('Cada línea con micas debe contener exactamente un armazón.');
        }
        if ($cliente < 1) {
            throw new InvalidArgumentException('Selecciona un cliente registrado para asociar micas o recetas.');
        }
        $idMica=$config['id_mica'];
        if (!is_int($idMica) || $idMica < 1) throw new InvalidArgumentException('Mica inválida.');
        $modo=$config['modo'];
        if (!is_string($modo) || !in_array($modo,['RECETA','PENDIENTE'],true)) {
            throw new InvalidArgumentException('Modalidad de receta inválida.');
        }
        $idReceta=$config['id_receta'];
        if ($modo==='PENDIENTE' && $idReceta !== null) {
            throw new InvalidArgumentException('Una receta pendiente no debe tener un ID de receta.');
        }
        if ($modo==='RECETA' && (!is_int($idReceta) || $idReceta < 1)) {
            throw new InvalidArgumentException('Selecciona una receta existente válida.');
        }
        $mica=self::uno($db,"SELECT m.id_mica,m.nombre_comercial,m.sku,m.precio_venta,m.requiere_revision,
                                    t.nombre AS tipo_lente
                               FROM catalogo_micas m JOIN tipos_lente t ON t.id_tipo_lente=m.id_tipo_lente
                              WHERE m.id_mica=? AND m.activo=1 AND t.activo=1
                                AND m.moneda='MXN' AND m.unidad_cobro='PAR'",[$idMica]);
        if (!$mica) throw new DomainException('La mica seleccionada ya no está disponible para venta por par en MXN.');
        $recetaEstado=null;
        if ($modo==='RECETA') {
            $receta=self::uno($db,'SELECT id_receta,estado_revision FROM recetas_opticas WHERE id_receta=? AND id_cliente=?',[$idReceta,$cliente]);
            if (!$receta || !in_array($receta['estado_revision'],['PENDIENTE','APROBADA'],true)) {
                throw new DomainException('La receta ya no pertenece al cliente seleccionado o no puede utilizarse.');
            }
            $recetaEstado=$receta['estado_revision'];
        }
        // 'requiere_revision' se conserva para la futura etapa de revisión profesional.
        return [
            'id_mica'=>(int)$mica['id_mica'],
            'nombre_mica'=>(string)$mica['nombre_comercial'],
            'tipo_lente'=>(string)$mica['tipo_lente'],
            'sku'=>(string)$mica['sku'],
            'precio_mica'=>(string)$mica['precio_venta'],
            'requiere_revision'=>(bool)$mica['requiere_revision'],
            'modo'=>$modo,
            'id_receta'=>$modo==='RECETA'?$idReceta:null,
            'estado_receta'=>$recetaEstado,
            'estado_optico'=>$modo==='PENDIENTE'?'PENDIENTE_RECETA':'PENDIENTE_REVISION',
        ];
    }
}
