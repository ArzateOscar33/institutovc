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
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: '. BASE_URL . 'admin');
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
                 $token = md5($fecha . '-' . $hora);
 
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
 
     public function enviarCorreoAgenda()
     {
         if (isset($_POST['iCorreo']) && isset($_POST['token'])) {
             //Create an instance; passing `true` enables exceptions
             $mail = new PHPMailer(true);
 
             try {
                 //Server settings
                 $mail->SMTPDebug = 0;                      //Enable verbose debug output
                 $mail->isSMTP();                                            //Send using SMTP
                 $mail->Host = HOST_SMTP;                     //Set the SMTP server to send through
                 $mail->SMTPAuth = true;                                   //Enable SMTP authentication
                 $mail->Username = USER_SMTP;                     //SMTP username
                 $mail->Password = PASS_SMTP;                               //SMTP password
                 $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                 $mail->Port = PUERTO_SMTP;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
 
                 //Recipients
                 $mail->setFrom('arzateoscar33@gmail.com', TITLE);
                 $mail->addAddress($_POST['iCorreo']);     //Add a recipient
 
 
                 //Attachments
                 //$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
                 // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
 
                 //Content
                 $mail->isHTML(true); 
                 // Aquí construimos el mensaje dinámico
                 $mensaje_cuerpo = "Estimado/a " .  ",<br><br>";
                 $mensaje_cuerpo .= "Se ha registrado tu cita con los siguientes datos:<br>";
                 $mensaje_cuerpo .= "Fecha: " .  "<br>";
                 $mensaje_cuerpo .= "Hora: " .  "<br><br>";
                 $mensaje_cuerpo .= "Para confirmar tu cita, por favor haz clic en el siguiente enlace:<br>";
                 $mensaje_cuerpo .= "<a href='" . BASE_URL . "principal/verificarCorreoAgenda/" . $_POST['token'] . "'>CLIC AQUI</a>";
 
                 //Set email format to HTML
                 $mail->Subject = 'Mensaje desde : ' . TITLE . ' CONFIRMA TU CITA ';
                 $mail->Body = $mensaje_cuerpo;
                 $mail->AltBody = 'GRACIAS POR LA PREFERENCIA ';
 
                 $mail->send();
                 $mensaje = array('msg' => 'CORREO ENVIADO,CONFIRME SU CITA,REVISAR SU BANDEJA DE ENTRADA - SPAN', 'icono' => 'success');
             } catch (Exception $e) {
                 $mensaje = array('msg' => 'Error al enviar Correo: ' . $mail->ErrorInfo, 'icono' => 'error');
             }
         } else {
             $mensaje = array('msg' => 'ERROR FATAL', 'icono' => 'error');
         }
         echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
         die();
     }
     public function verificarCorreoAgenda($token)
     {
         $verificar = $this->model->getToken($token);
         if (!empty($verificar)) {
             $data = $this->model->actualizarVerify($verificar['id_cita']);
             print_r($data);
             header('Location: ' . BASE_URL);
         } //if
     }
 
     //obtener  horarios disponibles para una fecha
 
     // Método para obtener horarios disponibles
     public function getHorariosDisponibles() {
         if (isset($_POST['fechaNuevaCita'])) {
             $fechaNuevaCita = $_POST['fechaNuevaCita'];
             $horarios = $this->model->obtenerHorariosDisponibles($fechaNuevaCita);
             echo json_encode($horarios, JSON_UNESCAPED_UNICODE);
         } else {
             echo json_encode(array('msg' => 'Fecha no proporcionada', 'icono' => 'error'), JSON_UNESCAPED_UNICODE);
         }
         die();
     }




   
}