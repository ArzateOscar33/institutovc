<?php
class Clientes extends Controller
{
    public function __construct() {
        parent::__construct();
        session_start();
    }
    public function index()
    {
        $data['title'] = 'Tu Perfil';
        $this->views->getView('principal', "perfil", $data);
    }
   public function registroDirecto(){
    if(isset($_POST['nombre']) && isset($_POST['clave'])){
        $apellidoP=$_POST['apellidoP'];
        $apellidoM=$_POST['apellidoM']; 
        $nombre=$_POST['nombre'];
        $correo=$_POST['correo'];
        $clave=$_POST['clave'];
        $hash=password_hash($clave,PASSWORD_DEFAULT);
        $data=$this->model->registroDirecto($apellidoP,$apellidoM,$nombre,$correo,$hash);
        if($data>0){
            $mensaje=array('msg'=>'registrado con exito','icono'=>'success');
        }else{
            
            $mensaje=array('msg'=>'Error al registrarse','icono'=>'error');
        }
        echo json_encode($mensaje,JSON_UNESCAPED_UNICODE);
        die();
    }//if
   }




}//class