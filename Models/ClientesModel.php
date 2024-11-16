<?php
class ClientesModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }
   
    public function registroDirecto($apellidoP,$apellidoM,$nombre,$correo,$clave,$token){
        $sql= "INSERT INTO clientes(apellidoP,apellidoM,nombre,correo,clave,token) VALUES (?,?,?,?,?,?)";
        $datos=array($apellidoP,$apellidoM,$nombre,$correo,$clave,$token);
       $data= $this->insertar($sql,$datos);
       if($data>0){
        $res=$data;
       }else{
        $res=0;
       }
       return $res;
    }//function registroDirecto

    public function getToken($token){
        $sql= "Select * From clientes WHERE token='$token' ";
        return $this->select($sql);
    }//function getToken

    public function actualizarVerify($id){
        $sql= "UPDATE clientes SET token=?,verify=? WHERE id=?";
        $datos=array(null,1,$id);
       $data= $this->save($sql,$datos);
       if($data==1){
        $res=$data;
       }else{
        $res=0;
       }
       return $res;
    }

    public function getVerificar($correo){
        $sql= "Select * From clientes WHERE correo='$correo' ";
        return $this->select($sql);
    }//function getToken
}
 
?>