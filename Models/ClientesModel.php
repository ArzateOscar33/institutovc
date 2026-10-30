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
        $q=(new Conexion())->conect()->prepare('SELECT * FROM clientes WHERE token=? LIMIT 1');
        $q->execute([$token]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
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
        $q=(new Conexion())->conect()->prepare('SELECT * FROM clientes WHERE correo=? LIMIT 1');
        $q->execute([$correo]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    } //function getToken

    public function registrarPedido(
        $id_transaccion,
        $monto,
        $estado,
        $fecha,
        $email,
        $nombre,
        $apellido,
        $id_cliente,
    ) {
        $sql = "INSERT INTO pedidos(id_transaccion,monto,estado,fecha,email,nombre,apellido,id_cliente) VALUES (?,?,?,?,?,?,?,?)";
        $datos = array($id_transaccion,
        $monto,
        $estado,
        $fecha,
        $email,
        $nombre,
        $apellido,
        $id_cliente,
       );
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

    public function registrarDetalle($producto, $precio, $cantidad, $id_pedido,$id_producto)
    {
        $sql = "INSERT INTO detalle_pedidos (producto, precio, cantidad, id_pedido,id_producto) VALUES (?,?,?,?,?)";
        $datos = array($producto, $precio, $cantidad, $id_pedido,$id_producto);
        $data = $this->insertar($sql, $datos);
        if ($data > 0) {
            $res = $data;
        } else {
            $res = 0;
        }
        return $res;
    }

    public function getPedidos($id_cliente){
        $q=(new Conexion())->conect()->prepare('SELECT * FROM pedidos WHERE id_cliente=? ORDER BY id DESC');
        $q->execute([(int)$id_cliente]);
        return $q->fetchAll(PDO::FETCH_ASSOC);

    }
    public function verPedido($idPedido){
        $sql = "SELECT d.* FROM pedidos p INNER JOIN detalle_pedidos d ON p.id= d.id_pedido WHERE p.id = $idPedido";
        return $this->selectAll($sql);

    }
    public function getPedido(int $idPedido, int $idCliente)
    {
        $q=(new Conexion())->conect()->prepare('SELECT * FROM pedidos WHERE id=? AND id_cliente=?');
        $q->execute([$idPedido,$idCliente]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function verPedidos(int $idPedido, int $idCliente): array
    {
        $q=(new Conexion())->conect()->prepare('SELECT d.* FROM pedidos p
            INNER JOIN detalle_pedidos d ON p.id=d.id_pedido
            WHERE p.id=? AND p.id_cliente=? ORDER BY d.id');
        $q->execute([$idPedido,$idCliente]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getProductos($id_cliente)
    {
        $sql = "SELECT d.producto, d.precio, SUM(d.cantidad) AS cantidad, d.id_producto FROM pedidos p INNER JOIN detalle_pedidos d ON p.id = d.id_pedido WHERE p.id_cliente = $id_cliente GROUP BY d.id_producto";
        return $this->selectAll($sql);
    }
    public function comprobarCalificacion($id_producto, $id_cliente)
    {
        $sql = "SELECT * FROM calificaciones WHERE id_producto = $id_producto AND id_cliente = $id_cliente";
        return $this->select($sql);
    }

    public function agregarCalificacion($cantidad, $id_producto, $id_cliente)
    {
        $sql = "INSERT INTO calificaciones (cantidad, id_producto, id_cliente) VALUES (?,?,?)";
        $datos = array($cantidad, $id_producto, $id_cliente);
        $data = $this->insertar($sql, $datos);
        if ($data > 0) {
            $res = $data;
        } else {
            $res = 0;
        }
        return $res;
    }

    public function cambiarCalificacion($cantidad, $id_producto, $id_cliente)
    {
        $sql = "UPDATE calificaciones SET cantidad=? WHERE id_producto=? AND id_cliente=?";
        $datos = array($cantidad, $id_producto, $id_cliente);
        $data = $this->save($sql, $datos);
        if ($data == 1) {
            $res = 1;
        } else {
            $res = 0;
        }
        return $res;
    }
}
