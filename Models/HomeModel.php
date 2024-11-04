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
        $sql= "Select * From productos ORDER BY id DESC LIMIT 3";
        return $this ->selectAll($sql);
    }//function getCategorias

}
 
?>