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
        $data = ['title'=>'Revisión de recetas','csrf'=>$_SESSION['ivc_rx_admin_csrf'],
            'puedeCargar'=>RolesAcceso::permiso('recetas.cargar'),
            'puedeRevisar'=>RolesAcceso::permiso('recetas.administrar')];
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

    /** Buscar clientes en módulo de recetas, con autorización de carga independiente. */
    public function buscarClientes()
    {
        try {
            $q=trim((string)($_GET['q']??''));
            $this->json(true,'Clientes encontrados.',['clientes'=>$this->model->buscarClientesParaCarga($q)]);
        } catch (InvalidArgumentException $e) {
            $this->json(false,$e->getMessage(),[],422);
        } catch(Throwable $e) {
            error_log('IVC buscar clientes receta: '.$e->getMessage());
            $this->json(false,'No fue posible buscar clientes.',[],500);
        }
    }

    /** Micas ya cobradas que no tienen receta y aún no pueden prepararse. */
    public function pendientesCliente($id='')
    {
        $id=filter_var($id,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) $this->json(false,'Selecciona un cliente válido.',[],422);
        try { $this->json(true,'Artículos pendientes.',['detalles'=>$this->model->detallesPendientesCliente((int)$id)]); }
        catch(Throwable $e) {
            error_log('IVC pendientes recetas: '.$e->getMessage());
            $this->json(false,'No se pudieron consultar los artículos pendientes.',[],500);
        }
    }

    /** Alta manual desde archivo recibido por WhatsApp/correo/en mostrador. No aprueba la receta. */
    public function cargar()
    {
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST') $this->json(false,'Método no permitido.',[],405);
        $csrf=$_POST['csrf']??null;
        if (!is_string($csrf)||empty($_SESSION['ivc_rx_admin_csrf'])||
            !hash_equals((string)$_SESSION['ivc_rx_admin_csrf'],$csrf)) {
            $this->json(false,'Sesión vencida. Actualiza el módulo.',[],403);
        }
        $guardado=null;
        try {
            $cliente=filter_var($_POST['id_cliente']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if (!$cliente) throw new InvalidArgumentException('Selecciona el cliente correcto.');
            $idDetalleRaw=trim((string)($_POST['id_detalle']??''));
            $idDetalle=$idDetalleRaw===''?null:filter_var($idDetalleRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if ($idDetalleRaw!=='' && !$idDetalle) throw new InvalidArgumentException('El artículo del pedido es inválido.');
            $fecha=trim((string)($_POST['fecha_receta']??''));
            if ($fecha!=='') {
                $f=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
                if (!$f||$f->format('Y-m-d')!==$fecha||$f>new DateTimeImmutable('today')||$f<new DateTimeImmutable('2000-01-01')) {
                    throw new InvalidArgumentException('La fecha de receta no es válida.');
                }
            }
            $notas=trim((string)($_POST['observaciones']??''));
            if ((function_exists('mb_strlen')?mb_strlen($notas,'UTF-8'):strlen($notas))>1000) {
                throw new InvalidArgumentException('Las observaciones no pueden superar los 1000 caracteres.');
            }
            $archivo=$_FILES['receta_archivo']??null;
            if (!is_array($archivo) || is_array($archivo['name']??null)) throw new InvalidArgumentException('Selecciona un único archivo de receta.');
            if (($archivo['error']??-1)!==UPLOAD_ERR_OK) throw new InvalidArgumentException('No se pudo subir el archivo; comprueba el límite de PHP.');
            $temporal=$archivo['tmp_name']??'';
            if (!is_string($temporal)||!is_uploaded_file($temporal)||
                ($archivo['size']??0)<1||$archivo['size']>10485760) {
                throw new InvalidArgumentException('El archivo debe ser JPG, PNG o PDF y ocupar máximo 10 MB.');
            }
            $tipos=['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($temporal);
            if (!isset($tipos[$mime])) throw new InvalidArgumentException('Formato no admitido; utiliza JPG, PNG o PDF.');
            $ext=strtolower(pathinfo((string)($archivo['name']??''),PATHINFO_EXTENSION));
            if (!in_array($ext,$mime==='image/jpeg'?['jpg','jpeg']:[$tipos[$mime]],true)) {
                throw new InvalidArgumentException('La extensión del archivo no corresponde al contenido.');
            }
            if ($mime!=='application/pdf' && @getimagesize($temporal)===false) {
                throw new InvalidArgumentException('La imagen de receta no es válida.');
            }
            if ($mime==='application/pdf') {
                $handle=fopen($temporal,'rb');
                $firma=$handle?fread($handle,5):'';
                if ($handle) fclose($handle);
                if ($firma!=='%PDF-') throw new InvalidArgumentException('El archivo PDF no es válido.');
            }
            require_once __DIR__.'/Recetas.php';
            $dir=Recetas::directorioPrivado();
            $nombre=bin2hex(random_bytes(20)).'.'.$tipos[$mime];
            $guardado=$dir.'/'.$nombre;
            if (!move_uploaded_file($temporal,$guardado)) throw new RuntimeException('No se pudo guardar el archivo en el almacén privado.');
            @chmod($guardado,0600);
            $nombreOriginal=basename(str_replace('\\','/',(string)$archivo['name']));
            $nombreOriginal=function_exists('mb_substr')?mb_substr($nombreOriginal,0,255,'UTF-8'):substr($nombreOriginal,0,255);
            $datosArchivo=['nombre'=>$nombre,'original'=>$nombreOriginal,'mime'=>$mime,
                'size'=>filesize($guardado),'sha256'=>hash_file('sha256',$guardado)];
            $idReceta=$this->model->cargarDesdeMostrador((int)$cliente,$idDetalle? (int)$idDetalle:null,
                (int)$this->usuario['id'],$fecha?:null,$notas?:null,$datosArchivo);
            $this->json(true,'Receta guardada y pendiente de revisión óptica.',[
                'id_receta'=>$idReceta,'id_detalle'=>$idDetalle,'estado'=>'PENDIENTE']);
        } catch (InvalidArgumentException $e) {
            if ($guardado&&is_file($guardado)) @unlink($guardado);
            $this->json(false,$e->getMessage(),[],422);
        } catch (DomainException $e) {
            if ($guardado&&is_file($guardado)) @unlink($guardado);
            $this->json(false,$e->getMessage(),[],409);
        } catch(Throwable $e) {
            if ($guardado&&is_file($guardado)) @unlink($guardado);
            error_log('IVC cargar receta admin: '.$e->getMessage());
            $this->json(false,'No se pudo guardar. Comprueba el almacenamiento privado y la migración.',[],500);
        }
    }

}
