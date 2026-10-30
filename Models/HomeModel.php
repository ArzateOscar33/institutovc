<?php
class HomeModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }
    public function getCategorias(){
        $sql= "Select * From categorias LIMIT 6";
        return $this ->selectAll($sql);
    }//function getCategorias

    public function getNuevoProducto(){
        $sql= "SELECT * FROM productos WHERE estado=1 AND cantidad>0 ORDER BY id DESC LIMIT 3";
        return $this ->selectAll($sql);
    }//function getCategorias
    public function getCalificacion($accion, $id)
    {
        $sql = "SELECT $accion(cantidad) AS total FROM calificaciones WHERE id_producto = $id";
        return $this->select($sql);
    }
}


 
?>