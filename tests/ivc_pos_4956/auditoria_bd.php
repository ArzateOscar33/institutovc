<?php
/** IVC 4.9.5.6 - Consulta únicamente SELECT sobre la BD existente.
 * Ejecutar desde consola /opt/lampp/bin/php con usuario MySQL de lectura cuando sea posible.
 * Prohibido agregar operaciones DML/DDL a este archivo.
 */
if (PHP_SAPI!=='cli') {http_response_code(403);exit;}
$root=dirname(__DIR__,2);
if (!is_file($root.'/Config/Config.php')) {fwrite(STDERR,"No se encontró la configuración de IVC.\n");exit(2);}
require_once $root.'/Config/Config.php';
try {
    $db=new PDO('mysql:host='.HOST.';dbname='.DB.';'.CHARSET,USER,PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch(Throwable $e){fwrite(STDERR,"No se pudo consultar MySQL. Revisa XAMPP y conexión privada.\n");exit(2);}
$failed=0;$pass=0;$errors=[];
function countQuery(PDO $db,string $nombre,string $sql): void {
    global $failed,$pass,$errors;
    try {
        $q=$db->query($sql);
        $n=(int)$q->fetchColumn();
        if ($n===0) {$pass++;echo "[OK] $nombre: 0 inconsistencias\n";}
        else {$failed++;$errors[]=[$nombre,$n];echo "[REVISAR] $nombre: $n inconsistencias\n";}
    }catch(Throwable $e){$failed++;$errors[]=[$nombre,'consulta'];echo "[ERROR] $nombre: no se pudo ejecutar la consulta.\n";}
}
// Cada sentencia de diagnóstico es SELECT sin parámetros externos; jamás se escriben filas.
$required=['pos_cajas','pos_turnos','pos_movimientos_caja','pos_ventas','pos_pagos','pos_devoluciones','pos_devolucion_detalles','pos_devolucion_pagos','pos_inventario_movimientos','pedidos','detalle_pedidos','productos','clientes','detalle_pedido_micas'];
$found=$db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
$missing=array_diff($required,$found);
if ($missing){echo "[ERROR] Tablas requeridas no encontradas: ".implode(', ',$missing)."\n";exit(2);}
$queries=[
 ['Stock negativo','SELECT COUNT(*) FROM productos WHERE cantidad<0'],
 ['Dos turnos abiertos para una caja',"SELECT COUNT(*) FROM (SELECT id_caja FROM pos_turnos WHERE estado='ABIERTO' GROUP BY id_caja HAVING COUNT(*)>1) t"],
 ['Usuario con múltiples turnos abiertos',"SELECT COUNT(*) FROM (SELECT id_usuario FROM pos_turnos WHERE estado='ABIERTO' GROUP BY id_usuario HAVING COUNT(*)>1) t"],
 ['Ventas sin renglones de pedido','SELECT COUNT(*) FROM pos_ventas v WHERE NOT EXISTS(SELECT 1 FROM detalle_pedidos d WHERE d.id_pedido=v.id_pedido)'],
 ['Total de venta distinto al de pagos',"SELECT COUNT(*) FROM pos_ventas v WHERE v.total IS NOT NULL AND v.total<>COALESCE((SELECT SUM(p.importe) FROM pos_pagos p WHERE p.id_venta=v.id_venta),0)"],
 ['Total de venta distinto a subtotal menos descuento',"SELECT COUNT(*) FROM pos_ventas WHERE subtotal IS NOT NULL AND total IS NOT NULL AND total<>subtotal-descuento"],
 ['Subtotal distinto de renglones y micas',"SELECT COUNT(*) FROM pos_ventas v WHERE v.subtotal IS NOT NULL AND v.subtotal<>COALESCE((SELECT SUM(d.cantidad*(d.precio+COALESCE(m.precio_unitario_mica,0))) FROM detalle_pedidos d LEFT JOIN detalle_pedido_micas m ON m.id_detalle_pedido=d.id WHERE d.id_pedido=v.id_pedido),0)"],
 ['Referencia bancaria de venta repetida',"SELECT COUNT(*) FROM (SELECT proveedor,referencia_terminal FROM pos_pagos WHERE proveedor IS NOT NULL AND referencia_terminal IS NOT NULL GROUP BY proveedor,referencia_terminal HAVING COUNT(*)>1) q"],
 ['Identificador de venta duplicado',"SELECT COUNT(*) FROM (SELECT solicitud FROM pos_ventas WHERE solicitud IS NOT NULL GROUP BY solicitud HAVING COUNT(*)>1) q"],
 ['Devoluciones superiores a venta',"SELECT COUNT(*) FROM pos_ventas v WHERE COALESCE((SELECT SUM(d.total) FROM pos_devoluciones d WHERE d.id_venta=v.id_venta),0)>v.total"],
 ['Devoluciones con pago distinto a su total',"SELECT COUNT(*) FROM pos_devoluciones d WHERE d.total<>COALESCE((SELECT SUM(p.importe) FROM pos_devolucion_pagos p WHERE p.id_devolucion=d.id_devolucion),0)"],
 ['Detalles de devolución ajenos al pedido original',"SELECT COUNT(*) FROM pos_devolucion_detalles dd JOIN pos_devoluciones dv ON dv.id_devolucion=dd.id_devolucion JOIN pos_ventas v ON v.id_venta=dv.id_venta JOIN detalle_pedidos dp ON dp.id=dd.id_detalle_pedido WHERE dp.id_pedido<>v.id_pedido"],
 ['Unidades devueltas superiores a las vendidas',"SELECT COUNT(*) FROM (SELECT dd.id_detalle_pedido,SUM(dd.cantidad) n,MAX(dp.cantidad) vendida FROM pos_devolucion_detalles dd JOIN detalle_pedidos dp ON dp.id=dd.id_detalle_pedido GROUP BY dd.id_detalle_pedido HAVING n>vendida) q"],
 ['Movimientos de inventario sin aritmética coherente',"SELECT COUNT(*) FROM pos_inventario_movimientos WHERE (tipo='VENTA' AND stock_despues<>stock_antes-cantidad) OR (tipo='DEVOLUCION' AND stock_despues<>stock_antes+cantidad)"],
 ['Devolución con inventario reingresado pero sin auditoría',"SELECT COUNT(*) FROM pos_devolucion_detalles dd JOIN pos_devoluciones d ON d.id_devolucion=dd.id_devolucion JOIN pos_ventas v ON v.id_venta=d.id_venta WHERE dd.reingresa_stock=1 AND NOT EXISTS (SELECT 1 FROM pos_inventario_movimientos m WHERE m.id_pedido=v.id_pedido AND m.id_detalle_pedido=dd.id_detalle_pedido AND m.tipo='DEVOLUCION' AND m.cantidad=dd.cantidad)"],
 ['Saldo esperado de turnos cerrados inconsistente',"SELECT COUNT(*) FROM pos_turnos t WHERE t.estado='CERRADO' AND (t.efectivo_esperado IS NULL OR ABS(t.efectivo_esperado-(t.fondo_inicial+COALESCE((SELECT SUM(CASE WHEN m.tipo IN ('INGRESO','VENTA') THEN m.importe ELSE -m.importe END) FROM pos_movimientos_caja m WHERE m.id_turno=t.id_turno),0)))>0.001)"],
 ['Reembolsos en efectivo superiores a pagos efectivos por venta',"SELECT COUNT(*) FROM pos_ventas v WHERE COALESCE((SELECT SUM(dp.importe) FROM pos_devolucion_pagos dp JOIN pos_devoluciones dv ON dv.id_devolucion=dp.id_devolucion WHERE dv.id_venta=v.id_venta AND dp.metodo='EFECTIVO'),0)>COALESCE((SELECT SUM(p.importe) FROM pos_pagos p WHERE p.id_venta=v.id_venta AND p.metodo='EFECTIVO'),0)"],
 ['Reembolsos en tarjeta superiores a pagos por tarjeta',"SELECT COUNT(*) FROM pos_ventas v WHERE COALESCE((SELECT SUM(dp.importe) FROM pos_devolucion_pagos dp JOIN pos_devoluciones dv ON dv.id_devolucion=dp.id_devolucion WHERE dv.id_venta=v.id_venta AND dp.metodo='TARJETA'),0)>COALESCE((SELECT SUM(p.importe) FROM pos_pagos p WHERE p.id_venta=v.id_venta AND p.metodo='TARJETA'),0)"],
];
echo "AUDITORÍA DE SOLO LECTURA — IVC POS 4.9.5.6\n";
try {
    $totalVentas=(int)$db->query('SELECT COUNT(*) FROM pos_ventas')->fetchColumn();
    $totalDevol=(int)$db->query('SELECT COUNT(*) FROM pos_devoluciones')->fetchColumn();
    echo "Ventas POS registradas: $totalVentas; devoluciones registradas: $totalDevol\n";
    if (!$totalVentas) echo "[AVISO] Sin ventas: auditoría de consistencia sin datos suficientes. Hacer pruebas manuales.\n";
    if (!$totalDevol) echo "[AVISO] Sin devoluciones: no se han ejercitado escenarios de reembolso en esta BD.\n";
} catch(Throwable $e) {echo "[AVISO] No se pudieron contar operaciones.\n";}
foreach($queries as [$nombre,$sql]) countQuery($db,$nombre,$sql);
printf("\nRESULTADO BD: %d controles sin hallazgos, %d con incidencias.\n",$pass,$failed);
if ($failed) {echo "No modificar datos automáticamente: revisar respaldos y conciliar las incidencias.\n";}
exit($failed?1:0);
