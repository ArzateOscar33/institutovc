<?php
class RecetasAdmin extends Controller
{
    private $usuario;
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $correo = (string)($_SESSION['email'] ?? '');
        $this->usuario = $correo && !empty($_SESSION['nombre_usuario']) ? $this->model->usuarioVigente($correo) : null;
        if (!$this->usuario) {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                http_response_code(401); header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok'=>false,'msg'=>'No tienes una sesión administrativa activa.']); exit;
            }
            header('Location: '.BASE_URL.'admin'); exit;
        }
    }
    private function json($ok,$message,$extra=[],$http=200)
    {
        http_response_code($http); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
        echo json_encode(array_merge(['ok'=>$ok,'msg'=>$message],$extra), JSON_UNESCAPED_UNICODE); exit;
    }
    public function index()
    {
        if (empty($_SESSION['ivc_rx_admin_csrf'])) $_SESSION['ivc_rx_admin_csrf'] = bin2hex(random_bytes(32));
        $data = ['title'=>'Revisión de recetas','csrf'=>$_SESSION['ivc_rx_admin_csrf']];
        $this->views->getView('admin/recetas','index',$data);
    }
    public function listar()
    {
        try { $this->json(true,'Consulta correcta.',['datos'=>$this->model->listarAdmin()]); }
        catch(Throwable $e) { error_log('IVC recetas listar: '.$e->getMessage()); $this->json(false,'No se pudo cargar el listado.',[],500); }
    }
    public function detalle($id='')
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) $this->json(false,'Identificador incorrecto.',[],422);
        $registro = $this->model->detalle($id);
        if (!$registro) $this->json(false,'Receta no encontrada.',[],404);
        $this->json(true,'Detalle consultado.',['receta'=>$registro]);
    }
    public function revisar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(false,'Método no permitido.',[],405);
        $csrf = $_POST['csrf'] ?? null;
        if (!is_string($csrf) || empty($_SESSION['ivc_rx_admin_csrf']) || !hash_equals($_SESSION['ivc_rx_admin_csrf'], $csrf)) $this->json(false,'Sesión caducada.',[],403);
        $id = filter_var($_POST['id_receta'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $estado = (string)($_POST['estado'] ?? '');
        $nota = trim((string)($_POST['observaciones'] ?? ''));
        if (!$id || !in_array($estado,['APROBADA','REQUIERE_CORRECCION','RECHAZADA'],true)) $this->json(false,'Datos incorrectos.',[],422);
        if ($estado !== 'APROBADA' && $nota === '') $this->json(false,'Explica la corrección o el rechazo.',[],422);
        if ((function_exists('mb_strlen') ? mb_strlen($nota,'UTF-8') : strlen($nota)) > 1500) $this->json(false,'La observación supera los 1,500 caracteres.',[],422);
        try {
            $this->model->revisar($id,$estado,$nota?:null,(int)$this->usuario['id']);
            $this->json(true,'Revisión guardada.',['estado'=>$estado]);
        } catch (InvalidArgumentException $e) {
            $this->json(false,$e->getMessage(),[],404);
        } catch (DomainException $e) {
            $this->json(false,$e->getMessage(),[],409);
        } catch (Throwable $e) {
            error_log('IVC recetas revisar: '.$e->getMessage());
            $this->json(false,'No se pudo registrar la revisión.',[],500);
        }
    }
    /** Entrega privada para visor embebido: misma autenticación que la descarga. */
    public function vistaPrevia($id='')
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) { http_response_code(404); exit; }
        try {
            $registro = $this->model->archivo((int)$id);
            if (!$registro) { http_response_code(404); exit; }
            require_once __DIR__.'/Recetas.php';
            Recetas::enviarArchivo($registro, true);
        } catch (Throwable $e) {
            error_log('IVC vista previa receta: '.$e->getMessage());
            http_response_code(404); exit;
        }
    }

    public function archivo($id='')
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) { http_response_code(404); exit; }
        $registro = $this->model->archivo($id);
        if (!$registro) { http_response_code(404); exit; }
        require_once __DIR__.'/Recetas.php';
        Recetas::enviarArchivo($registro);
    }
}
