<?php
/**
 * Confirmaciones de citas. El historial se registra exclusivamente en cita_correos.
 * Envío SMTP fuera de la transacción; la entrega SMTP es al menos una vez si
 * se interrumpe el proceso después de send() y antes de guardar ENVIADA.
 */
require_once __DIR__.'/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

final class CorreoCitas
{
    private PDO $db;
    private const TIPO = 'CONFIRMACION_CITA';
    private const MAX_INTENTOS = 5;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Conexion())->conect();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function obtener(string $sql, array $args): ?array
    {
        $st=$this->db->prepare($sql);$st->execute($args);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function encolarDesdeToken(string $token): int
    {
        $cita=$this->obtener('SELECT id_cita,verify,estado FROM citas WHERE token=? LIMIT 1',[$token]);
        if (!$cita || (int)$cita['estado']!==1 || (int)$cita['verify']===1) {
            throw new DomainException('La cita no existe o ya fue confirmada.');
        }
        $id=(int)$cita['id_cita'];
        $st=$this->db->prepare("INSERT INTO cita_correos (id_cita,tipo,estado,proximo_intento)
            VALUES (?,'CONFIRMACION_CITA','PENDIENTE',NOW())
            ON DUPLICATE KEY UPDATE id=id");
        $st->execute([$id]);
        return $id;
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    }
    private static function fecha(string $iso): string
    {
        $meses=[1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$iso);
        if (!$d || $d->format('Y-m-d')!==$iso) return $iso;
        return $d->format('j').' de '.$meses[(int)$d->format('n')].' de '.$d->format('Y');
    }
    private function preparar(int $id): array
    {
        $c=$this->obtener("SELECT c.id_cita,c.nombre,c.apellidoP,c.apellidoM,c.correo,c.fecha,
            DATE_FORMAT(h.horario,'%H:%i') AS hora,c.token,c.verify,c.estado
            FROM citas c JOIN horarios h ON h.id_horario=c.id_hora WHERE c.id_cita=?",[$id]);
        if (!$c || (int)$c['estado']!==1 || (int)$c['verify']===1 || !is_string($c['token']) || $c['token']==='') {
            throw new DomainException('La cita ya no admite confirmación.');
        }
        if (!filter_var($c['correo'],FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Correo inválido en la cita.');
        $nombre=trim($c['nombre'].' '.$c['apellidoP'].' '.$c['apellidoM']);
        $fecha=self::fecha($c['fecha']);
        $link=rtrim(BASE_URL,'/').'/principal/verificarCorreoAgenda/'.rawurlencode($c['token']);
        $html='<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width"></head>'
          .'<body style="margin:0;background:#f0f7fb;color:#153c58;font-family:Arial,Helvetica,sans-serif">'
          .'<div style="padding:26px 12px"><div style="max-width:620px;margin:auto;background:#fff;border:1px solid #d9eaf5;border-radius:18px;overflow:hidden">'
          .'<header style="background:#125b89;color:#fff;padding:32px 28px">'
          .'<div style="letter-spacing:2px;font-size:12px;font-weight:700">INSTITUTO VISUAL CALIFORNIA</div>'
          .'<h1 style="font-size:26px;line-height:1.2;margin:14px 0 4px">¡Tu cita está casi lista!</h1>'
          .'<p style="margin:0;color:#dceefb">Solo falta confirmar tu asistencia.</p></header>'
          .'<main style="padding:30px 28px">'
          .'<p>Hola <strong>'.self::esc($nombre).'</strong>,</p>'
          .'<p>Recibimos tu solicitud de cita. Revisa los detalles y confirma tu asistencia desde el botón.</p>'
          .'<div style="background:#eff8fd;border:1px solid #d5eafa;border-radius:13px;padding:17px 20px;margin:24px 0">'
          .'<p style="margin:5px 0"><strong>Folio:</strong> #'.(int)$c['id_cita'].'</p>'
          .'<p style="margin:5px 0"><strong>Fecha:</strong> '.self::esc($fecha).'</p>'
          .'<p style="margin:5px 0"><strong>Hora:</strong> '.self::esc($c['hora']).' h (Tijuana)</p>'
          .'<p style="margin:5px 0"><strong>Estado:</strong> Pendiente de confirmación</p></div>'
          .'<p style="text-align:center;margin:28px 0"><a href="'.self::esc($link).'" style="display:inline-block;background:#167ab5;color:#fff;padding:15px 27px;border-radius:10px;text-decoration:none;font-size:16px;font-weight:700">Confirmar mi cita</a></p>'
          .'<p style="font-size:13px;color:#557185">Si el botón no funciona, copia este enlace en tu navegador:<br><a style="color:#166b9f;overflow-wrap:anywhere" href="'.self::esc($link).'">'.self::esc($link).'</a></p>'
          .'<hr style="border:0;border-top:1px solid #e0edf4;margin:27px 0">'
          .'<p style="margin:4px 0"><strong>Tu visita:</strong> Instituto Visual California, Tijuana, Baja California.</p>'
          .'<p style="font-size:13px;color:#617b8c">Si no solicitaste esta cita, puedes ignorar el mensaje. Evita compartir tu enlace de confirmación.</p>'
          .'</main><footer style="background:#f5fafd;padding:18px 28px;font-size:12px;color:#668298;text-align:center">Gracias por confiar en Instituto Visual California.</footer>'
          .'</div></div></body></html>';
        $plain="Instituto Visual California\nHola {$nombre}\nSolicitaste una cita.\nFolio: #{$id}\nFecha: {$fecha}\nHora: {$c['hora']} h (Tijuana)\nEstado: pendiente de confirmación\nConfirma tu cita: {$link}\n";
        return ['correo'=>$c['correo'],'html'=>$html,'texto'=>$plain,'folio'=>$id];
    }
    public function enviarAhora(int $id): string
    {
        $st=$this->db->prepare("UPDATE cita_correos SET estado='ENVIANDO',intentos=intentos+1,
          bloqueado_hasta=DATE_ADD(NOW(), INTERVAL 2 MINUTE)
          WHERE id_cita=? AND tipo=? AND intentos<? AND (
          (estado IN ('PENDIENTE','ERROR') AND proximo_intento<=NOW())
          OR (estado='ENVIANDO' AND bloqueado_hasta<NOW()))");
        $st->execute([$id,self::TIPO,self::MAX_INTENTOS]);
        $row=$this->obtener('SELECT id,estado,intentos FROM cita_correos WHERE id_cita=? AND tipo=?',[$id,self::TIPO]);
        if (!$row) return 'PENDIENTE';
        if ($st->rowCount()!==1) return $row['estado']==='ENVIADA' ? 'ENVIADA' : 'PENDIENTE';
        $idLog=(int)$row['id'];
        try {
            $c=$this->preparar($id);
            if (!filter_var(USER_SMTP,FILTER_VALIDATE_EMAIL) || PASS_SMTP==='') {
                throw new RuntimeException('SMTP no configurado.');
            }
            $mail=new PHPMailer(true);
            $mail->CharSet='UTF-8';$mail->isSMTP();$mail->SMTPDebug=0;
            $mail->Host=HOST_SMTP;$mail->Port=(int)PUERTO_SMTP;$mail->SMTPAuth=true;
            $mail->Username=USER_SMTP;$mail->Password=PASS_SMTP;
            $mail->SMTPSecure=((int)PUERTO_SMTP===587) ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Timeout=10;$mail->setFrom(USER_SMTP,'Instituto Visual California');
            $mail->addAddress($c['correo']);$mail->isHTML(true);
            $mail->Subject='Confirma tu cita #'.$c['folio'].' | Instituto Visual California';
            $mail->Body=$c['html'];$mail->AltBody=$c['texto'];
            $mail->send();
            $this->db->prepare("UPDATE cita_correos SET estado='ENVIADA',enviado_en=NOW(),
              bloqueado_hasta=NULL,ultimo_error=NULL WHERE id=? AND estado='ENVIANDO'")->execute([$idLog]);
            return 'ENVIADA';
        } catch (Throwable $e) {
            // Nunca exponer credenciales ni diagnóstico SMTP al cliente o a la base de datos.
            error_log('[IVC] Falló envío de cita #'.$id.' (registro privado).');
            $st=$this->db->prepare("UPDATE cita_correos SET
              estado=IF(intentos>=?,'ERROR','PENDIENTE'),
              proximo_intento=DATE_ADD(NOW(),INTERVAL LEAST(60,POW(2,intentos)) MINUTE),
              bloqueado_hasta=NULL,ultimo_error='Error de entrega; consultar log privado'
              WHERE id=? AND estado='ENVIANDO'");
            $st->execute([self::MAX_INTENTOS,$idLog]);
            return 'PENDIENTE';
        }
    }
    public function procesarPendientes(int $max=10): array
    {
        $max=min(30,max(1,$max));
        $st=$this->db->query("SELECT id_cita FROM cita_correos WHERE tipo='CONFIRMACION_CITA'
          AND id_cita IS NOT NULL AND intentos<".self::MAX_INTENTOS."
          AND ((estado IN ('PENDIENTE','ERROR') AND proximo_intento<=NOW())
               OR (estado='ENVIANDO' AND bloqueado_hasta<NOW()))
          ORDER BY id LIMIT ".$max);
        $r=['procesados'=>0,'enviados'=>0];
        foreach($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $r['procesados']++;
            if($this->enviarAhora((int)$id)==='ENVIADA') $r['enviados']++;
        }
        return $r;
    }
}
