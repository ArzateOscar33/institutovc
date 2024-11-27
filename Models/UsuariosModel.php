<?php
class UsuariosModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }
    public function getUsuarios($estado){
        $sql= "SELECT  id,nombres,apellidos,correo,perfil FROM usuario WHERE estado=$estado";
        return $this->selectAll($sql);
    }//function getUsuarios
    public function registrar($nombre,$apellido,$correo,$clave){
        $sql="INSERT INTO usuario(nombres,apellidos,correo,clave) VALUES (?,?,?,?)";
        $array=array($nombre,$apellido,$correo,$clave);
        return $this->insertar($sql,$array);
        

    }
 
    public function verificarCorreo($correo){
        $sql="SELECT correo FROM usuario WHERE correo='$correo' AND estado=1";
        return $this->select($sql);
    }

    public function eliminar($idUser){
        $sql="UPDATE usuario SET estado=? WHERE id=?";
        $array=array(0,$idUser);
        return $this->save($sql,$array);
    }
    public function getUsuario($idUser){  
         $sql= "SELECT  id,nombres,apellidos,correo FROM usuario WHERE id=$idUser";
        return $this->select($sql);
    }

    public function modificar($nombre,$apellido,$correo,$id){
        $sql="UPDATE  usuario SET nombres=?,apellidos=?,correo=? WHERE id=?";
        $array=array($nombre,$apellido,$correo,$id);
        return $this->save($sql,$array);
        
    }
}
 
?>