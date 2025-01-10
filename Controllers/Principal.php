<?php
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
//Load Composer's autoloader
require 'vendor/autoload.php';

class Principal extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
    }

    //vista about
    public function about()
    {
        $data['perfil'] = 'no';
        $data['title'] = 'Sobre Nosotros';
        $this->views->getView('principal', "about", $data);
    }

    //vista SHOP
    public function shop($page)
    {
        $data['perfil'] = 'no';
        $pagina = (empty($page)) ? 1 : $page;
        $porPagina = 21;
        $desde = ($pagina - 1) * $porPagina;
        $data['title'] = 'Nuestros Productos';
        $data['productos'] = $this->model->getProductos($desde, $porPagina);
        $data['pagina'] = $pagina;
        $total = $this->model->getTotalProductos();
        $data['total'] = ceil($total['total'] / $porPagina);
        $this->views->getView('principal', "shop", $data);
    }
    //vista detalle
    public function detail($id_producto)
    {
        $data['perfil'] = 'no';
        $data['producto'] = $this->model->getProducto($id_producto);
        if (empty($data['producto'])) {
            echo 'Pagina no encontrada';
            exit;
        }
        $id_categoria = $data['producto']['id_categoria'];
        $data['relacionados'] = $this->model->getAleatorios($id_categoria, $data['producto']['id']);

        //scanear galeria
        $result = array();
        $directorio = 'assets/images/productos/' . $id_producto;
        if (file_exists($directorio)) {
            $imagenes = scandir($directorio);
            if (false !== $imagenes) {
                foreach ($imagenes as $file) {
                    if ('.' != $file && '..' != $file) {
                        array_push($result, $file);
                    }
                }
            }
        }
        $data['imagenes'] = $result;

        $data['title'] = $data['producto']['nombre'];
        $this->views->getView('principal', "shop-single", $data);
    }
    //vista Agendar
    public function agendar()
    {
        $data['perfil'] = 'no';
        $data['title'] = 'Agenda tu Cita';
        $this->views->getView('principal', "agendar", $data);
    }

    //vista categorias
    public function categorias($datos)
    {
        $data['perfil'] = 'no';
        $id_categoria = 1;
        $page = 1;

        $array = explode(',', $datos);
        if (isset($array[0])) {
            if (!empty($array[0])) {
                $id_categoria = $array[0];
            }
        }
        if (isset($array[1])) {
            if (!empty($array[1])) {
                $page = $array[1];
            }
        }


        $pagina = (empty($page)) ? 1 : $page;
        $porPagina = 15;
        $desde = ($pagina - 1) * $porPagina;

        $data['pagina'] = $pagina;
        $total = $this->model->getTotalProductosCat($id_categoria);
        $data['total'] = ceil($total['total'] / $porPagina);

        $data['productos'] = $this->model->getProductosCat($id_categoria, $desde, $porPagina);
        $data['title'] = 'Categorias';
        $data['id_categoria'] = $id_categoria;
        $this->views->getView('principal', "categorias", $data);
    }

    //vista deseos
    public function deseo()
    {
        $data['perfil'] = 'no';
        $data['title'] = 'Tu lista de Deseos';
        $this->views->getView('principal', "deseo", $data);
    }


    public function listaProductos()
    {
        $datos = file_get_contents('php://input');
        $json = json_decode($datos, true);
        $array['productos'] = array();
        $total = 0.00;
        if (!empty($json)) {

            foreach ($json as $producto) {
                $result = $this->model->getProducto($producto['idProducto']);
                $data['id'] = $result['id'];
                $data['nombre'] = $result['nombre'];
                $data['precio'] = $result['precio'];
                $data['cantidad'] = $producto['cantidad'];
                $data['imagen'] = $result['imagen'];
                $subTotal = $result['precio'] * $producto['cantidad'];
                $data['subTotal'] = number_format($subTotal, 2);

                array_push($array['productos'], $data);
                $total += $subTotal;
            }
        }
        $array['total'] = number_format($total, 2);
        $array['totalPayPal'] = number_format($total, 2, '.', '');
        $array['moneda'] = MONEDA;
        echo json_encode($array, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function busqueda($valor)
    {
        $data = $this->model->getBusqueda($valor);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Método para registrar una cita
    public function registrarCita()
    {
        if (isset($_POST['fechaCita'], $_POST['selectHorarios'])) {
            if (empty($_POST['fechaCita']) || empty($_POST['selectHorarios']) || empty($_POST['iapPaterno']) || empty($_POST['iapMaterno']) || empty($_POST['iCorreo']) || empty($_POST['iTelefono']) || empty($_POST['iNombre'])) {
                $mensaje = array('msg' => 'TODOS LOS CAMPOS SON REQUERIDOS DESDE REGISTRAR', 'icono' => 'warning');
            } else {
                $fecha = $_POST['fechaCita'];
                $hora = $_POST['selectHorarios'];
                $apellidoP = $_POST['iapPaterno'];
                $apellidoM = $_POST['iapMaterno'];
                $nombre = $_POST['iNombre'];
                $correo = $_POST['iCorreo'];
                $telefono = $_POST['iTelefono'];
                $token = md5($fecha . '-' . $hora);

                $resultado = $this->model->registrarCita($apellidoP, $apellidoM, $nombre, $correo, $telefono, $fecha, $hora, $token);
                if ($resultado > 0) {
                    $mensaje = array(
                        'msg' => 'Cita registrada con éxito',
                        'icono' => 'success',
                        'token' => $token,
                        'iCorreo' => $correo,
                        'iNombre' => $nombre,
                        'fechaCita' => $fecha,
                        'selectHorarios' => $hora
                    );
                } else {
                    $mensaje = array('msg' => 'Error al registrar la cita', 'icono' => 'error');
                }
            }
            echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
            die();
        } else {
            echo json_encode(['msg' => 'Solicitud inválida', 'icono' => 'error'], JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    public function enviarCorreoAgenda()
    {
        if (isset($_POST['iCorreo']) && isset($_POST['token'])) {
            //Create an instance; passing `true` enables exceptions
            $mail = new PHPMailer(true);

            try { 
        $token = $_POST['token'];
        $nombre = $_POST['iNombre'];
        $fecha = $_POST['fechaCita'];
        $hora = $_POST['selectHorarios'];
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
                $mensaje_cuerpo = "Estimado/a $nombre" . ",<br><br>";
                $mensaje_cuerpo .= "Se ha registrado tu cita con los siguientes datos:<br>";
                $mensaje_cuerpo .= "Fecha: $fecha" .   "<br>";
                $mensaje_cuerpo .= "Hora: $hora" .   "<br><br>";
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
        if (isset($_POST['fechaCita'])) {
            $fechaCita = $_POST['fechaCita'];
            $horarios = $this->model->obtenerHorariosDisponibles($fechaCita);
            echo json_encode($horarios, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('msg' => 'Fecha no proporcionada', 'icono' => 'error'), JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}//class
