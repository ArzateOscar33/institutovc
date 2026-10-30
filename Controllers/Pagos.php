<?php
/** Fase 4.6: captura verificada + pedido idempotente. SOLO PAYPAL SANDBOX. */
require_once 'Config/App/PayPalSandboxClient.php';
require_once 'Config/App/RegistroPedidos.php';
require_once 'Config/App/ReservasInventario.php';
class Pagos extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }
    private function json(bool $ok, string $msg, array $data=[], int $status=200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(array_merge(['ok'=>$ok,'msg'=>$msg,'sandbox'=>true],$data), JSON_UNESCAPED_UNICODE);
        exit;
    }
    private function cliente(): int
    {
        $id=filter_var($_SESSION['idCliente'] ?? null, FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $email=$_SESSION['correoCliente'] ?? null;
        if (!$id || !is_string($email) || !$this->model->clienteVigente((int)$id, $email)) {
            $this->json(false,'Inicia sesión como cliente y verifica tu cuenta.',[],401);
        }
        return (int)$id;
    }
    private function body(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(false,'Solo POST.',[],405);
        if (stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''),'application/json')===false) {
            $this->json(false,'Content-Type debe ser application/json.',[],415);
        }
        $raw=file_get_contents('php://input',false,null,0,8193);
        if (!is_string($raw)||strlen($raw)>8192) $this->json(false,'Solicitud demasiado grande.',[],413);
        $body=json_decode($raw,true);
        if (!is_array($body)||array_keys($body)===range(0,count($body)-1)) $this->json(false,'JSON inválido.',[],400);
        $csrf=$body['csrf'] ?? null;
        $stored=$_SESSION['ivc_cotizaciones_csrf'] ?? null;
        if (!is_string($csrf)||!is_string($stored)||!hash_equals($stored,$csrf)) {
            $this->json(false,'Token de seguridad inválido. Recarga la página.',[],403);
        }
        return $body;
    }
    private function quoteId(array $body): int
    {
        $id=filter_var($body['id_cotizacion']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) $this->json(false,'Cotización inválida.',[],422);
        return (int)$id;
    }
    public function index() { $this->json(true,'Servicio PayPal Sandbox listo; no procesa dinero real.'); }
    public function configuracion()
    {
        $this->cliente();
        if (($_SERVER['REQUEST_METHOD'] ?? '')!=='GET') $this->json(false,'Solo GET.',[],405);
        try {
            $paypal=new PayPalSandboxClient();
            $this->json(true,'Configuración Sandbox.', ['client_id'=>$paypal->browserClientId(),'currency'=>'MXN']);
        } catch (Throwable $e) {
            error_log('IVC Sandbox configuracion: '.$e->getMessage());
            $this->json(false,'PayPal Sandbox no está configurado. Comprueba el archivo privado de credenciales.',[],503);
        }
    }
    public function crear()
    {
        $cliente=$this->cliente();$body=$this->body();$id=$this->quoteId($body);
        try {
            $paypal=new PayPalSandboxClient();
            $reserved=$this->model->reservar($id,$cliente);
            $row=$reserved['order'];
            if (preg_match('/^[A-Z0-9]{8,40}$/D',(string)$row['paypal_order_id'])) {
                $this->json(true,'Orden Sandbox previamente creada.',[
                    'order_id'=>$row['paypal_order_id'], 'total'=>$reserved['quote']['total'],'moneda'=>'MXN',
                    'reserva_expira_en'=>$reserved['reserva_expira_en'],
                ]);
            }
            $remote=$paypal->crearOrden($id,(string)$reserved['quote']['total']);
            $paypalId=$remote['id'] ?? '';
            if (!is_string($paypalId) || !preg_match('/^[A-Z0-9]{8,40}$/D',$paypalId) ||
                !in_array($remote['status']??'', ['CREATED','PAYER_ACTION_REQUIRED','APPROVED'],true)) {
                throw new RuntimeException('PayPal Sandbox devolvió una orden inesperada.');
            }
            $this->model->registrarOrden((int)$row['id'],$paypalId);
            $this->json(true,'Orden creada en PayPal Sandbox.',[
                'order_id'=>$paypalId,'total'=>$reserved['quote']['total'],'moneda'=>'MXN',
                'reserva_expira_en'=>$reserved['reserva_expira_en'],
            ]);
        } catch (DomainException $e) { $this->json(false,$e->getMessage(),[],409);
        } catch (Throwable $e) {
            error_log('IVC Sandbox crear: '.$e->getMessage());
            $this->json(false,'No se pudo crear la orden de prueba. Reintenta sin modificar la cotización.',[],502);
        }
    }
    private function extraerCaptura(array $remote, string $esperado): array
    {
        if (($remote['status']??'')!=='COMPLETED') throw new DomainException('PayPal aún no confirma la captura.');
        $units=$remote['purchase_units']??null;
        if (!is_array($units)||count($units)!==1) throw new DomainException('Respuesta PayPal incompleta.');
        $captures=$units[0]['payments']['captures']??null;
        if (!is_array($captures)||count($captures)!==1) throw new DomainException('No hay una captura única confirmada.');
        $cap=$captures[0];
        $amount=$cap['amount']??[];
        if (($cap['status']??'')!=='COMPLETED'||($amount['currency_code']??'')!=='MXN'||
            ($amount['value']??null)!==$esperado || empty($cap['id'])) {
            throw new DomainException('La captura de PayPal no coincide con el importe en MySQL. Revisión manual necesaria.');
        }
        return ['id'=>(string)$cap['id'],'amount'=>(string)$amount['value']];
    }
    /** Un fallo comercial tras la captura jamás se informa como pago NO realizado. */
    private function terminar(int $idCotizacion, int $cliente, string $captureId, string $monto, bool $repetida): void
    {
        try {
            $servicio=new RegistroPedidos();
            $resultado=$servicio->registrar($idCotizacion,$cliente);
            $this->json(true, $resultado['ya_existia']
                ? 'Pago y pedido ya confirmados. No se descontó inventario de nuevo.'
                : 'Pago Sandbox confirmado. Pedido registrado correctamente.', [
                    'pago_confirmado'=>true, 'id_cotizacion'=>$idCotizacion,
                    'capture_id'=>$captureId, 'total'=>$monto,'moneda'=>'MXN',
                    'pedido_creado'=>true,'id_pedido'=>$resultado['pedido_id'],
                    'ya_registrada'=>$resultado['ya_existia'], 'recuperacion'=>$repetida,
                    'requiere_valoracion'=>$resultado['requiere_valoracion'],
                ]);
        } catch (Throwable $e) {
            // PayPal ya capturó el dinero. La BD conserva la captura para posterior recuperación.
            error_log('IVC 4.6 pedido pendiente cot='.$idCotizacion.': '.$e->getMessage());
            $this->json(true,'Pago CAPTURADO, pero el pedido aún NO pudo registrarse. No realices otro pago. Solicita conciliación o usa Recuperar pedido.',[
                'pago_confirmado'=>true,'id_cotizacion'=>$idCotizacion,'capture_id'=>$captureId,'total'=>$monto,'moneda'=>'MXN',
                'pedido_creado'=>false,'requiere_atencion'=>true,
                'incidencia'=>'REGISTRO_PEDIDO_PENDIENTE',
            ]);
        }
    }

    public function capturar()
    {
        $cliente=$this->cliente();$body=$this->body();$id=$this->quoteId($body);
        $orderId=$body['order_id']??null;
        if (!is_string($orderId)||!preg_match('/^[A-Z0-9]{8,40}$/D',$orderId)) {
            $this->json(false,'ID de orden PayPal inválido.',[],422);
        }
        try {
            $row=$this->model->obtenerCaptura($id,$cliente,$orderId);
            if ($row['estado']==='CAPTURADA') {
                $this->terminar($id,$cliente,(string)$row['paypal_capture_id'],(string)$row['monto_capturado'],true);
            }
            // Estado PROCESANDO se registra ANTES del POST externo. Una petición repetida
            // no puede iniciar una segunda captura. Si hay timeout, se concilia por GET.
            $this->model->iniciarCaptura($id,$cliente,$orderId);
            $paypal=new PayPalSandboxClient();
            try {
                $remote=$paypal->capturar($orderId);
            } catch (Throwable $e) {
                error_log('IVC captura incierta cot='.$id.': '.$e->getMessage());
                // Si el GET falla, PROCESANDO retiene inventario para conciliación.
                $remote=$paypal->verOrden($orderId);
            }
            $capture=$this->extraerCaptura($remote,(string)$row['monto_esperado']);
            $this->model->guardarCaptura((int)$row['id'],$capture['id'],$capture['amount']);
            $this->terminar($id,$cliente,$capture['id'],$capture['amount'],false);
        } catch (DomainException $e) { $this->json(false,$e->getMessage(),[],409);
        } catch (Throwable $e) {
            error_log('IVC Sandbox captura inesperada: '.$e->getMessage());
            $this->json(false,'No se pudo conciliar el estado del cobro. No repitas el pago; usa Recuperar pedido o verifica PayPal Sandbox.',[],502);
        }
    }

    /** Revisa en PayPal SIN crear ni capturar una orden; útil ante timeouts y cierres del navegador. */
    public function recuperar()
    {
        $cliente=$this->cliente();$body=$this->body();$id=$this->quoteId($body);
        try {
            $row=$this->model->obtenerParaConciliar($id,$cliente);
            if ($row['estado']==='CAPTURADA') {
                $this->terminar($id,$cliente,(string)$row['paypal_capture_id'],(string)$row['monto_capturado'],true);
            }
            $paypal=new PayPalSandboxClient();
            $remote=$paypal->verOrden((string)$row['paypal_order_id']);
            if (($remote['status']??'')!=='COMPLETED') {
                throw new DomainException('PayPal no registra una captura completada para esa cotización. No se realizó ningún cargo nuevo.');
            }
            $capture=$this->extraerCaptura($remote,(string)$row['monto_esperado']);
            $this->model->guardarCaptura((int)$row['id'],$capture['id'],$capture['amount']);
            $this->terminar($id,$cliente,$capture['id'],$capture['amount'],true);
        } catch (DomainException $e) {$this->json(false,$e->getMessage(),[],409);
        } catch (Throwable $e) {
            error_log('IVC Sandbox recuperar: '.$e->getMessage());
            $this->json(false,'No se pudo recuperar la captura. No repitas el cobro; solicita revisión manual.',[],502);
        }
    }

    public function pendientes()
    {
        $cliente=$this->cliente();
        if (($_SERVER['REQUEST_METHOD']??'')!=='GET') $this->json(false,'Solo GET.',[],405);
        try {
            $this->json(true,'Capturas sin pedido.', ['capturas'=>$this->model->pendientes($cliente)]);
        } catch (Throwable $e) {
            error_log('IVC Sandbox pendientes: '.$e->getMessage());
            $this->json(false,'No se pudo consultar el historial de conciliación.',[],500);
        }
    }
}
