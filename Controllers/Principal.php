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
                //$fecha = $_POST['fechaNuevaCita'];
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
                $mensaje_cuerpo = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
        }
        .header {
            background-color: #002855;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .details {
            padding: 20px;
        }
        .details h2 {
            font-size: 20px;
            margin: 0 0 10px;
            color: #002855;
        }
        .details p {
            margin: 5px 0;
            font-size: 16px;
            color: #555;
        }
        .button-container {
            text-align: center;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color:rgb(4, 0, 0);
            background-color:rgb(241, 244, 232);
            text-decoration: none;
            border-radius: 5px;
        }
        .footer {
            padding: 20px;
            font-size: 14px;
            color: #555;
            text-align: center;
            background-color: #f9f9f9;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        .address {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tu cita ha sido confirmada</h1>
        </div>
        <div class="details">
            <h2>Detalles de la cita</h2>
            <p><strong>Nombre:</strong> ' . htmlspecialchars($_POST['iNombre']) . ' ' . htmlspecialchars($_POST['iapPaterno']) . ' ' . htmlspecialchars($_POST['iapMaterno']) . '</p>
            <p><strong>Fecha:</strong> ' . htmlspecialchars($_POST['fechaCita']) . '</p>
            <p><strong>Hora:</strong> ' . htmlspecialchars($_POST['selectHorarios']) . '</p>
            <p><strong>Atención:</strong> La Lic. Opt. Marcela Hernandez estará atendiéndote en la fecha y hora establecida.</p>
        </div>
        <div class="button-container">
            <a href="' . BASE_URL . 'principal/verificarCorreoAgenda/' . htmlspecialchars($_POST['token']) . '" class="button">Confirmar Cita</a>
        </div>
        <div class="address">
            <h3>Ubicación</h3>
            <p>Óptica Otay</p>
            <p>Blvd. Alberto Limón Padilla 1-8-8,</p>
            <p>La Pechuga, Fovissste, 22457 Tijuana, B.C.</p>
        </div>
        <div class="footer">
            <p>Gracias por tu preferencia</p>
            <p>Instituto Visual California</p>
            <p><a href="https://www.google.com/maps/place/Instituto+Visual+California/@32.5326146,-116.9474636,17z/data=!3m1!4b1!4m6!3m5!1s0x80d9471dc77f80dd:0x77e78ae8607ee6d!8m2!3d32.5326146!4d-116.9448887!16s%2Fg%2F11t5dx4wvp?entry=ttu&g_ep=EgoyMDI1MDEwOC4wIKXMDSoASAFQAw%3D%3D">Cómo llegar</a> | <a href="' . BASE_URL . 'principal/shop' . '">Visita Nuestra Tienda</a></p>
        </div>
    </div>
</body>
</html>
';

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
    public function getHorariosDisponibles()
    {
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
