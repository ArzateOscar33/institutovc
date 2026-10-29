<?php
class PrincipalModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }

    /* IVC CATALOGO V4: consultas seguras para filtros, ordenamiento y paginación.
       Se agregan sin alterar las consultas originales utilizadas por otras pantallas. */
    private function ivcCatalogoConsulta(string $sql, array $params = [], bool $primero = false)
    {
        // Query mantiene $con privado. Aquí abrimos una conexión PDO solo para el catálogo.
        $pdo = (new Conexion())->conect();
        $stmt = $pdo->prepare($sql);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $primero ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ivcCatalogoCondiciones(array $filtros): array
    {
        $condiciones = ['p.estado = 1', 'c.estado = 1'];
        $params = [];
        if (!empty($filtros['categoria'])) {
            $condiciones[] = 'p.id_categoria = :categoria';
            $params[':categoria'] = (int) $filtros['categoria'];
        }
        if (isset($filtros['texto']) && $filtros['texto'] !== '') {
            $condiciones[] = '(p.nombre LIKE :buscar_nombre OR p.descripcion LIKE :buscar_descripcion)';
            $texto = '%' . (string) $filtros['texto'] . '%';
            $params[':buscar_nombre'] = $texto;
            $params[':buscar_descripcion'] = $texto;
        }
        if (isset($filtros['minimo']) && $filtros['minimo'] !== null) {
            $condiciones[] = 'p.precio >= :minimo';
            $params[':minimo'] = number_format((float)$filtros['minimo'], 2, '.', '');
        }
        if (isset($filtros['maximo']) && $filtros['maximo'] !== null) {
            $condiciones[] = 'p.precio <= :maximo';
            $params[':maximo'] = number_format((float)$filtros['maximo'], 2, '.', '');
        }
        if (!empty($filtros['disponible'])) {
            $condiciones[] = 'p.cantidad > 0';
        }
        return [' WHERE ' . implode(' AND ', $condiciones), $params];
    }

    public function getCategoriasCatalogo(): array
    {
        // Incluye solo categorías activas con al menos un producto activo.
        return $this->ivcCatalogoConsulta(
            'SELECT c.id, c.categoria, COUNT(p.id) AS total_productos
             FROM categorias c
             INNER JOIN productos p ON p.id_categoria = c.id AND p.estado = 1
             WHERE c.estado = 1
             GROUP BY c.id, c.categoria
             ORDER BY c.categoria ASC'
        );
    }

    public function getTotalCatalogo(array $filtros): int
    {
        list($where, $params) = $this->ivcCatalogoCondiciones($filtros);
        $res = $this->ivcCatalogoConsulta(
            'SELECT COUNT(*) AS total FROM productos p INNER JOIN categorias c ON c.id = p.id_categoria ' . $where,
            $params,
            true
        );
        return (int) ($res['total'] ?? 0);
    }

    public function getCatalogoFiltrado(array $filtros, int $desde, int $porPagina): array
    {
        list($where, $params) = $this->ivcCatalogoCondiciones($filtros);
        // Nunca interpolar directamente el parámetro GET en ORDER BY o LIMIT.
        $ordenesPermitidos = [
            'recientes' => 'p.id DESC',
            'precio_asc' => 'p.precio ASC, p.id DESC',
            'precio_desc' => 'p.precio DESC, p.id DESC',
            'nombre_asc' => 'p.nombre ASC, p.id DESC'
        ];
        $orden = $ordenesPermitidos[$filtros['ordenar'] ?? 'recientes'] ?? $ordenesPermitidos['recientes'];
        $desde = max(0, (int) $desde);
        $porPagina = max(1, min(60, (int) $porPagina));
        $sql = 'SELECT p.*, c.categoria FROM productos p
                INNER JOIN categorias c ON c.id = p.id_categoria'
              . $where . ' ORDER BY ' . $orden . ' LIMIT ' . $desde . ', ' . $porPagina;
        return $this->ivcCatalogoConsulta($sql, $params);
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

public function registrarCita($apellidoP, $apellidoM, $nombre, $correo, $telefono ,$fecha,$id_hora,$token)
{
    $sql = "INSERT INTO citas(apellidoP,apellidoM,nombre,correo,telefono,fecha,id_hora,token) VALUES (?,?,?,?,?,?,?,?)";
    $datos = array($apellidoP, $apellidoM, $nombre, $correo, $telefono ,$fecha,$id_hora,$token);
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
    $sql = "UPDATE citas SET token=?,verify=? ,color=? WHERE id_cita=?";
    $datos = array(null, 1, '#37b607',$id_cita);
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


 

public function obtenerHorariosDisponibles($fechaCita) {
    $sql = "SELECT h.id_horario,DATE_FORMAT(h.horario, '%H:%i') as horario
    FROM horarios h 
    LEFT JOIN citas c 
    ON h.id_horario = c.id_hora 
    AND c.fecha = '$fechaCita' 
    AND c.estado = 1 
    WHERE c.id_hora 
    IS NULL 
    AND h.estado = 0;";
    
    return $this->selectAll($sql);
}

 
}
 
?>