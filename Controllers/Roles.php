<?php
/** Módulo de roles: solo el propietario ROOT puede usarlo. */
class Roles extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status()!==PHP_SESSION_ACTIVE) session_start();
        if (!RolesAcceso::permiso('roles.gestionar')) {http_response_code(403);exit('Acceso restringido.');}
    }
    private function json(array $datos,int $codigo=200): void
    {
        http_response_code($codigo);header('Cache-Control: no-store');header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos,JSON_UNESCAPED_UNICODE);exit;
    }
    private function verificarPost(): void
    {
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST') $this->json(['ok'=>false,'msg'=>'Método no permitido.'],405);
        $token=(string)($_SESSION['ivc_roles_csrf']??'');$received=$_POST['csrf']??null;
        if (!$token || !is_string($received) || !hash_equals($token,$received)) $this->json(['ok'=>false,'msg'=>'La sesión ha caducado. Actualiza la página.'],403);
    }
    public function index(): void
    {
        if (empty($_SESSION['ivc_roles_csrf'])) $_SESSION['ivc_roles_csrf']=bin2hex(random_bytes(32));
        $data=['title'=>'Roles y permisos','csrf'=>$_SESSION['ivc_roles_csrf'],'catalogo'=>RolesAcceso::catalogo()];
        $this->views->getView('admin/roles','index',$data);
    }
    public function listar(): void { $this->json(['ok'=>true,'datos'=>$this->model->listar()]); }
    public function obtener($id=''): void
    {
        $id=filter_var($id,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) $this->json(['ok'=>false,'msg'=>'Identificador incorrecto.'],422);
        $r=$this->model->obtener((int)$id);
        if (!$r) $this->json(['ok'=>false,'msg'=>'Rol no encontrado.'],404);
        $this->json(['ok'=>true,'rol'=>$r]);
    }
    public function guardar(): void
    {
        $this->verificarPost();
        $id=(int)($_POST['id_rol']??0);
        $nombre=trim((string)($_POST['nombre']??''));
        $codigo=trim((string)($_POST['codigo']??''));
        $descripcion=trim((string)($_POST['descripcion']??''));
        $permisos=$_POST['permisos']??[];
        if (!is_array($permisos) || count($permisos)>40 || $id<0) $this->json(['ok'=>false,'msg'=>'Selección inválida.'],422);
        try {
            $nuevo=$this->model->guardar($id,$codigo,$nombre,$descripcion,$permisos);
            $this->json(['ok'=>true,'msg'=>$id?'Permisos actualizados.':'Rol creado correctamente.','id_rol'=>$nuevo]);
        } catch (DomainException $e) { $this->json(['ok'=>false,'msg'=>$e->getMessage()],422); }
        catch (Throwable $e) {error_log('IVC Roles guardar: '.$e->getMessage());$this->json(['ok'=>false,'msg'=>'No se pudo guardar el rol.'],500);}
    }
    public function estado(): void
    {
        $this->verificarPost();
        $id=(int)($_POST['id_rol']??0); $activo=(string)($_POST['activo']??'');
        if ($id<1 || !in_array($activo,['0','1'],true)) $this->json(['ok'=>false,'msg'=>'Datos incorrectos.'],422);
        try { $this->model->cambiarEstado($id,$activo==='1');$this->json(['ok'=>true,'msg'=>'Estado actualizado.']); }
        catch(DomainException $e){$this->json(['ok'=>false,'msg'=>$e->getMessage()],409);}
        catch(Throwable $e){error_log('IVC Roles estado: '.$e->getMessage());$this->json(['ok'=>false,'msg'=>'No se pudo actualizar el rol.'],500);}
    }
}
