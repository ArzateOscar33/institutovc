<?php
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
chdir(dirname(__DIR__));
require_once 'Config/Config.php';
require_once 'Config/App/Conexion.php';
require_once 'Config/App/CorreoCitas.php';
try {
    $r=(new CorreoCitas())->procesarPendientes(10);
    echo date('c').' | Correos de citas revisados: '.$r['procesados'].' | enviados: '.$r['enviados'].PHP_EOL;
} catch (Throwable $e) {
    error_log('[IVC] Error en cola de citas (ver log de servidor).');
    fwrite(STDERR,"No fue posible revisar la cola de citas.\n");exit(1);
}
