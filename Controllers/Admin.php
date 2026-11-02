<?php
class Admin extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }
    public function index()
    {
        if (RolesAcceso::usuario()) {
            header('Location: '. BASE_URL . RolesAcceso::destinoInicial());
            exit;
        }
        $data['title'] = 'Acceso al sistema';
        $this->views->getView('admin', "login", $data);
    }
    public function validar()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $correo = trim((string)($_POST['email'] ?? ''));
        $clave = (string)($_POST['clave'] ?? '');
        $respuesta = ['msg'=>'No pudimos iniciar sesión. Verifica tus credenciales.', 'icono'=>'warning'];
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
        } elseif (filter_var($correo, FILTER_VALIDATE_EMAIL) && $clave !== '') {
            $data = $this->model->getUsuario($correo);
            if ($data && (int)$data['estado'] === 1 && !empty($data['rol']) && password_verify($clave, $data['clave'])) {
                session_regenerate_id(true);
                $_SESSION['ivc_admin_id'] = (int)$data['id'];
                $_SESSION['email'] = $data['correo'];
                $_SESSION['nombre_usuario'] = $data['nombres'];
                $_SESSION['ivc_admin_rol'] = $data['rol'];
                $_SESSION['ivc_roles_csrf'] = bin2hex(random_bytes(32));
                $respuesta = ['msg'=>'Acceso correcto.', 'icono'=>'success', 'rol'=>$data['rol'], 'destino'=>RolesAcceso::destinoInicial()];
            }
        }
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function home()
    {
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: '. BASE_URL . 'admin');
            exit;
        }
        if (!RolesAcceso::permiso('dashboard')) { header('Location: '.BASE_URL.RolesAcceso::destinoInicial()); exit; }
        $data['title'] = 'Panel Administrativo';
        $data['pendientes'] = $this->model->getTotales(1);
        $data['procesos'] = $this->model->getTotales(2);
        $data['finalizados'] = $this->model->getTotales(3);
        $data['productos'] = $this->model->getProductos();
        $data['dashboard'] = $this->model->resumenDashboard();
        $this->views->getView('admin/administracion', "index", $data);
    }

    public function productosMinimos()
    {
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: '. BASE_URL . 'admin');
            exit;
        }
        $data = $this->model->productosMinimos();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();

    }

    public function topProductos()
    {
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: '. BASE_URL . 'admin');
            exit;
        }
        $data = $this->model->topProductos();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();

    }

    public function salir()
    {
        session_destroy();
        header('Location: ' . BASE_URL);
    }
}
