<?php
class AdminModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }
    public function getUsuario($correo){
        $sql= "SELECT * From usuario WHERE correo='$correo'";
        return $this->select($sql);
    }

 

}
?>