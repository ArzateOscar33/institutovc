<?php
class ClientesModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }
   
    public function registroDirecto($apellidoP,$apellidoM,$nombre,$correo,$clave){
        $sql= "INSERT INTO clientes(apellidoP,apellidoM,nombre,correo,clave) VALUES (?,?,?,?,?)";
        $datos=array($apellidoP,$apellidoM,$nombre,$correo,$clave);
       $data= $this->insertar($sql,$datos);
       if($data>0){
        $res=$data;
       }else{
        $res=0;
       }
       return $res;
    }//function registroDirecto

}
 
?>