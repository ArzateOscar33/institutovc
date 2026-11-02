<?php
/**
 * Instituto Visual California — confirmación de compra por correo.
 * Un registro de salida por pedido; SMTP nunca forma parte de la transacción de PayPal.
 * Si el servidor cae después de enviar y antes de registrar ENVIADO, podría llegar
 * otro mensaje en el siguiente intento: el correo no garantiza exactly-once.
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

final class CorreoPedidos
{
    private PDO $db;
    private const TIPO = 'CONFIRMACION_COMPRA';
    private const MAX_INTENTOS = 5;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Conexion())->conect();
        if (!$this->db instanceof PDO) throw new RuntimeException('No se pudo abrir la base de datos.');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    private function una(string $sql, array $args = []): ?array
    {
        $s = $this->db->prepare($sql); $s->execute($args);
        return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    /** Solo una captura pagada con pedido asociado puede programar correo. */
    public function encolar(int $pedidoId): void
    {
        if ($pedidoId <= 0) throw new InvalidArgumentException('Pedido incorrecto.');
        $q = $this->db->prepare("INSERT INTO pedido_correos (id_pedido,tipo,estado,proximo_intento)
            SELECT p.id,?,'PENDIENTE',NOW()
            FROM pedidos p
            JOIN checkout_cotizaciones c ON c.id_pedido=p.id AND c.estado='PAGADA'
            JOIN paypal_ordenes po ON po.id_cotizacion=c.id_cotizacion AND po.estado='CAPTURADA'
                AND po.paypal_capture_id=p.id_transaccion
            WHERE p.id=? AND p.estado='COMPLETED' AND po.monto_capturado=p.monto
            ON DUPLICATE KEY UPDATE id_pedido=id_pedido");
        $q->execute([self::TIPO,$pedidoId]);
        if (!$this->una('SELECT id FROM pedido_correos WHERE id_pedido=? AND tipo=?',[$pedidoId,self::TIPO])) {
            throw new DomainException('No se pudo verificar el pago y pedido para el correo.');
        }
    }
    /**
     * Reclama en MySQL el siguiente intento. El envío SMTP sucede FUERA de la transacción.
     * El estado ENVIANDO caducado permite recuperar un envío interrumpido.
     */
    private function reclamar(int $pedidoId): ?int
    {
        $s = $this->db->prepare("UPDATE pedido_correos SET estado='ENVIANDO',
            intentos=intentos+1,bloqueado_hasta=DATE_ADD(NOW(),INTERVAL 2 MINUTE)
            WHERE id_pedido=? AND tipo=? AND intentos < ? AND (
                (estado IN ('PENDIENTE','ERROR') AND proximo_intento<=NOW())
                OR (estado='ENVIANDO' AND bloqueado_hasta<NOW())
            )");
        $s->execute([$pedidoId,self::TIPO,self::MAX_INTENTOS]);
        if ($s->rowCount()!==1) return null;
        $row = $this->una('SELECT id FROM pedido_correos WHERE id_pedido=? AND tipo=?',[$pedidoId,self::TIPO]);
        return $row ? (int)$row['id'] : null;
    }
    private static function esc($text): string
    {
        return htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    }
    private static function importe($v): string
    {
        return '$'.number_format((float)$v,2,'.',',').' MXN';
    }
    private function preparar(int $pedidoId): array
    {
        $p = $this->una("SELECT p.id,p.monto,p.estado,p.proceso,p.email,p.nombre,p.apellido,
            (SELECT COUNT(*) FROM pedido_valoraciones v WHERE v.id_pedido=p.id
                AND v.estado<>'REALIZADA') AS valoraciones_pendientes
            FROM pedidos p WHERE p.id=?",[$pedidoId]);
        if (!$p || $p['estado']!=='COMPLETED' || !filter_var($p['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Pedido o correo de destino inválido.');
        }
        $sql = "SELECT d.producto,d.cantidad,d.precio,m.nombre_mica_snapshot,
            m.precio_unitario_mica,m.estado_optico FROM detalle_pedidos d
            LEFT JOIN detalle_pedido_micas m ON m.id_detalle_pedido=d.id
            WHERE d.id_pedido=? ORDER BY d.id";
        $q=$this->db->prepare($sql);$q->execute([$pedidoId]);$items=$q->fetchAll(PDO::FETCH_ASSOC);
        if (!$items) throw new RuntimeException('No existen artículos en el pedido.');
        $pendientes = [];
        $filas = '';$lineas=[];
        foreach($items as $item) {
            $cantidad=(int)$item['cantidad'];
            $nombre=(string)$item['producto'];
            $mica=$item['nombre_mica_snapshot']===null ? 'Sin graduación' : (string)$item['nombre_mica_snapshot'];
            $unit=(float)$item['precio'] + (float)($item['precio_unitario_mica'] ?? 0);
            $subtotal=$unit*$cantidad;
            $estado=(string)($item['estado_optico']??'');
            if ($estado==='PENDIENTE_REVISION') $pendientes['revision']=true;
            if ($estado==='PENDIENTE_RECETA') $pendientes['receta']=true;
            if ($estado==='PENDIENTE_VALORACION') $pendientes['valoracion']=true;
            if ($estado==='REQUIERE_CORRECCION') $pendientes['correccion']=true;
            if ($estado==='CANCELADA') $pendientes['cancelada']=true;
            $filas.='<tr><td style="padding:10px 8px;border-bottom:1px solid #e1edf4">'.self::esc($nombre)
              .'<br><small style="color:#5e8195">'.self::esc($mica).'</small></td>'
              .'<td style="padding:10px 8px;text-align:center;border-bottom:1px solid #e1edf4">'.$cantidad.'</td>'
              .'<td style="padding:10px 8px;text-align:right;border-bottom:1px solid #e1edf4">'.self::esc(self::importe($subtotal)).'</td></tr>';
            $lineas[]=$nombre.' | '.$mica.' | '.$cantidad.' | '.self::importe($subtotal);
        }
        $optico = 'Sin validaciones ópticas pendientes';
        if ((int)$p['valoraciones_pendientes']>0 || isset($pendientes['valoracion'])) $optico='Valoración visual pendiente';
        elseif(isset($pendientes['correccion'])) $optico='Receta requiere corrección';
        elseif(isset($pendientes['receta'])) $optico='Receta pendiente de entrega';
        elseif(isset($pendientes['cancelada'])) $optico='Configuración óptica cancelada';
        elseif(isset($pendientes['revision'])) $optico='Receta y micas pendientes de revisión';
        $estadosComerciales = ['1'=>'Recibido','2'=>'En preparación','3'=>'Finalizado'];
        $proceso = $estadosComerciales[(string)$p['proceso']] ?? 'Recibido';
        $nombreCliente=trim((string)$p['nombre']);
        $link=rtrim((string)BASE_URL,'/').'/clientes';
        $citaLink=rtrim((string)BASE_URL,'/').'/principal/agendar?pedido='.(int)$pedidoId;
        $agendar=((int)$p['valoraciones_pendientes']>0 || isset($pendientes['valoracion']));
        $body='<!doctype html><html lang="es"><head><meta charset="UTF-8"></head>'
          .'<body style="margin:0;background:#f2f8fc;font-family:Arial,sans-serif;color:#173f57">'
          .'<div style="max-width:650px;margin:25px auto;background:#fff;border:1px solid #d9eaf5;border-radius:14px;overflow:hidden">'
          .'<div style="background:#166b9f;color:#fff;padding:26px 30px"><div style="font-size:13px;letter-spacing:2px">INSTITUTO VISUAL CALIFORNIA</div>'
          .'<h1 style="font-size:26px;margin:12px 0 2px">¡Gracias por tu compra!</h1></div>'
          .'<div style="padding:26px 30px"><p>Hola '.self::esc($nombreCliente).',</p>'
          .'<p>Recibimos tu pago y registramos tu pedido <strong>#'.(int)$p['id'].'</strong>.</p>'
          .'<div style="background:#edf7fc;padding:16px;border-radius:10px;margin:20px 0">'
          .'<p style="margin:4px 0"><strong>Pago:</strong> Confirmado en entorno de pruebas</p>'
          .'<p style="margin:4px 0"><strong>Estado del pedido:</strong> '.self::esc($proceso).'</p>'
          .'<p style="margin:4px 0"><strong>Estado de tus lentes:</strong> '.self::esc($optico).'</p></div>'
          .'<table style="border-collapse:collapse;width:100%;font-size:14px"><thead><tr style="background:#edf7fc">'
          .'<th align="left" style="padding:10px">Producto</th><th>Cant.</th><th align="right" style="padding:10px">Subtotal</th></tr></thead><tbody>'
          .$filas.'</tbody></table>'
          .'<p style="text-align:right;font-size:20px;color:#135c89"><strong>Total pagado: '.self::esc(self::importe($p['monto'])).'</strong></p>'
          .'<p>Los lentes graduados se prepararán una vez que la óptica revise la receta o se realice la valoración correspondiente.</p>'
          .'<p><a href="'.self::esc($link).'" style="background:#166b9f;color:white;text-decoration:none;padding:12px 20px;border-radius:8px;display:inline-block">Consultar mi pedido</a></p>'
          .'<p style="font-size:12px;color:#6a8290">Transacción de prueba (PayPal Sandbox): no representa un cargo bancario real. Este correo no es una aprobación óptica. No respondas con datos médicos sensibles.</p></div></div></body></html>';
        $plain="Instituto Visual California\nCompra confirmada: pedido #".$p['id']."\n".
          "Pago: confirmado en entorno de pruebas\nEstado del pedido: ".$proceso."\nEstado de tus lentes: ".$optico."\n\n".
          implode("\n",$lineas)."\n\nTotal pagado: ".self::importe($p['monto'])."\n".
          "Consulta tu pedido: ".$link.($agendar ? "\nAgenda tu valoración: ".$citaLink : "");
        return ['pedido'=>$p, 'html'=>$body,'text'=>$plain];
    }
    /** Devuelve ENVIADO, PENDIENTE o ERROR, sin lanzar errores SMTP al comprador. */
    public function procesarPedido(int $pedidoId): string
    {
        $claim = $this->reclamar($pedidoId);
        if ($claim === null) {
            $row = $this->una('SELECT estado FROM pedido_correos WHERE id_pedido=? AND tipo=?',[$pedidoId,self::TIPO]);
            return $row && $row['estado']==='ENVIADA' ? 'ENVIADO' : 'PENDIENTE';
        }
        try {
            $contenido=$this->preparar($pedidoId);
            if (!filter_var(USER_SMTP,FILTER_VALIDATE_EMAIL) || !defined('PASS_SMTP') || PASS_SMTP==='') {
                throw new RuntimeException('Configura las credenciales privadas de correo.');
            }
            $mail=new PHPMailer(true);
            $mail->CharSet='UTF-8';
            $mail->isSMTP();$mail->SMTPDebug=0;
            $mail->Host=HOST_SMTP;$mail->Port=(int)PUERTO_SMTP;
            $mail->SMTPAuth=true;
            $mail->Username=USER_SMTP;$mail->Password=PASS_SMTP;
            $mail->SMTPSecure=((int)PUERTO_SMTP===587)
                ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Timeout=8;
            $mail->setFrom(USER_SMTP,'Instituto Visual California');
            $mail->addAddress($contenido['pedido']['email']);
            $mail->isHTML(true);
            $mail->Subject='Tu compra #'.$pedidoId.' está confirmada | Instituto Visual California';
            $mail->Body=$contenido['html'];$mail->AltBody=$contenido['text'];
            $mail->send();
            $this->db->prepare("UPDATE pedido_correos SET estado='ENVIADA',enviado_en=NOW(),
                bloqueado_hasta=NULL,ultimo_error=NULL WHERE id=? AND estado='ENVIANDO'")->execute([$claim]);
            return 'ENVIADO';
        } catch (Throwable $e) {
            // No guardar el error completo: SMTP puede incluir datos privados del servidor.
            error_log('[IVC] Error SMTP pedido #'.$pedidoId.'; intentar luego.');
            $s=$this->db->prepare("UPDATE pedido_correos
                SET estado=IF(intentos>=?,'ERROR','PENDIENTE'),
                proximo_intento=DATE_ADD(NOW(),INTERVAL LEAST(60,POW(2,intentos)) MINUTE),
                bloqueado_hasta=NULL,ultimo_error='Fallo de envío; revisar log privado'
                WHERE id=? AND estado='ENVIANDO'");
            $s->execute([self::MAX_INTENTOS,$claim]);
            return 'PENDIENTE';
        }
    }
    public function procesarPendientes(int $limite=10): array
    {
        $limite=min(20,max(1,$limite));
        $q=$this->db->query("SELECT id_pedido FROM pedido_correos WHERE tipo='CONFIRMACION_COMPRA' AND id_pedido IS NOT NULL AND intentos < ".self::MAX_INTENTOS."
            AND ((estado IN ('PENDIENTE','ERROR') AND proximo_intento<=NOW())
              OR (estado='ENVIANDO' AND bloqueado_hasta<NOW()))
            ORDER BY id LIMIT ".$limite);
        $ids=$q->fetchAll(PDO::FETCH_COLUMN);
        $result=['procesados'=>0,'enviados'=>0];
        foreach ($ids as $id) {
            $result['procesados']++;
            if ($this->procesarPedido((int)$id)==='ENVIADO') $result['enviados']++;
        }
        return $result;
    }
}
