<?php
/**
 * Instituto Visual California — configuración privada (Fase 4.9.1-A).
 * Este archivo no contiene credenciales. Los valores se leen fuera de htdocs.
 * No modificar la ruta sin mover también private.json de manera segura.
 */
$ivcPrivatePath = getenv('IVC_PRIVATE_CONFIG') ?: '/opt/lampp/var/ivc-config/private.json';
if (!is_readable($ivcPrivatePath)) {
    error_log('[IVC] Archivo privado de configuración no disponible.');
    http_response_code(503);
    exit('El servicio no está disponible temporalmente.');
}
$ivcPrivateRaw = file_get_contents($ivcPrivatePath);
$ivcSettings = ($ivcPrivateRaw !== false) ? json_decode($ivcPrivateRaw, true) : null;
if (!is_array($ivcSettings)) {
    error_log('[IVC] Formato inválido de la configuración privada.');
    http_response_code(503);
    exit('El servicio no está disponible temporalmente.');
}
$ivcKeys = [
    'BASE_URL', 'HOST', 'USER', 'PASS', 'DB', 'CHARSET', 'TITLE', 'MONEDA',
    'CLIENT_ID', 'USER_SMTP', 'PASS_SMTP', 'PUERTO_SMTP', 'HOST_SMTP'
];
foreach ($ivcKeys as $ivcKey) {
    if (!array_key_exists($ivcKey, $ivcSettings) || !is_string($ivcSettings[$ivcKey])) {
        error_log('[IVC] Falta clave de configuración: ' . $ivcKey);
        http_response_code(503);
        exit('El servicio no está disponible temporalmente.');
    }
    // Se conservan los nombres originales de constantes para no modificar controladores ni modelos.
    if (!defined($ivcKey)) {
        define($ivcKey, $ivcSettings[$ivcKey]);
    }
}
unset($ivcPrivatePath, $ivcPrivateRaw, $ivcSettings, $ivcKeys, $ivcKey);
