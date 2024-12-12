<?php
class PrincipalModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }

    public function getProducto($id_producto)
    {
        $sql = "SELECT p.*, c.categoria FROM productos p INNER JOIN categorias c ON p.id_categoria = c.id WHERE p.id = $id_producto";
        return $this->select($sql);
    }
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

    //produtcos relacionas con la categoria
    public function getProductosCat($id_categoria,$desde,$porPagina){
     $sql="SELECT * FROM productos WHERE id_categoria =$id_categoria LIMIT $desde,$porPagina";
     return $this->selectAll($sql);   
    }
    //obetener total productos relacionados con la cateogira
    public function getTotalProductosCat($id_categoria){
        $sql = "SELECT COUNT(*) AS total FROM productos WHERE id_categoria =$id_categoria" ;
        return $this->select($sql);   
       }

          //produtcos relacionas aleatorios
    public function getAleatorios($id_categoria,$id_producto){
        $sql="SELECT * FROM productos WHERE id_categoria =$id_categoria AND id != $id_producto ORDER BY RAND() LIMIT 20";
        return $this->selectAll($sql);   
       }
 
    public function getListaDeseo($id_producto){
        $sql="SELECT * FROM productos WHERE id=$id_producto";
        return $this->select($sql);   
       }
//busqueda de productos
       public function getBusqueda($valor){
        $sql="SELECT * FROM productos WHERE nombre LIKE '%".$valor."%' OR descripcion LIKE '%".$valor."%' LIMIT 6";
        return $this->selectAll($sql);   
       }
//citas

public function registrarCita($apellidoP, $apellidoM, $nombre, $correo, $telefono ,$fecha,$hora,$token)
{
    $sql = "INSERT INTO citas(apellidoP,apellidoM,nombre,correo,telefono,fecha,hora,token) VALUES (?,?,?,?,?,?,?,?)";
    $datos = array($apellidoP, $apellidoM, $nombre, $correo, $telefono ,$fecha,$hora,$token);
    $data = $this->insertar($sql, $datos);
    if ($data > 0) {
        $res = $data;
    } else {
        $res = 0;
    }
    return $res;
} //function registroDirecto

public function getToken($token)
{
    $sql = "Select * From citas WHERE token='$token' ";
    return $this->select($sql);
} //function getToken

public function actualizarVerify($id_cita)
{
    $sql = "UPDATE citas SET token=?,verify=? WHERE id_cita=?";
    $datos = array(null, 1, $id_cita);
    $data = $this->save($sql, $datos);
    if ($data == 1) {
        $res = $data;
    } else {
        $res = 0;
    }
    return $res;
}

public function getVerificar($correo)
{
    $sql = "Select * From citas WHERE correo='$correo' ";
    return $this->select($sql);
} //function getToken


public function verificarHorario($fecha, $hora)
{
    $sql = "SELECT * FROM citas WHERE fecha = ? AND hora = ?";
    $datos = array($fecha, $hora);
    return $this->select($sql, $datos);
}
}
 
?>