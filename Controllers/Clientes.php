<?php
// SDK de Mercado Pago
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

require_once 'vendor/autoload.php';

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
      //session_destroy();
    }
    public function index()
    {
        if (empty($_SESSION['correoCliente'])) {
            header('Location: ' . BASE_URL);
        }
        $data['perfil']='si';
        $data['title'] = 'Tu Perfil';
        $data['verificar'] = $this->model->getVerificar($_SESSION['correoCliente']);
    
        $this->views->getView('principal', "perfil", $data);
    }
    public function registroDirecto()
    {
        if (isset($_POST['nombre']) && isset($_POST['clave'])) {
            if (empty($_POST['nombre']) || empty($_POST['apellidoP']) || empty($_POST['apellidoM']) || empty($_POST['correo']) || empty($_POST['clave'])) {
                $mensaje = array('msg' => 'TODOS LOS CAMPOS SON REQUERIDOS', 'icono' => 'warning');
            } else {
                
                $apellidoP = $_POST['apellidoP'];
                $apellidoM = $_POST['apellidoM'];
                $nombre = $_POST['nombre'];
                $correo = $_POST['correo'];
                $clave = $_POST['clave'];
                $verificar = $this->model->getVerificar($correo);
                if (empty($verificar)) {
                    $token = md5($correo);
                    $hash = password_hash($clave, PASSWORD_DEFAULT);
                    $data = $this->model->registroDirecto($apellidoP, $apellidoM, $nombre, $correo, $hash, $token);


                    if ($data > 0) {
                        $_SESSION['idCliente'] = $data;
                        $_SESSION['correoCliente'] = $correo;
                        $_SESSION['nombreCliente'] = $nombre;
                        $_SESSION['apellidoPCliente'] = $apellidoP;

                        $mensaje = array('msg' => 'registrado con exito', 'icono' => 'success', 'token' => $token);
                    } else {

                        $mensaje = array('msg' => 'Error al registrarse', 'icono' => 'error');
                    }
                } else {
                    $mensaje = array('msg' => 'YA TIENES UNA CUENTA ', 'icono' => 'warning');
                }
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
                $mensaje = array('msg' => 'Error al enviar Correo: ' . $mail->ErrorInfo, 'icono' => 'error');
            }
        } else {
            $mensaje = array('msg' => 'ERROR FATAL', 'icono' => 'error');
        }
        echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
        die();
    }

    //login directo
    public function loginDirecto()
    {
        if (isset($_POST['correoLogin']) && isset($_POST['claveLogin'])) {
            if (empty($_POST['correoLogin']) || empty($_POST['claveLogin'])) {
                $mensaje = array('msg' => 'TODOS LOS CAMPOS SON REQUERIDOS', 'icono' => 'warning');
            } else {
                $correo = $_POST['correoLogin'];
                $clave = $_POST['claveLogin'];
                $verificar = $this->model->getVerificar($correo);
                if (!empty($verificar)) {
                    if (is_string($verificar['clave'] ?? null) && $verificar['clave'] !== '' && password_verify($clave, $verificar['clave'])) {
                        session_regenerate_id(true);
                        $_SESSION['idCliente'] = $verificar['id'];
                        $_SESSION['correoCliente'] = $verificar['correo'];
                        $_SESSION['nombreCliente'] = $verificar['nombre'];
                        $_SESSION['apellidoPCliente'] = $verificar['apellidoP'];

                        $mensaje = array('msg' => 'Ok', 'icono' => 'success');
                    } else {

                        $mensaje = array('msg' => 'CONTRASEÑA INCORRECTA', 'icono' => 'error');
                    }
                } else {
                    $mensaje = array('msg' => 'EL CORREO NO EXISTE ', 'icono' => 'warning');
                }
            }

            echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
            die();
        } //if
    }


    public function verificarCorreo($token)
    {
        $verificar = $this->model->getToken($token);
        if (!empty($verificar)) {
            $data = $this->model->actualizarVerify($verificar['id']);

            header('Location: ' . BASE_URL . 'clientes');
        } //if
    }

//registrar pedidos 
/**
 * Registro heredado deshabilitado: los datos de PayPal enviados por JS no demuestran pago.
 * Todas las ventas (armazones con/sin micas) deben usar cotizaciones + pagos/capturar.
 */
public function registrarPedido(){
    http_response_code(410);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['msg'=>'Checkout anterior deshabilitado. Usa la cotización PHP y PayPal Sandbox.',
                      'icono'=>'error','ok'=>false],JSON_UNESCAPED_UNICODE);
    exit;
}
//lsitar productos pendientes
public function listarPendientes(){
    $id_cliente = $_SESSION['idCliente'];
    $data=$this->model->getPedidos($id_cliente);
    for ($i=0; $i < count($data); $i++) { 
        $data[$i]['accion']='<div class="text-center"><button class="btn btn-primary" type="button" onClick="verPedido('.$data[$i]['id'].')"><i class="fas fa-eye"></i></button></div>';
    }
    echo json_encode($data);
    die();
}
public function verPedido($idPedido){
    header('Content-Type: application/json; charset=utf-8');
    $idCliente=filter_var($_SESSION['idCliente']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    $id=filter_var($idPedido,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if (!$idCliente || !$id){http_response_code(401);echo json_encode(['error'=>'No autorizado']);exit;}
    $pedido=$this->model->getPedido((int)$id,(int)$idCliente);
    if (!$pedido){http_response_code(404);echo json_encode(['error'=>'Pedido no encontrado']);exit;}
    echo json_encode(['pedido'=>$pedido,'productos'=>$this->model->verPedidos((int)$id,(int)$idCliente),
                      'moneda'=>MONEDA],JSON_UNESCAPED_UNICODE);
    exit;
}
public function salir(){
    session_destroy();
    header('Location: '.BASE_URL);
}

    //listar productos pendientes
    public function listarProductos()
    {
        $id_cliente = $_SESSION['idCliente'];
        $data = $this->model->getProductos($id_cliente);
        for ($i = 0; $i < count($data); $i++) {
            $comprobar = $this->model->comprobarCalificacion($data[$i]['id_producto'], $id_cliente);
            $total = (empty($comprobar)) ? 0 : $comprobar['cantidad'] ;
            $uno = ($total >= 1) ? 'text-warning' : 'text-muted';
            $dos = ($total >= 2) ? 'text-warning' : 'text-muted';
            $tres = ($total >= 3) ? 'text-warning' : 'text-muted';
            $cuatro = ($total >= 4) ? 'text-warning' : 'text-muted';
            $cinco = ($total == 5) ? 'text-warning' : 'text-muted';
            $data[$i]['calificacion'] = '<ul class="list-unstyled d-flex justify-content-between">
                <li class="calificacion">
                    <i class="fas fa-star '.$uno.'" onclick="agregarCalificacion(' . $data[$i]['id_producto'] . ', 1)"></i>
                    <i class="fas fa-star '.$dos.'" onclick="agregarCalificacion(' . $data[$i]['id_producto'] . ', 2)"></i>
                    <i class="fas fa-star '.$tres.'" onclick="agregarCalificacion(' . $data[$i]['id_producto'] . ', 3)"></i>
                    <i class="fas fa-star '.$cuatro.'" onclick="agregarCalificacion(' . $data[$i]['id_producto'] . ', 4)"></i>
                    <i class="fas fa-star '.$cinco.'" onclick="agregarCalificacion(' . $data[$i]['id_producto'] . ', 5)"></i>
                </li>
            </ul>';
        }
        echo json_encode($data);
        die();
    }
public function agregarCalificacion()
{
    $id_cliente = $_SESSION['idCliente'];
    $datos = file_get_contents('php://input');
    $json = json_decode($datos, true);
    $comprobar = $this->model->comprobarCalificacion($json['id_producto'], $id_cliente);
    if (empty($comprobar)) {
        $data = $this->model->agregarCalificacion($json['cantidad'], $json['id_producto'], $id_cliente);
        if ($data > 0) {
            $mensaje = array('msg' => 'calificacion agregada', 'icono' => 'success');
        } else {
            $mensaje = array('msg' => 'error al calificar', 'icono' => 'error');
        }
    } else {
        $data = $this->model->cambiarCalificacion($json['cantidad'], $json['id_producto'], $id_cliente);
        if ($data == 1) {
            $mensaje = array('msg' => 'calificacion agregada', 'icono' => 'success');
        } else {
            $mensaje = array('msg' => 'error al calificar', 'icono' => 'error');
        }
    }


    echo json_encode($mensaje);
    die();
}
}//class