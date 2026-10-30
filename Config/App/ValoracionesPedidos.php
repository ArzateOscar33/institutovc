<?php
/** IVC 4.8: registro seguro de citas de valoración vinculadas al pedido. */
class ValoracionesPedidos
{
    private PDO $db;
    public function __construct()
    {
        $this->db=(new Conexion())->conect();
        $this->db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    }
    private function uno(string $sql,array $p=[]): ?array
    {
        $s=$this->db->prepare($sql);$s->execute($p);return $s->fetch(PDO::FETCH_ASSOC)?:null;
    }
    /** Solo cliente autenticado; devuelve el pedido cuando requiere valoración. */
    public function pedidoCliente(int $idPedido,int $idCliente): ?array
    {
        if ($idPedido<1 || $idCliente<1) return null;
        return $this->uno("SELECT p.id,c.correo,v.estado FROM pedidos p
          JOIN clientes c ON c.id=p.id_cliente
          JOIN pedido_valoraciones v ON v.id_pedido=p.id
          WHERE p.id=? AND p.id_cliente=? AND p.estado='COMPLETED' AND p.proceso='1'
          AND v.estado IN ('PENDIENTE_AGENDAR','AGENDADA')",[$idPedido,$idCliente]);
    }
    /** La operación de cita vinculada se confirma en una sola transacción. */
    public function registrar(int $idPedido,int $idCliente,array $form): array
    {
        if ($idPedido<1||$idCliente<1) throw new DomainException('Debes iniciar sesión para agendar la valoración de tu pedido.');
        $fecha=trim((string)($form['fechaCita']??''));
        $hora=filter_var($form['selectHorarios']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $f=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
        if (!$f||$f->format('Y-m-d')!==$fecha||$f<new DateTimeImmutable('tomorrow')||$f->format('w')==='0')
            throw new DomainException('Selecciona una fecha futura válida que no sea domingo.');
        if (!$hora) throw new DomainException('Selecciona un horario válido.');
        $nombre=trim((string)($form['iNombre']??''));$paterno=trim((string)($form['iapPaterno']??''));
        $materno=trim((string)($form['iapMaterno']??''));$correo=trim((string)($form['iCorreo']??''));
        $telefono=trim((string)($form['iTelefono']??''));
        if ($nombre===''||$paterno===''||$materno===''||strlen($nombre)>100||strlen($paterno)>50||strlen($materno)>50
          ||!preg_match('/^[0-9]{10}$/D',$telefono)||!filter_var($correo,FILTER_VALIDATE_EMAIL)||strlen($correo)>100)
            throw new DomainException('Completa correctamente tus datos y teléfono de 10 dígitos.');
        $this->db->beginTransaction();
        try {
            $pedido=$this->uno("SELECT p.id,p.proceso,p.estado,c.correo, v.estado AS valoracion
              FROM pedidos p JOIN clientes c ON c.id=p.id_cliente
              JOIN pedido_valoraciones v ON v.id_pedido=p.id
              WHERE p.id=? AND p.id_cliente=? FOR UPDATE",[$idPedido,$idCliente]);
            if (!$pedido || $pedido['estado']!=='COMPLETED'||(string)$pedido['proceso']!=='1'
              ||!in_array($pedido['valoracion'],['PENDIENTE_AGENDAR','AGENDADA'],true))
                throw new DomainException('Este pedido no admite una nueva cita de valoración.');
            if (strcasecmp($correo,trim((string)$pedido['correo']))!==0)
                throw new DomainException('Utiliza el correo registrado en la cuenta que realizó la compra.');
            $vigente=$this->uno('SELECT c.id_cita FROM pedido_citas_valoracion pv JOIN citas c ON c.id_cita=pv.id_cita
               WHERE pv.id_pedido=? AND c.estado=1 LIMIT 1',[$idPedido]);
            if ($vigente) throw new DomainException('El pedido ya tiene una cita activa. Consulta con la óptica si necesitas reagendar.');
            // Bloqueo de horario: peticiones de valoración para una misma hora se serializan.
            $slot=$this->uno('SELECT id_horario,horario,estado FROM horarios WHERE id_horario=? FOR UPDATE',[(int)$hora]);
            if (!$slot || (int)$slot['estado']!==0) throw new DomainException('El horario ya no está disponible.');
            $ocupado=$this->uno('SELECT id_cita FROM citas WHERE fecha=? AND id_hora=? AND estado=1 LIMIT 1',[$fecha,(int)$hora]);
            if ($ocupado) throw new DomainException('Otro cliente reservó este horario. Selecciona uno diferente.');
            $token=bin2hex(random_bytes(24));
            $sql='INSERT INTO citas (apellidoP,apellidoM,nombre,correo,telefono,fecha,id_hora,token) VALUES (?,?,?,?,?,?,?,?)';
            $s=$this->db->prepare($sql);$s->execute([$paterno,$materno,$nombre,$pedido['correo'],$telefono,$fecha,(int)$hora,$token]);
            $idCita=(int)$this->db->lastInsertId();
            $this->db->prepare('INSERT INTO pedido_citas_valoracion(id_pedido,id_cita) VALUES (?,?)')->execute([$idPedido,$idCita]);
            $this->db->prepare("UPDATE pedido_valoraciones SET estado='AGENDADA' WHERE id_pedido=?")->execute([$idPedido]);
            $this->db->commit();
            return ['id_cita'=>$idCita,'token'=>$token,'id_pedido'=>$idPedido];
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
}
