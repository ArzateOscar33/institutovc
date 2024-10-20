<?php
class PrincipalModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }

    public function GetProducto($id_producto){
        $sql = "SELECT p.*,c.categoria From productos p INNER JOIN  categorias c ON p.id_categoria=c.id WHERE p.id =$id_producto";
        return $this ->select($sql);
    }//function
//paginacion
    public function GetProductos($desde,$porPagina){
        $sql = "SELECT * FROM productos LIMIT $desde,$porPagina";
        return $this ->selectAll($sql);
    }//function
    //obtener el total productos
    public function GetTotalProductos( ){
        $sql = "SELECT COUNT(*) AS total FROM productos";
        return $this ->select($sql);
    }//function
}
 
?>