<?php
class AdminModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }
    public function getUsuario($correo)
    {
        $pdo = (new Conexion())->conect();
        $stmt = $pdo->prepare('SELECT u.*, r.codigo AS rol FROM usuario u INNER JOIN roles r ON r.id_rol=u.id_rol AND r.activo=1 WHERE u.correo=? AND u.estado=1 LIMIT 1');
        $stmt->execute([(string)$correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }
    public function getTotales($estado)
    {
        $sql = "SELECT COUNT(*) AS total FROM pedidos WHERE proceso = $estado";
        return $this->select($sql);
    }
    public function getProductos()
    {
        $sql = "SELECT COUNT(*) AS total FROM productos WHERE estado = 1";
        return $this->select($sql);
    }

    public function productosMinimos()
    {
        $sql = "SELECT * FROM productos WHERE cantidad < 15 AND estado = 1 ORDER BY cantidad DESC LIMIT 3";
        return $this->selectAll($sql);
    }

    public function topProductos()
    {
        $sql = "SELECT producto, SUM(cantidad) AS total FROM detalle_pedidos GROUP BY id_producto ORDER BY total DESC LIMIT 3";
        return $this->selectAll($sql);
    }

    /** Indicadores y series reales para la vista administrativa. Solo lectura. */
    public function resumenDashboard()
    {
        $uno = function ($sql) {
            $fila = $this->select($sql);
            return (float) ($fila['total'] ?? 0);
        };
        $inicio = (new DateTimeImmutable('first day of this month'))
            ->modify('-11 months')->format('Y-m-01');
        $periodo = [];
        $cursor = new DateTimeImmutable($inicio);
        for ($i = 0; $i < 12; $i++) {
            $periodo[$cursor->format('Y-m')] = 0.0;
            $cursor = $cursor->modify('+1 month');
        }
        // pedidos.estado = COMPLETED incluye el flujo histórico y el registro nuevo.
        // Estas son ventas registradas, no una conciliación de PayPal.
        $ventas = $this->selectAll("SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes,
                            COALESCE(SUM(monto), 0) AS total
                            FROM pedidos
                            WHERE estado = 'COMPLETED' AND fecha >= '" . $inicio . "'
                            GROUP BY DATE_FORMAT(fecha, '%Y-%m')");
        foreach ($ventas as $venta) {
            if (array_key_exists($venta['mes'], $periodo)) {
                $periodo[$venta['mes']] = (float) $venta['total'];
            }
        }
        $porEstado = [];
        foreach ([1, 2, 3] as $estado) {
            $porEstado[] = (int) $uno('SELECT COUNT(*) AS total FROM pedidos WHERE proceso = ' . $estado);
        }
        return [
            'meses' => array_keys($periodo),
            'ventas_mensuales' => array_values($periodo),
            'ventas_periodo' => array_sum($periodo),
            'pedidos_por_estado' => $porEstado,
            'total_pedidos' => array_sum($porEstado),
            'productos_activos' => (int) $uno('SELECT COUNT(*) AS total FROM productos WHERE estado = 1'),
            'existencias_bajas' => (int) $uno('SELECT COUNT(*) AS total FROM productos WHERE estado = 1 AND cantidad <= 5'),
            'categorias_activas' => (int) $uno('SELECT COUNT(*) AS total FROM categorias WHERE estado = 1'),
            'clientes' => (int) $uno('SELECT COUNT(*) AS total FROM clientes'),
            'recetas_pendientes' => (int) $uno("SELECT COUNT(*) AS total FROM recetas_opticas WHERE estado_revision = 'PENDIENTE'"),
            'citas_proximas' => (int) $uno('SELECT COUNT(*) AS total FROM citas WHERE estado = 1 AND fecha >= CURDATE()'),
            'top_productos' => $this->selectAll("SELECT d.id_producto, MAX(d.producto) AS producto,
                SUM(d.cantidad) AS total FROM detalle_pedidos d
                JOIN pedidos p ON p.id = d.id_pedido AND p.estado = 'COMPLETED'
                GROUP BY d.id_producto ORDER BY total DESC LIMIT 5"),
            'inventario_bajo' => $this->selectAll('SELECT id, nombre, cantidad FROM productos
                WHERE estado = 1 AND cantidad <= 5 ORDER BY cantidad ASC, nombre ASC LIMIT 6'),
            'productos_categoria' => $this->selectAll('SELECT c.categoria, COUNT(p.id) AS total
                FROM categorias c LEFT JOIN productos p ON p.id_categoria = c.id AND p.estado = 1
                WHERE c.estado = 1 GROUP BY c.id, c.categoria ORDER BY total DESC LIMIT 6'),
            'pedidos_recientes' => $this->selectAll('SELECT id, fecha, monto, proceso, estado
                FROM pedidos ORDER BY id DESC LIMIT 7'),
        ];
    }
}
