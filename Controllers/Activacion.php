<?php
/** Portal público para convertir una ficha POS en cuenta web sin duplicar el cliente. */
require_once __DIR__.'/../Config/App/ActivacionClientesPos.php';
class Activacion extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if(session_status()!==PHP_SESSION_ACTIVE)session_start();
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }
    private function csrf(): string
    {
        if(empty($_SESSION['ivc_activacion_csrf']))$_SESSION['ivc_activacion_csrf']=bin2hex(random_bytes(32));
        return $_SESSION['ivc_activacion_csrf'];
    }
    private function post(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Método no permitido.');}
        if(!is_string($_POST['csrf']??null) || !hash_equals($this->csrf(),$_POST['csrf'])){
            http_response_code(403);exit('Sesión caducada. Actualiza el formulario.');
        }
    }
    private function vista(string $estado,string $token='',string $mensaje=''): void
    {
        $data=['title'=>'Activar cuenta web','estado'=>$estado,'token'=>$token,'mensaje'=>$mensaje,'csrf'=>$this->csrf()];
        $this->views->getView('principal','activar-cuenta',$data);
    }
    public function index(): void {$this->vista('inicio');}
    public function solicitar(): void
    {
        $this->post();
        try {
            ActivacionClientesPos::solicitar((string)($_POST['correo']??''),(string)($_POST['codigo']??''));
            $this->vista('enviado');
        } catch(InvalidArgumentException $e) {http_response_code(422);$this->vista('inicio','',$e->getMessage());}
          catch(DomainException $e) {http_response_code(422);$this->vista('inicio','','No fue posible validar el código. Verifica los datos o solicita uno nuevo en mostrador.');}
          catch(Throwable $e) {error_log('[IVC] activar solicitar: '.$e->getMessage());http_response_code(503);$this->vista('inicio','','No fue posible enviar el correo. Inténtalo más tarde.');}
    }
    public function confirmar($token=''): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
        try {
            if(!ActivacionClientesPos::tokenValido((string)$token))throw new DomainException('Enlace inválido o vencido.');
            $this->vista('clave',(string)$token);
        }catch(Throwable $e){http_response_code(410);$this->vista('vencido');}
    }
    public function finalizar(): void
    {
        $this->post();
        $token=(string)($_POST['token']??'');
        try {
            ActivacionClientesPos::finalizar($token,(string)($_POST['clave']??''),(string)($_POST['confirmar']??''));
            header('Location: '.BASE_URL.'activacion/exito',true,303);exit;
        }catch(InvalidArgumentException $e){http_response_code(422);$this->vista('clave',$token,$e->getMessage());}
         catch(DomainException $e){http_response_code(410);$this->vista('vencido');}
         catch(Throwable $e){error_log('[IVC] activar finalizar: '.$e->getMessage());http_response_code(503);$this->vista('vencido');}
    }
    public function exito(): void {$this->vista('exito');}
}
