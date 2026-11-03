<?php
/** IVC 4.9.5.3.2: vinculación presencial + verificación de correo.
 * Sin almacenar códigos o tokens legibles; no crea cuentas duplicadas.
 */
final class ActivacionClientesPos
{
    private static function db(): PDO
    {
        $pdo=(new Conexion())->conect();
        if (!$pdo instanceof PDO) throw new RuntimeException('No disponible MySQL.');
        return $pdo;
    }
    private static function ahora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now',new DateTimeZone('America/Tijuana'));
    }
    private static function fecha(DateTimeImmutable $d): string {return $d->format('Y-m-d H:i:s');}
    private static function buscar(PDO $db,string $sql,array $params): ?array
    {
        $q=$db->prepare($sql);$q->execute($params);
        return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }
    private static function epoca(string $fecha): int
    {
        return (new DateTimeImmutable($fecha,new DateTimeZone('America/Tijuana')))->getTimestamp();
    }
    public static function correo(string $correo): string
    {
        $correo=strtolower(trim($correo));
        if (strlen($correo)>100 || !filter_var($correo,FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Introduce un correo electrónico válido.');
        }
        return $correo;
    }
    public static function codigoNormalizado(string $codigo): string
    {
        $codigo=strtoupper(str_replace(['-',' '],'',trim($codigo)));
        if (!preg_match('/\A[0-9A-F]{16}\z/D',$codigo)) {
            throw new InvalidArgumentException('El código debe contener 16 caracteres hexadecimales.');
        }
        return $codigo;
    }
    public static function emitir(int $idCliente,int $idVendedor,string $correo): array
    {
        $correo=self::correo($correo);
        $db=self::db();$db->beginTransaction();
        try {
            $cliente=self::buscar($db,'SELECT id,origen,clave,verify,correo FROM clientes WHERE id=? FOR UPDATE',[$idCliente]);
            if (!$cliente) throw new DomainException('Cliente no encontrado.');
            if ($cliente['origen']!=='POS' || $cliente['clave']!==null || (int)$cliente['verify']===1) {
                throw new DomainException('El cliente ya tiene cuenta web o no pertenece al registro POS.');
            }
            $otro=self::buscar($db,'SELECT id FROM clientes WHERE correo=? AND id<>? LIMIT 1',[$correo,$idCliente]);
            if ($otro) throw new DomainException('El correo ya pertenece a otro cliente. Solicita revisión administrativa.');
            $existente=self::buscar($db,'SELECT emitido_en FROM clientes_activaciones_pos WHERE id_cliente=? FOR UPDATE',[$idCliente]);
            $ahora=self::ahora();
            if ($existente && self::epoca($existente['emitido_en']) > $ahora->getTimestamp()-120) {
                throw new DomainException('Espera dos minutos antes de generar otro código para este cliente.');
            }
            $c= strtoupper(bin2hex(random_bytes(8)));  // 64 bits; código mostrado UNA sola vez
            $h=hash('sha256',$c);
            $expira=self::fecha($ahora->modify('+7 days'));
            $turno=self::buscar($db,"SELECT id_turno FROM pos_turnos WHERE id_usuario=? AND estado='ABIERTO' ORDER BY id_turno DESC LIMIT 1",[$idVendedor]);
            if (!$turno) throw new DomainException('Abre caja antes de emitir el código.');
            $q=$db->prepare('UPDATE clientes SET correo=? WHERE id=? AND clave IS NULL AND origen=\'POS\'');
            $q->execute([$correo,$idCliente]);
            $q=$db->prepare('INSERT INTO clientes_activaciones_pos (id_cliente,correo_destino,codigo_hash,codigo_expira,intentos,token_hash,token_expira,emitido_en,enviado_en,id_usuario_emisor,id_turno_emisor)
                VALUES(?,?,?,?,0,NULL,NULL,?,NULL,?,?)
                ON DUPLICATE KEY UPDATE correo_destino=VALUES(correo_destino),codigo_hash=VALUES(codigo_hash),codigo_expira=VALUES(codigo_expira),intentos=0,token_hash=NULL,token_expira=NULL,emitido_en=VALUES(emitido_en),enviado_en=NULL,id_usuario_emisor=VALUES(id_usuario_emisor),id_turno_emisor=VALUES(id_turno_emisor)');
            $q->execute([$idCliente,$correo,$h,$expira,self::fecha($ahora),$idVendedor,(int)$turno['id_turno']]);
            $db->commit();
            return ['codigo'=>implode('-',str_split($c,4)),'url'=>BASE_URL.'activacion','expira'=>$expira,
                'mensaje'=>'Código emitido. Entrégalo únicamente al comprador verificado.'];
        } catch (Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
    }
    public static function solicitar(string $correo,string $codigo): void
    {
        $correo=self::correo($correo);
        $codigo=self::codigoNormalizado($codigo);
        $db=self::db();$db->beginTransaction();
        try {
            $a=self::buscar($db,'SELECT a.*,c.clave,c.verify,c.origen FROM clientes_activaciones_pos a
                INNER JOIN clientes c ON c.id=a.id_cliente WHERE a.correo_destino=? AND c.correo=? LIMIT 1 FOR UPDATE',[$correo,$correo]);
            $ahora=self::ahora();
            if (!$a) throw new DomainException('No se pudo validar el código. Solicita uno nuevo en la óptica.');
            if ($a['origen']!=='POS' || $a['clave']!==null || (int)$a['verify']===1 ||
                self::epoca($a['codigo_expira']) <= $ahora->getTimestamp() || (int)$a['intentos']>=5) {
                throw new DomainException('No se pudo validar el código. Solicita uno nuevo en la óptica.');
            }
            if ($a['enviado_en'] !== null && self::epoca($a['enviado_en']) > $ahora->getTimestamp()-120) {
                throw new DomainException('Si solicitaste un correo recientemente, revisa tu bandeja y espera dos minutos.');
            }
            if (!hash_equals((string)$a['codigo_hash'],hash('sha256',$codigo))) {
                $db->prepare('UPDATE clientes_activaciones_pos SET intentos=intentos+1 WHERE id_cliente=?')->execute([(int)$a['id_cliente']]);
                $db->commit();
                throw new DomainException('No se pudo validar el código. Solicita uno nuevo en la óptica.');
            }
            $raw=bin2hex(random_bytes(32));$hash=hash('sha256',$raw);
            $q=$db->prepare('UPDATE clientes_activaciones_pos SET token_hash=?,token_expira=?,enviado_en=? WHERE id_cliente=?');
            $q->execute([$hash,self::fecha($ahora->modify('+30 minutes')),self::fecha($ahora),(int)$a['id_cliente']]);
            $db->commit();
            try {self::enviarEmail($correo,$raw);}
            catch(Throwable $e) {
                error_log('[IVC] Envío de activación: '.$e->getMessage());
                // Permitir reintentar, sin mantener un token no entregado.
                $db->prepare('UPDATE clientes_activaciones_pos SET token_hash=NULL,token_expira=NULL,enviado_en=NULL WHERE id_cliente=? AND token_hash=?')
                   ->execute([(int)$a['id_cliente'],$hash]);
                throw new RuntimeException('No fue posible enviar el enlace. Revisa la configuración SMTP.');
            }
        } catch (Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
    }
    private static function enviarEmail(string $correo,string $token): void
    {
        if (!str_starts_with(BASE_URL,'https://') && !preg_match('~\Ahttp://(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?/~i',BASE_URL)) {
            throw new RuntimeException('Se requiere HTTPS para enviar enlaces de activación.');
        }
        require_once 'vendor/autoload.php';
        $link=BASE_URL.'activacion/confirmar/'.$token;
        $mail=new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();$mail->Host=HOST_SMTP;$mail->SMTPAuth=true;
        $mail->Username=USER_SMTP;$mail->Password=PASS_SMTP;
        $mail->Port=(int)PUERTO_SMTP;
        $mail->SMTPSecure=((int)PUERTO_SMTP===465)?\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS:\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet='UTF-8';$mail->setFrom(USER_SMTP,TITLE);$mail->addAddress($correo);
        $mail->isHTML(true);$mail->Subject='Activa tu cuenta - Instituto Visual California';
        $mail->Body='<p>Solicitaste activar tu cuenta web de Instituto Visual California.</p><p><a href="'.htmlspecialchars($link,ENT_QUOTES,'UTF-8').'">Confirmar correo y crear contraseña</a></p><p>El enlace caduca en 30 minutos. Si no lo solicitaste, ignora este mensaje.</p>';
        $mail->AltBody="Activa tu cuenta: $link\nEl enlace caduca en 30 minutos. Si no lo solicitaste, ignóralo.";
        $mail->send();
    }
    public static function tokenValido(string $token): bool
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/D',$token)) return false;
        $db=self::db();$row=self::buscar($db,'SELECT a.token_expira,c.clave,c.verify,c.origen FROM clientes_activaciones_pos a
          INNER JOIN clientes c ON c.id=a.id_cliente WHERE a.token_hash=? LIMIT 1',[hash('sha256',$token)]);
        return (bool)($row && $row['origen']==='POS' && $row['clave']===null && (int)$row['verify']===0 &&
            $row['token_expira']!==null && self::epoca($row['token_expira'])>self::ahora()->getTimestamp());
    }
    public static function finalizar(string $token,string $clave,string $repetida): void
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/D',$token)) throw new DomainException('Enlace inválido o vencido.');
        if (strlen($clave)<12 || strlen($clave)>128 || $clave!==$repetida) {
            throw new InvalidArgumentException('La contraseña debe tener entre 12 y 128 caracteres y coincidir con su confirmación.');
        }
        $db=self::db();$db->beginTransaction();
        try {
            $a=self::buscar($db,'SELECT a.id_cliente,a.token_expira,c.clave,c.verify,c.origen FROM clientes_activaciones_pos a
                INNER JOIN clientes c ON c.id=a.id_cliente WHERE a.token_hash=? FOR UPDATE',[hash('sha256',$token)]);
            if (!$a || $a['origen']!=='POS' || $a['clave']!==null || (int)$a['verify']===1 ||
                $a['token_expira']===null || self::epoca($a['token_expira']) <= self::ahora()->getTimestamp()) {
                throw new DomainException('El enlace es inválido, expiró o ya fue utilizado.');
            }
            $q=$db->prepare('UPDATE clientes SET clave=?,verify=1,token=NULL WHERE id=? AND clave IS NULL AND origen=\'POS\' AND verify=0');
            $q->execute([password_hash($clave,PASSWORD_DEFAULT),(int)$a['id_cliente']]);
            if ($q->rowCount()!==1) throw new DomainException('La cuenta ya fue activada.');
            $db->prepare('DELETE FROM clientes_activaciones_pos WHERE id_cliente=?')->execute([(int)$a['id_cliente']]);
            $db->commit();
        } catch (Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
    }
}
