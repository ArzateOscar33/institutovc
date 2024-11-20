<?php
class ClientesModel extends Query
{

    public function __construct()
    {
        parent::__construct();
    }

    public function registroDirecto($apellidoP, $apellidoM, $nombre, $correo, $clave, $token)
    {
        $sql = "INSERT INTO clientes(apellidoP,apellidoM,nombre,correo,clave,token) VALUES (?,?,?,?,?,?)";
        $datos = array($apellidoP, $apellidoM, $nombre, $correo, $clave, $token);
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
        $sql = "Select * From clientes WHERE token='$token' ";
        return $this->select($sql);
    } //function getToken

    public function actualizarVerify($id)
    {
        $sql = "UPDATE clientes SET token=?,verify=? WHERE id=?";
        $datos = array(null, 1, $id);
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
        $sql = "Select * From clientes WHERE correo='$correo' ";
        return $this->select($sql);
    } //function getToken

    public function registrarPedido(
        $id_transaccion,
        $monto,
        $estado,
        $fecha,
        $email,
        $nombre,
        $apellido,
        $email_user
    ) {
        $sql = "INSERT INTO pedidos(id_transaccion,monto,estado,fecha,email,nombre,apellido,email_user) VALUES (?,?,?,?,?,?,?,?)";
        $datos = array($id_transaccion,
        $monto,
        $estado,
        $fecha,
        $email,
        $nombre,
        $apellido,
        $email_user);
        $data = $this->insertar($sql, $datos);
        if ($data > 0) {
            $res = $data;
        } else {
            $res = 0;
        }
        return $res;
    } //function registroDirecto

    public function getProducto($id_producto)
    {
        $sql = "SELECT * FROM productos WHERE id = $id_producto";
        return $this->select($sql);
    }

    public function registrarDetalle($producto, $precio, $cantidad, $id_pedido)
    {
        $sql = "INSERT INTO detalle_pedidos (producto, precio, cantidad, id_pedido) VALUES (?,?,?,?)";
        $datos = array($producto, $precio, $cantidad, $id_pedido);
        $data = $this->insertar($sql, $datos);
        if ($data > 0) {
            $res = $data;
        } else {
            $res = 0;
        }
        return $res;
    }
}
