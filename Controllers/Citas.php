<?php
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
//Load Composer's autoloader
require 'vendor/autoload.php';
class Citas extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }
    public function index()
    {
        $data['title'] = 'citas';

        $this->views->getView('admin/citas', "index", $data);
    }
    // Método para registrar una cita
    public function registrarCita()
    {
        if (isset($_POST['fechaNuevaCita'], $_POST['selectHorarios'])) {
            if (empty($_POST['fechaNuevaCita']) || empty($_POST['selectHorarios']) || empty($_POST['iapPaterno']) || empty($_POST['iapMaterno']) || empty($_POST['iCorreo']) || empty($_POST['iTelefono']) || empty($_POST['iNombre'])) {
                $mensaje = array('msg' => 'TODOS LOS CAMPOS SON REQUERIDOS DESDE REGISTRAR', 'icono' => 'warning');
            } else {
                $fecha = $_POST['fechaNuevaCita'];
                $hora = $_POST['selectHorarios'];
                $apellidoP = $_POST['iapPaterno'];
                $apellidoM = $_POST['iapMaterno'];
                $nombre = $_POST['iNombre'];
                $correo = $_POST['iCorreo'];
                $telefono = $_POST['iTelefono'];
                $token = bin2hex(random_bytes(24));

                $resultado = $this->model->registrarCita($apellidoP, $apellidoM, $nombre, $correo, $telefono, $fecha, $hora, $token);
                if ($resultado > 0) {
                    $mensaje = array('msg' => 'Cita registrada con éxito', 'icono' => 'success', 'token' => $token);
                } else {
                    $mensaje = array('msg' => 'Error al registrar la cita', 'icono' => 'error');
                }
            }
            echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
            die();
        } else {
            echo json_encode(['msg' => 'Solicitud inválida admin', 'icono' => 'error'], JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    /** Envío de la cita desde los datos persistidos, con bitácora idempotente. */
    public function enviarCorreoAgenda()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['icono'=>'error','msg'=>'Solicitud no permitida.']);
            exit;
        }
        try {
            $token = $_POST['token'] ?? '';
            if (!is_string($token) || !preg_match('/^[a-f0-9]{32,100}$/iD', $token)) {
                throw new DomainException('Enlace de confirmación inválido.');
            }
            require_once 'Config/App/CorreoCitas.php';
            $correo = new CorreoCitas();
            $idCita = $correo->encolarDesdeToken($token);
            $estado = $correo->enviarAhora($idCita);
            echo json_encode($estado === 'ENVIADA'
                ? ['icono'=>'success','msg'=>'Te enviamos el enlace para confirmar tu cita.','correo_enviado'=>true]
                : ['icono'=>'warning','msg'=>'La cita está registrada, pero el correo está pendiente de envío. Puedes reintentarlo sin generar otra cita.','correo_enviado'=>false], JSON_UNESCAPED_UNICODE);
        } catch (DomainException $e) {
            echo json_encode(['icono'=>'warning','msg'=>$e->getMessage(),'correo_enviado'=>false],JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('[IVC] No se pudo procesar el correo de cita.');
            http_response_code(500);
            echo json_encode(['icono'=>'error','msg'=>'Tu cita sigue registrada. No pudimos verificar el correo; contacta a la óptica si necesitas ayuda.','correo_enviado'=>false],JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function verificarCorreoAgenda($token)
    {
        $verificar = $this->model->getToken($token);
        if (!empty($verificar)) {
            $this->model->actualizarVerify($verificar['id_cita']);
            // Establece un mensaje en la sesión para mostrar la alerta
            $_SESSION['alerta_confirmacion'] = "¡Cita confirmada con éxito!";
            header('Location: ' . BASE_URL);
        } else {
            // Mensaje de error si no se encuentra el token
            $_SESSION['alerta_error'] = "No se pudo confirmar la cita. Token inválido.";
            header('Location: ' . BASE_URL);
        }
    }

    //obtener  horarios disponibles para una fecha

    // Método para obtener horarios disponibles
    public function getHorariosDisponibles()
    {
        if (isset($_POST['fechaNuevaCita'])) {
            $fechaNuevaCita = $_POST['fechaNuevaCita'];
            $horarios = $this->model->obtenerHorariosDisponibles($fechaNuevaCita);
            echo json_encode($horarios, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('msg' => 'Fecha no proporcionada', 'icono' => 'error'), JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function listar()
    {
        $data = $this->model->listarCitas();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function eliminarCita($id_cita)
    {
        $data = $this->model->eliminarCita($id_cita);
        if ($data == 1) {
            $mensaje = array('msg' => 'CITA ELIMINADA', 'icono' => 'success');
        } else {
            $mensaje = array('msg' => 'Error al eliminar cita', 'icono' => 'warning');
        }
        echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
        die();
    }
}
