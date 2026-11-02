<?php
/** Ejecución exclusiva por CLI; no permitir acceso HTTP público a la cola. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
require_once 'Config/Config.php';
require_once 'Config/App/Conexion.php';
require_once 'Config/App/CorreoPedidos.php';
try {
    $result = (new CorreoPedidos())->procesarPendientes(10);
    echo date('c').' | Correos procesados: '.(int)$result['procesados'].' | enviados: '.(int)$result['enviados'].PHP_EOL;
} catch (Throwable $e) {
    error_log('[IVC] Error en la cola de correos: '.$e->getMessage());
    fwrite(STDERR,"No se pudo procesar la cola de correos.\n");
    exit(1);
}
