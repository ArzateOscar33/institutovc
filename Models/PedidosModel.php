<?php
/** IVC 4.7 — Consulta y cambios seguros de pedidos del administrador. */
class PedidosModel extends Query
{
    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = (new Conexion())->conect();
        if (!$this->db instanceof PDO) throw new RuntimeException('No se pudo conectar con MySQL.');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function uno(string $sql, array $params = []): ?array
    {
        $s=$this->db->prepare($sql); $s->execute($params);
        return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    private function varios(string $sql, array $params = []): array
    {
        $s=$this->db->prepare($sql); $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function usuarioVigente(string $correo): ?array
    {
        return $this->uno('SELECT id FROM usuario WHERE correo=? AND estado=1 LIMIT 1',[$correo]);
    }

    /** Estado óptico pendiente si alguna mica no fue aprobada O la receta perdió validez. */
    private function pendienteSql(): string
    {
        return "SELECT COUNT(*) FROM detalle_pedidos dx
          INNER JOIN detalle_pedido_micas mx ON mx.id_detalle_pedido=dx.id
          LEFT JOIN recetas_opticas rx ON rx.id_receta=mx.id_receta
          WHERE dx.id_pedido=p.id AND (
             mx.estado_optico<>'APROBADA' OR mx.id_receta IS NULL
             OR rx.estado_revision IS NULL OR rx.estado_revision<>'APROBADA')";
    }

    public function listarAdmin(string $buscar = '', string $proceso = ''): array
    {
        $sql="SELECT p.id,p.fecha,p.nombre,p.apellido,p.email,p.id_cliente,p.monto,p.estado,p.proceso,p.id_transaccion,
            (SELECT COUNT(*) FROM detalle_pedidos d WHERE d.id_pedido=p.id) AS conceptos,
            (SELECT COUNT(*) FROM detalle_pedidos d INNER JOIN detalle_pedido_micas m ON m.id_detalle_pedido=d.id
              WHERE d.id_pedido=p.id) AS micas,
            (".$this->pendienteSql().") AS opticas_pendientes,
            (SELECT v.estado FROM pedido_valoraciones v WHERE v.id_pedido=p.id LIMIT 1) AS valoracion,
            (SELECT c.id_cotizacion FROM checkout_cotizaciones c WHERE c.id_pedido=p.id ORDER BY c.id_cotizacion DESC LIMIT 1) AS id_cotizacion
            FROM pedidos p WHERE 1=1";
        $params=[];
        if ($buscar!=='') {
            $sql.=' AND (CAST(p.id AS CHAR) LIKE ? OR p.id_transaccion LIKE ? OR p.email LIKE ? OR p.nombre LIKE ? OR p.apellido LIKE ?)';
            $needle='%'.$buscar.'%'; $params=array_fill(0,5,$needle);
        }
        if ($proceso!=='') { $sql.=' AND p.proceso=?'; $params[]=$proceso; }
        $sql.=' ORDER BY p.id DESC LIMIT 300';
        return $this->varios($sql,$params);
    }

    public function detalleAdmin(int $id): ?array
    {
        $pedido=$this->uno("SELECT p.*,
            (SELECT c.id_cotizacion FROM checkout_cotizaciones c WHERE c.id_pedido=p.id ORDER BY c.id_cotizacion DESC LIMIT 1) AS id_cotizacion,
            (SELECT c.moneda FROM checkout_cotizaciones c WHERE c.id_pedido=p.id ORDER BY c.id_cotizacion DESC LIMIT 1) AS moneda,
            (SELECT v.estado FROM pedido_valoraciones v WHERE v.id_pedido=p.id) AS estado_valoracion,
            (SELECT v.observaciones FROM pedido_valoraciones v WHERE v.id_pedido=p.id) AS nota_valoracion,
            (".$this->pendienteSql().") AS opticas_pendientes
            FROM pedidos p WHERE p.id=?", [$id]);
        if (!$pedido) return null;
        $pedido['productos']=$this->varios("SELECT d.id,d.producto,d.id_producto,d.precio,d.cantidad,
            m.id_mica,m.id_receta,m.nombre_mica_snapshot,m.precio_unitario_mica,m.estado_optico,
            m.id_usuario_revisor,m.fecha_revision,m.observaciones,
            r.estado_revision AS estado_receta,r.fecha_receta,
            (SELECT COUNT(*) FROM receta_archivos a WHERE a.id_receta=m.id_receta) AS archivos_receta
            FROM detalle_pedidos d
            LEFT JOIN detalle_pedido_micas m ON m.id_detalle_pedido=d.id
            LEFT JOIN recetas_opticas r ON r.id_receta=m.id_receta
            WHERE d.id_pedido=? ORDER BY d.id",[$id]);
        // El estado PayPal pertenece a la cotización vinculada; pedidos antiguos pueden carecer de ella.
        $pedido['paypal']=$pedido['id_cotizacion']!==null ? $this->uno(
            'SELECT estado,paypal_capture_id,monto_capturado,fecha_captura FROM paypal_ordenes WHERE id_cotizacion=? ORDER BY id DESC LIMIT 1',
            [(int)$pedido['id_cotizacion']]) : null;
        $pedido['citas']=$this->varios("SELECT c.id_cita,c.fecha,DATE_FORMAT(h.horario,'%H:%i') AS hora,
              c.verify,c.estado,c.correo,pc.creado_en
              FROM pedido_citas_valoracion pc JOIN citas c ON c.id_cita=pc.id_cita
              JOIN horarios h ON h.id_horario=c.id_hora
              WHERE pc.id_pedido=? ORDER BY c.fecha DESC,c.id_cita DESC",[$id]);
        if ($pedido['estado_valoracion']!==null) {
            $pedido['citas_candidatas']=$this->citasCandidatas($id);
            $pedido['recetas_candidatas']=$this->varios(
                "SELECT r.id_receta,r.origen,r.modalidad,r.estado_revision,r.fecha_receta
                 FROM recetas_opticas r WHERE r.id_cliente=? ORDER BY r.id_receta DESC LIMIT 60",
                [(int)$pedido['id_cliente']]);
        } else {
            $pedido['citas_candidatas']=[];
            $pedido['recetas_candidatas']=[];
        }
        return $pedido;
    }

    /** Solo citas del correo del comprador; excluye citas ya asignadas a otro pedido. */
    private function citasCandidatas(int $idPedido): array
    {
        return $this->varios("SELECT c.id_cita,c.fecha,DATE_FORMAT(h.horario,'%H:%i') AS hora,
                  c.verify,c.estado
              FROM pedidos p JOIN clientes cl ON cl.id=p.id_cliente
              JOIN citas c ON LOWER(TRIM(c.correo))=LOWER(TRIM(cl.correo)) AND c.estado=1
              JOIN horarios h ON h.id_horario=c.id_hora
              WHERE p.id=? AND NOT EXISTS (
                SELECT 1 FROM pedido_citas_valoracion pc WHERE pc.id_cita=c.id_cita AND pc.id_pedido<>p.id
              ) ORDER BY c.fecha DESC,c.id_cita DESC LIMIT 50",[$idPedido]);
    }

    public function vincularCita(int $idPedido,int $idCita): void
    {
        $this->db->beginTransaction();
        try {
            $pedido=$this->uno("SELECT p.id,p.proceso,p.estado,p.id_cliente,c.correo,v.estado AS valoracion
              FROM pedidos p JOIN clientes c ON c.id=p.id_cliente
              JOIN pedido_valoraciones v ON v.id_pedido=p.id
              WHERE p.id=? FOR UPDATE",[$idPedido]);
            if (!$pedido||$pedido['estado']!=='COMPLETED'||(string)$pedido['proceso']!=='1'
                ||!in_array($pedido['valoracion'],['PENDIENTE_AGENDAR','AGENDADA'],true))
                throw new DomainException('El pedido no tiene una valoración pendiente que permita vincular una cita.');
            $cita=$this->uno('SELECT id_cita,correo,estado FROM citas WHERE id_cita=? FOR UPDATE',[$idCita]);
            if (!$cita||(int)$cita['estado']!==1||strcasecmp(trim($cita['correo']),trim($pedido['correo']))!==0)
                throw new DomainException('La cita debe estar activa y pertenecer al correo registrado del comprador.');
            $ajena=$this->uno('SELECT id_pedido FROM pedido_citas_valoracion WHERE id_cita=? AND id_pedido<>? LIMIT 1',[$idCita,$idPedido]);
            if ($ajena) throw new DomainException('Esta cita ya está asociada a otro pedido.');
            $dup=$this->uno('SELECT id_pedido FROM pedido_citas_valoracion WHERE id_pedido=? AND id_cita=?',[$idPedido,$idCita]);
            if (!$dup) $this->db->prepare('INSERT INTO pedido_citas_valoracion(id_pedido,id_cita) VALUES (?,?)')->execute([$idPedido,$idCita]);
            $this->db->prepare("UPDATE pedido_valoraciones SET estado='AGENDADA' WHERE id_pedido=?")->execute([$idPedido]);
            $this->db->commit();
        } catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function marcarValoracionRealizada(int $idPedido,int $idCita): void
    {
        $this->db->beginTransaction();
        try {
            $pedido=$this->uno('SELECT id,proceso,estado FROM pedidos WHERE id=? FOR UPDATE',[$idPedido]);
            if (!$pedido||$pedido['estado']!=='COMPLETED'||(string)$pedido['proceso']!=='1')
                throw new DomainException('Pedido no disponible para valoración.');
            $v=$this->uno('SELECT estado FROM pedido_valoraciones WHERE id_pedido=? FOR UPDATE',[$idPedido]);
            if (!$v||!in_array($v['estado'],['AGENDADA','REALIZADA'],true))
                throw new DomainException('Primero vincula una cita confirmada.');
            $cita=$this->uno("SELECT c.id_cita,c.verify,c.estado,c.fecha,h.horario
                FROM pedido_citas_valoracion pc JOIN citas c ON c.id_cita=pc.id_cita
                JOIN horarios h ON h.id_horario=c.id_hora
                WHERE pc.id_pedido=? AND pc.id_cita=? FOR UPDATE",[$idPedido,$idCita]);
            if (!$cita||(int)$cita['estado']!==1||(int)$cita['verify']!==1)
                throw new DomainException('La cita debe estar activa y confirmada por correo.');
            if (($cita['fecha'].' '.$cita['horario'])>date('Y-m-d H:i:s'))
                throw new DomainException('No puedes marcar como realizada una valoración futura.');
            $this->db->prepare("UPDATE pedido_valoraciones SET estado='REALIZADA' WHERE id_pedido=?")->execute([$idPedido]);
            $this->db->commit();
        } catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    /** Se asocia una receta del comprador; después RecetasAdmin debe aprobarla y Pedidos.aprobarMica validarla. */
    public function asociarReceta(int $idDetalle,int $idReceta): void
    {
        $this->db->beginTransaction();
        try {
            $ref=$this->uno('SELECT id_pedido FROM detalle_pedidos WHERE id=?',[$idDetalle]);
            if (!$ref) throw new DomainException('Detalle inexistente.');
            $pedido=$this->uno('SELECT id,id_cliente,estado,proceso FROM pedidos WHERE id=? FOR UPDATE',[(int)$ref['id_pedido']]);
            if (!$pedido||$pedido['estado']!=='COMPLETED'||(string)$pedido['proceso']!=='1')
                throw new DomainException('Solo puedes asociar recetas a pedidos pagados, antes de preparar.');
            $v=$this->uno('SELECT estado FROM pedido_valoraciones WHERE id_pedido=?',[(int)$pedido['id']]);
            if (!$v||$v['estado']!=='REALIZADA') throw new DomainException('Primero completa la valoración visual.');
            $mica=$this->uno('SELECT id_detalle_pedido,id_receta,estado_optico FROM detalle_pedido_micas WHERE id_detalle_pedido=? FOR UPDATE',[$idDetalle]);
            if (!$mica||$mica['estado_optico']==='APROBADA'||$mica['estado_optico']==='CANCELADA')
                throw new DomainException('Las micas ya están aprobadas, canceladas o no existen.');
            $receta=$this->uno('SELECT id_receta,id_cliente,estado_revision FROM recetas_opticas WHERE id_receta=?',[$idReceta]);
            if (!$receta||(int)$receta['id_cliente']!==(int)$pedido['id_cliente']||$receta['estado_revision']==='RECHAZADA')
                throw new DomainException('La receta no pertenece al comprador o fue rechazada.');
            $this->db->prepare("UPDATE detalle_pedido_micas SET id_receta=?,estado_optico='PENDIENTE_REVISION',
                 id_usuario_revisor=NULL,fecha_revision=NULL,observaciones='Receta asociada tras valoración. Pendiente de revisión óptica.'
                 WHERE id_detalle_pedido=?")->execute([$idReceta,$idDetalle]);
            $this->db->commit();
        } catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    /** Registra receta manual de valoración (INSTITUTO), pero NO la aprueba automáticamente. */
    public function crearRecetaInstituto(int $idDetalle,int $idUsuario,array $datos): int
    {
        if ($idUsuario<1) throw new DomainException('Usuario revisor no válido.');
        $ojos=[];
        foreach (['od'=>'OD','oi'=>'OI'] as $prefix=>$ojo) {
            $a=[];
            foreach (['esfera'=>[-30,30],'cilindro'=>[-15,15],'adicion'=>[0,6]] as $campo=>$limites) {
                $raw=trim((string)($datos[$prefix.'_'.$campo]??''));
                if ($raw===''||!preg_match('/^[+-]?\d{1,2}(?:\.\d{1,2})?$/D',$raw))
                    throw new DomainException('Captura un valor numérico para '.$campo.' del ojo '.$ojo.'.');
                $value=(float)$raw;
                if ($value<$limites[0]||$value>$limites[1]||abs(round($value*4)-$value*4)>0.00001)
                    throw new DomainException($campo.' '.$ojo.' fuera de rango o diferente de pasos de 0.25.');
                $a[$campo]=number_format($value,2,'.','');
            }
            $eje=filter_var($datos[$prefix.'_eje']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>180]]);
            if ($eje===false) throw new DomainException('Eje inválido para ojo '.$ojo.'.');
            $a['eje']=(int)$eje;$ojos[$ojo]=$a;
        }
        $dp=filter_var($datos['dp']??null,FILTER_VALIDATE_FLOAT);
        if ($dp===false||$dp===null||$dp<40||$dp>80) throw new DomainException('La distancia pupilar debe estar entre 40 y 80 mm.');
        $observaciones=trim((string)($datos['observaciones']??''));
        if(strlen($observaciones)>800) throw new DomainException('Observaciones demasiado largas.');
        $this->db->beginTransaction();
        try {
            $ref=$this->uno('SELECT id_pedido FROM detalle_pedidos WHERE id=?',[$idDetalle]);
            if (!$ref) throw new DomainException('Detalle inexistente.');
            $pedido=$this->uno('SELECT id,id_cliente,estado,proceso FROM pedidos WHERE id=? FOR UPDATE',[(int)$ref['id_pedido']]);
            if (!$pedido||$pedido['estado']!=='COMPLETED'||(string)$pedido['proceso']!=='1')
                throw new DomainException('Solo se pueden registrar recetas antes de la preparación.');
            $val=$this->uno('SELECT estado FROM pedido_valoraciones WHERE id_pedido=?',[(int)$pedido['id']]);
            if (!$val||$val['estado']!=='REALIZADA') throw new DomainException('Primero confirma la realización de la valoración.');
            $mica=$this->uno('SELECT estado_optico FROM detalle_pedido_micas WHERE id_detalle_pedido=? FOR UPDATE',[$idDetalle]);
            if (!$mica||in_array($mica['estado_optico'],['APROBADA','CANCELADA'],true))
                throw new DomainException('Las micas no admiten una nueva receta.');
            $nota='Receta capturada en clínica para pedido #'.$pedido['id'].'; usuario administrativo #'.$idUsuario;
            if ($observaciones!=='') $nota.='; '.$observaciones;
            $q=$this->db->prepare("INSERT INTO recetas_opticas
               (id_cliente,origen,modalidad,estado_revision,fecha_receta,distancia_pupilar_total_mm,observaciones_cliente)
               VALUES (?,'INSTITUTO','MANUAL','PENDIENTE',CURDATE(),?,?)");
            $q->execute([(int)$pedido['id_cliente'],number_format((float)$dp,2,'.',''),$nota]);
            $idReceta=(int)$this->db->lastInsertId();
            $q=$this->db->prepare('INSERT INTO receta_graduacion_ojo
               (id_receta,ojo,esfera,cilindro,eje,adicion,dp_monocular_mm) VALUES (?,?,?,?,?,?,NULL)');
            foreach ($ojos as $ojo=>$valores) {
                $q->execute([$idReceta,$ojo,$valores['esfera'],$valores['cilindro'],$valores['eje'],$valores['adicion']]);
            }
            $this->db->prepare("UPDATE detalle_pedido_micas SET id_receta=?,estado_optico='PENDIENTE_REVISION',
               id_usuario_revisor=NULL,fecha_revision=NULL,observaciones='Nueva receta interna; pendiente de aprobación.'
               WHERE id_detalle_pedido=?")->execute([$idReceta,$idDetalle]);
            $this->db->commit();return $idReceta;
        } catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    private function validarAvance(int $id): void
    {
        $noAprobadas=$this->uno("SELECT COUNT(*) AS total FROM detalle_pedidos d
          INNER JOIN detalle_pedido_micas m ON m.id_detalle_pedido=d.id
          LEFT JOIN recetas_opticas r ON r.id_receta=m.id_receta
          WHERE d.id_pedido=? AND (
            m.estado_optico<>'APROBADA' OR m.id_receta IS NULL
            OR r.estado_revision IS NULL OR r.estado_revision<>'APROBADA')",[$id]);
        if ((int)$noAprobadas['total']>0) {
            throw new DomainException('No se puede avanzar: existen micas o recetas pendientes de validación óptica.');
        }
        $valoracion=$this->uno('SELECT estado FROM pedido_valoraciones WHERE id_pedido=?',[$id]);
        if ($valoracion && $valoracion['estado']!=='REALIZADA') {
            throw new DomainException('No se puede avanzar: la valoración visual no está terminada.');
        }
    }

    public function avanzar(int $id): string
    {
        $this->db->beginTransaction();
        try {
            $pedido=$this->uno('SELECT id,proceso,estado FROM pedidos WHERE id=? FOR UPDATE',[$id]);
            if (!$pedido) throw new DomainException('Pedido inexistente.');
            if (strtoupper((string)$pedido['estado'])!=='COMPLETED') {
                throw new DomainException('Solo se puede avanzar un pedido con pago confirmado.');
            }
            $actual=(string)$pedido['proceso'];
            if (!in_array($actual,['1','2'],true)) throw new DomainException('El pedido ya está finalizado.');
            $this->validarAvance($id);
            $nuevo=$actual==='1'?'2':'3';
            $s=$this->db->prepare('UPDATE pedidos SET proceso=? WHERE id=? AND proceso=?');
            $s->execute([$nuevo,$id,$actual]);
            if ($s->rowCount()!==1) throw new DomainException('El pedido cambió durante la operación.');
            $this->db->commit();
            return $nuevo;
        } catch(Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Revisión óptica atómica: el estado global de la receta y el estado de las
     * micas de ESTE artículo se actualizan dentro de una sola transacción.
     * El módulo Recetas mantiene su revisión independiente de recetas sin pedido.
     */
    public function revisarOpticaUnificada(int $idDetalle, int $idUsuario,
        string $accion, string $observaciones = ''): array
    {
        if ($idDetalle < 1 || $idUsuario < 1 || !in_array($accion, ['APROBAR','CORRECCION'], true)) {
            throw new DomainException('Solicitud de revisión inválida.');
        }
        $observaciones = trim($observaciones);
        if ((function_exists('mb_strlen') ? mb_strlen($observaciones, 'UTF-8') : strlen($observaciones)) > 1500) {
            throw new DomainException('Observaciones demasiado largas.');
        }
        if ($accion === 'CORRECCION' && $observaciones === '') {
            throw new DomainException('Indica qué debe corregir el cliente.');
        }
        $this->db->beginTransaction();
        try {
            // Consistentemente se bloquea primero pedidos (igual que avanzar()).
            $ref = $this->uno('SELECT id_pedido FROM detalle_pedidos WHERE id=?', [$idDetalle]);
            if (!$ref) throw new DomainException('El artículo no existe.');
            $pedido = $this->uno('SELECT id,id_cliente,estado,proceso FROM pedidos WHERE id=? FOR UPDATE',
                [(int)$ref['id_pedido']]);
            if (!$pedido || strtoupper((string)$pedido['estado']) !== 'COMPLETED'
                || (string)$pedido['proceso'] !== '1') {
                throw new DomainException('La revisión requiere un pedido pagado y pendiente de preparación.');
            }
            $mica = $this->uno('SELECT id_detalle_pedido,id_receta,estado_optico FROM detalle_pedido_micas
                WHERE id_detalle_pedido=? FOR UPDATE', [$idDetalle]);
            if (!$mica) throw new DomainException('Este artículo no tiene micas configuradas.');
            if (in_array((string)$mica['estado_optico'], ['APROBADA','CANCELADA'], true)) {
                throw new DomainException('Este artículo ya fue validado o está cancelado.');
            }
            if (!$mica['id_receta']) throw new DomainException('Asocia primero una receta al artículo.');
            $receta = $this->uno('SELECT id_receta,id_cliente,estado_revision,modalidad FROM recetas_opticas
                WHERE id_receta=? FOR UPDATE', [(int)$mica['id_receta']]);
            if (!$receta || (int)$receta['id_cliente'] !== (int)$pedido['id_cliente']) {
                throw new DomainException('La receta no pertenece al comprador.');
            }
            $valoracion = $this->uno('SELECT estado FROM pedido_valoraciones WHERE id_pedido=?',
                [(int)$pedido['id']]);
            if ($valoracion && (string)$valoracion['estado'] !== 'REALIZADA') {
                throw new DomainException('La valoración visual debe realizarse antes de validar las micas.');
            }

            if ($accion === 'APROBAR') {
                if (!in_array((string)$receta['estado_revision'], ['PENDIENTE','APROBADA'], true)) {
                    throw new DomainException('La receta requiere corrección o fue rechazada. Asocia una receta nueva.');
                }
                // No se permite aprobar un expediente que carezca de evidencia clínica.
                $evidencia = $this->uno('SELECT
                    (SELECT COUNT(*) FROM receta_archivos WHERE id_receta=?) AS archivos,
                    (SELECT COUNT(*) FROM receta_graduacion_ojo WHERE id_receta=?) AS ojos',
                    [(int)$receta['id_receta'], (int)$receta['id_receta']]);
                $archivos = (int)($evidencia['archivos'] ?? 0);
                $ojos = (int)($evidencia['ojos'] ?? 0);
                if ($receta['modalidad'] === 'ARCHIVO' && $archivos < 1
                    || $receta['modalidad'] === 'MANUAL' && $ojos < 2
                    || $receta['modalidad'] === 'MIXTA' && $archivos < 1 && $ojos < 2) {
                    throw new DomainException('La receta no contiene el archivo o la graduación necesaria para aprobarla.');
                }
                // Solo se revisa globalmente la receta cuando está PENDIENTE.
                // Si ya fue aprobada por otra vía, no reescribir su auditoría.
                if ($receta['estado_revision'] === 'PENDIENTE') {
                    $q = $this->db->prepare("UPDATE recetas_opticas SET estado_revision='APROBADA',
                        observaciones_revision=?,id_usuario_revisor=?,fecha_revision=NOW()
                        WHERE id_receta=? AND estado_revision='PENDIENTE'");
                    $q->execute([$observaciones !== '' ? $observaciones : null,
                        $idUsuario,(int)$receta['id_receta']]);
                    if ($q->rowCount() !== 1) throw new DomainException('El estado de la receta cambió. Recarga el pedido.');
                }
                $q = $this->db->prepare("UPDATE detalle_pedido_micas SET estado_optico='APROBADA',
                    id_usuario_revisor=?,fecha_revision=NOW(),observaciones=?
                    WHERE id_detalle_pedido=? AND estado_optico NOT IN ('APROBADA','CANCELADA')");
                $q->execute([$idUsuario, $observaciones !== '' ? $observaciones : 'Validación óptica unificada desde Pedidos.',
                    $idDetalle]);
                if ($q->rowCount() !== 1) throw new DomainException('Las micas cambiaron durante la revisión.');
                $estadoReceta = 'APROBADA';
                $estadoMica = 'APROBADA';
            } else {
                // Una receta ya aprobada no puede invalidarse con esta acción;
                // podría estar utilizada por otros pedidos, incluso en preparación.
                if ($receta['estado_revision'] !== 'PENDIENTE') {
                    throw new DomainException('Solo se puede solicitar corrección de una receta pendiente.');
                }
                $q = $this->db->prepare("UPDATE recetas_opticas
                    SET estado_revision='REQUIERE_CORRECCION',observaciones_revision=?,
                        id_usuario_revisor=?,fecha_revision=NOW()
                    WHERE id_receta=? AND estado_revision='PENDIENTE'");
                $q->execute([$observaciones,$idUsuario,(int)$receta['id_receta']]);
                if ($q->rowCount() !== 1) throw new DomainException('La receta cambió durante la revisión.');
                $q = $this->db->prepare("UPDATE detalle_pedido_micas
                    SET estado_optico='REQUIERE_CORRECCION',id_usuario_revisor=?,
                        fecha_revision=NOW(),observaciones=?
                    WHERE id_detalle_pedido=? AND estado_optico NOT IN ('APROBADA','CANCELADA')");
                $q->execute([$idUsuario,$observaciones,$idDetalle]);
                if ($q->rowCount() !== 1) throw new DomainException('Las micas cambiaron durante la revisión.');
                $estadoReceta = 'REQUIERE_CORRECCION';
                $estadoMica = 'REQUIERE_CORRECCION';
            }
            $this->db->commit();
            return [
                'id_detalle'=>$idDetalle, 'id_receta'=>(int)$receta['id_receta'],
                'estado_receta'=>$estadoReceta, 'estado_optico'=>$estadoMica,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** Aprobar micas exige receta del propio comprador, ya aprobada por RecetasAdmin. */
    public function aprobarMica(int $idDetalle, int $idUsuario): void
    {
        if ($idUsuario<1) throw new DomainException('Revisor inválido.');
        $this->db->beginTransaction();
        try {
            // Bloquear primero el pedido para serializar con avanzar().
            $ref=$this->uno('SELECT id_pedido FROM detalle_pedidos WHERE id=?',[$idDetalle]);
            if (!$ref) throw new DomainException('Detalle inexistente.');
            $pedido=$this->uno('SELECT id,proceso,estado,id_cliente FROM pedidos WHERE id=? FOR UPDATE',[(int)$ref['id_pedido']]);
            if (!$pedido) throw new DomainException('Pedido inexistente.');
            if ((string)$pedido['proceso']!=='1') throw new DomainException('La validación óptica solo está disponible antes de iniciar preparación.');
            if (strtoupper((string)$pedido['estado'])!=='COMPLETED') throw new DomainException('El pedido todavía no tiene pago confirmado.');
            $row=$this->uno('SELECT m.id_detalle_pedido,m.estado_optico,m.id_receta,r.estado_revision,r.id_cliente
                FROM detalle_pedido_micas m
                LEFT JOIN recetas_opticas r ON r.id_receta=m.id_receta
                WHERE m.id_detalle_pedido=? FOR UPDATE',[$idDetalle]);
            if (!$row) throw new DomainException('Este artículo no tiene micas configuradas.');
            if ($row['estado_optico']==='APROBADA') throw new DomainException('Estas micas ya fueron aprobadas.');
            if (!$row['id_receta'] || $row['estado_revision']!=='APROBADA'
                || (int)$row['id_cliente']!==(int)$pedido['id_cliente']) {
                throw new DomainException('Primero debe aprobarse una receta válida del cliente en Revisar recetas.');
            }
            $valoracion=$this->uno('SELECT estado FROM pedido_valoraciones WHERE id_pedido=?',[(int)$pedido['id']]);
            if ($valoracion && $valoracion['estado']!=='REALIZADA') {
                throw new DomainException('Este pedido todavía requiere una valoración visual.');
            }
            $s=$this->db->prepare("UPDATE detalle_pedido_micas SET estado_optico='APROBADA',
                id_usuario_revisor=?,fecha_revision=NOW(),observaciones=?
                WHERE id_detalle_pedido=? AND estado_optico<>'APROBADA'");
            $s->execute([$idUsuario,'Validación administrativa posterior a receta aprobada.', $idDetalle]);
            if ($s->rowCount()!==1) throw new DomainException('Las micas cambiaron durante la operación.');
            $this->db->commit();
        } catch(Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
