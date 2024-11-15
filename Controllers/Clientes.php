<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
//Load Composer's autoloader
require 'vendor/autoload.php';
class Clientes extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
    }
    public function index()
    {
        $data['title'] = 'Tu Perfil';
        $this->views->getView('principal', "perfil", $data);
    }
    public function registroDirecto()
    {
        if (isset($_POST['nombre']) && isset($_POST['clave'])) {
            $apellidoP = $_POST['apellidoP'];
            $apellidoM = $_POST['apellidoM'];
            $nombre = $_POST['nombre'];
            $correo = $_POST['correo'];
            $clave = $_POST['clave'];
            $token = md5($correo);
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $data = $this->model->registroDirecto($apellidoP, $apellidoM, $nombre, $correo, $hash, $token);


            if ($data > 0) {
                $mensaje = array('msg' => 'registrado con exito', 'icono' => 'success', 'token' => $token);
            } else {

                $mensaje = array('msg' => 'Error al registrarse', 'icono' => 'error');
            }
            echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
            die();
        } //if
    }
    public function enviarCorreo()
    {
        if (isset($_POST['correo']) && isset($_POST['token'])) {
            //Create an instance; passing `true` enables exceptions
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
                $mail->isSMTP();                                            //Send using SMTP
                $mail->Host = HOST_SMTP;                     //Set the SMTP server to send through
                $mail->SMTPAuth = true;                                   //Enable SMTP authentication
                $mail->Username = USER_SMTP;                     //SMTP username
                $mail->Password = PASS_SMTP;                               //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                $mail->Port = PUERTO_SMTP;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                //Recipients
                $mail->setFrom('arzateoscar33@gmail.com', TITLE);
                $mail->addAddress($_POST['correo']);     //Add a recipient


                //Attachments
                //$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
                // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

                //Content
                $mail->isHTML(true);                                  //Set email format to HTML
                $mail->Subject = 'Mensaje desde : ' . TITLE;
                $mail->Body = 'Para verificar tu correo en nuestra optica <a href="' . BASE_URL . 'clientes/verificarCorreo/' . $_POST['token'] . '">CLIC AQUI</a>';
                $mail->AltBody = 'GRACIAS POR LA PREFERENCIA ';

                $mail->send();
                $mensaje = array('msg' => 'CORREO ENVIADO,REVISAR SU BANDEJA DE ENTRADA - SPAN', 'icono' => 'success');
            } catch (Exception $e) {
                $mensaje = array('msg' => 'Error al enviar Correo'.$mail->ErrorInfo, 'icono' => 'error');
            }
        } else {
            $mensaje = array('msg' => 'ERROR FATAL', 'icono' => 'error');
        }
        echo json_encode($mensaje,JSON_UNESCAPED_UNICODE);
        die();
    }




    public function verificarCorreo($token)
    {
        print_r($token);
    }
}//class