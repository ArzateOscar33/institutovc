<?php
class Usuarios extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        // El router verifica permisos contra BD; doble control para rutas del controlador.
        if (!RolesAcceso::permiso('usuarios.administrar')) { http_response_code(403); exit('Acceso restringido.'); }
    }
    private function json(string $mensaje, string $icono='success', int $http=200): void
    {
        http_response_code($http); header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['msg'=>$mensaje,'icono'=>$icono],JSON_UNESCAPED_UNICODE);exit;
    }
    private function postCsrf(): void
    {
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST') $this->json('Método no permitido.','error',405);
        $s=(string)($_SESSION['ivc_roles_csrf']??'');
        $t=$_POST['csrf']??null;
        if (!$s || !is_string($t) || !hash_equals($s,$t)) $this->json('Sesión caducada. Actualiza la página.','error',403);
    }
    public function index()
    {
        if (empty($_SESSION['ivc_roles_csrf'])) $_SESSION['ivc_roles_csrf']=bin2hex(random_bytes(32));
        $data=['title'=>'Usuarios y roles','csrf'=>$_SESSION['ivc_roles_csrf'], 'roles'=>$this->model->rolesDisponibles((string)RolesAcceso::rol())];
        $this->views->getView('admin/usuarios','index',$data);
    }
    public function listar()
    {
        $u=RolesAcceso::usuario();
        $rows=$this->model->getUsuarios(1);
        foreach ($rows as &$row) {
            $protegido=$row['rol']==='ROOT' && $u['rol']!=='ROOT';
            $self=(int)$row['id']===(int)$u['id'];
            $row['perfil']=htmlspecialchars($row['rol_nombre'],ENT_QUOTES,'UTF-8');
            $row['accion']=$protegido ? '<span class="badge bg-secondary">Restringido</span>' :
                '<div class="d-flex gap-1"><button class="btn btn-primary btn-sm" type="button" onclick="editUser('.(int)$row['id'].')" aria-label="Editar"><i class="fas fa-edit"></i></button>'.
                ($self?'':'<button class="btn btn-danger btn-sm" type="button" onclick="eliminarUser('.(int)$row['id'].')" aria-label="Desactivar"><i class="fas fa-user-slash"></i></button>').'</div>';
        }
        unset($row);
        header('Content-Type: application/json; charset=utf-8'); echo json_encode($rows,JSON_UNESCAPED_UNICODE);exit;
    }
    public function edit($id='')
    {
        $id=filter_var($id,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) $this->json('ID incorrecto.','error',422);
        $u=$this->model->getUsuario((int)$id);
        if (!$u) $this->json('Usuario no encontrado.','error',404);
        if ($u['rol']==='ROOT' && RolesAcceso::rol()!=='ROOT') $this->json('Acceso restringido.','error',403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($u, JSON_UNESCAPED_UNICODE);exit;
    }
    public function registrar()
    {
        $this->postCsrf();
        $id=(int)($_POST['id']??0);
        $nombre=trim((string)($_POST['nombre']??''));$ap=trim((string)($_POST['apellido']??''));
        $correo=trim((string)($_POST['correo']??''));$clave=(string)($_POST['clave']??'');
        $rol=(int)($_POST['id_rol']??0);
        if (!$nombre || !$ap || !filter_var($correo,FILTER_VALIDATE_EMAIL) || $rol<1 || strlen($nombre)>100 || strlen($ap)>100 || strlen($correo)>100) $this->json('Completa los campos con datos válidos.','warning',422);
        try {
            $actor=RolesAcceso::usuario();
            $msg=$this->model->guardar((int)$actor['id'],(string)$actor['rol'],$id,$nombre,$ap,$correo,$clave,$rol);
            $this->json($msg);
        } catch (DomainException $e) { $this->json($e->getMessage(),'warning',409); }
        catch (Throwable $e) { error_log('IVC Usuarios guardar: '.$e->getMessage());$this->json('No se pudo guardar el usuario.','error',500); }
    }
    public function delete($id='')
    {
        $this->postCsrf();
        $id=filter_var($id,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) $this->json('ID incorrecto.','warning',422);
        try {
            $actor=RolesAcceso::usuario();
            $this->model->desactivar((int)$actor['id'],(string)$actor['rol'],(int)$id);
            $this->json('Usuario desactivado.');
        } catch (DomainException $e) { $this->json($e->getMessage(),'warning',409); }
        catch (Throwable $e) { error_log('IVC Usuarios desactivar: '.$e->getMessage());$this->json('No se pudo desactivar el usuario.','error',500); }
    }
}
