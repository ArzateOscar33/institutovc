<?php
/** IVC 4.6.1. Ejecutar SOLO desde CLI (cron cada minuto).
 * Libera RESERVADA vencidas; no toca pagos capturados ni estados inciertos.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = dirname(__DIR__);
chdir($root);
require_once $root.'/Config/Config.php';
require_once $root.'/Config/App/Conexion.php';
require_once $root.'/Config/App/ReservasInventario.php';
$db = (new Conexion())->conect();
if (!$db instanceof PDO) {fwrite(STDERR,"Error conectando a MySQL\n");exit(1);}
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
try {
    $n=ReservasInventario::limpiarVencidas($db,100);
    echo date('c')." | Reservas liberadas: {$n}\n";
    $st=$db->query("SELECT id_cotizacion,id_cliente,creado_en
        FROM inventario_reservas WHERE estado IN ('PROCESANDO','REVISAR')
          AND actualizado_en < DATE_SUB(NOW(), INTERVAL 20 MINUTE)
        ORDER BY actualizado_en LIMIT 30");
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        // La captura podría haberse completado en PayPal. JAMÁS liberar automáticamente.
        fwrite(STDERR,'CONCILIAR MANUALMENTE: cotización '.$row['id_cotizacion']." (no realizar otro cobro)\n");
    }
} catch (Throwable $e) {
    fwrite(STDERR,'ERROR LIMPIEZA IVC: '.$e->getMessage()."\n");exit(2);
}
