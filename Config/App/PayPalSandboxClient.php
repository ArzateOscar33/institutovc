<?php
/** PayPal Orders v2. SOLO SANDBOX, sin soporte para producción en esta fase. */
final class PayPalSandboxClient
{
    private $clientId;
    private $clientSecret;
    private const API = 'https://api-m.sandbox.paypal.com';

    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('La extensión PHP cURL no está habilitada.');
        }
        $path = '/opt/lampp/var/ivc-paypal/sandbox.ini';
        $settings = is_readable($path) ? parse_ini_file($path, false, INI_SCANNER_RAW) : [];
        if (!is_array($settings)) $settings = [];
        $this->clientId = trim((string)($settings['client_id'] ?? getenv('IVC_PAYPAL_SANDBOX_CLIENT_ID') ?: ''));
        $this->clientSecret = trim((string)($settings['client_secret'] ?? getenv('IVC_PAYPAL_SANDBOX_CLIENT_SECRET') ?: ''));
        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new RuntimeException('Configura las credenciales PayPal Sandbox fuera de htdocs.');
        }
    }

    public function browserClientId(): string { return $this->clientId; }

    private function call(string $method, string $route, ?array $payload, array $headers, bool $auth): array
    {
        $url = self::API . $route;
        $ch = curl_init($url);
        if ($ch === false) throw new RuntimeException('No se pudo iniciar cURL.');
        $responseHeaders = ['Accept: application/json', 'Content-Type: application/json', 'Prefer: return=representation'];
        foreach ($headers as $h) $responseHeaders[] = $h;
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $responseHeaders,
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_TIMEOUT => 22,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($auth) curl_setopt($ch, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientSecret);
        if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload === [] ? (object)[] : $payload, JSON_UNESCAPED_SLASHES));
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new RuntimeException('No se obtuvo respuesta de PayPal Sandbox: ' . $error);
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) throw new RuntimeException('Respuesta inesperada de PayPal Sandbox. HTTP ' . $status);
        if ($status < 200 || $status >= 300) {
            $name = preg_replace('/[^A-Z_]/', '', (string)($decoded['name'] ?? 'API_ERROR'));
            throw new RuntimeException('PayPal Sandbox rechazó la operación (HTTP ' . $status . ', ' . $name . ').');
        }
        return $decoded;
    }

    private function accessToken(): string
    {
        $ch = curl_init(self::API . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $this->clientId . ':' . $this->clientSecret,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: es_MX', 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_CONNECTTIMEOUT => 7, CURLOPT_TIMEOUT => 22, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $parsed = is_string($body) ? json_decode($body, true) : null;
        if ($status !== 200 || !is_array($parsed) || empty($parsed['access_token'])) {
            throw new RuntimeException('No se pudo autenticar con PayPal Sandbox. Verifica client_id y client_secret.');
        }
        return $parsed['access_token'];
    }

    private function api(string $method, string $path, ?array $body = null, ?string $requestId = null): array
    {
        $headers = ['Authorization: Bearer ' . $this->accessToken()];
        if ($requestId !== null) $headers[] = 'PayPal-Request-Id: ' . $requestId;
        return $this->call($method, $path, $body, $headers, false);
    }

    private function key(string $action, string $identity): string
    {
        // Identificador estable entre reintentos: nunca regenerar un id distinto
        // al repetir el mismo POST hacia PayPal.
        return substr(hash('sha256', 'IVC-45|' . $action . '|' . $identity . '|' . $this->clientId), 0, 32);
    }

    public function crearOrden(int $quoteId, string $total): array
    {
        return $this->api('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'IVC-COT-' . $quoteId,
                'custom_id' => 'IVC-COT-' . $quoteId,
                'amount' => ['currency_code' => 'MXN', 'value' => $total],
                'description' => 'Instituto Visual California - Cotizacion ' . $quoteId,
            ]],
        ], $this->key('create', (string)$quoteId));
    }

    public function capturar(string $orderId): array
    {
        if (!preg_match('/^[A-Z0-9]{8,40}$/D', $orderId)) {
            throw new InvalidArgumentException('Identificador PayPal inválido.');
        }
        return $this->api('POST', '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture', [], $this->key('capture', $orderId));
    }

    public function verOrden(string $orderId): array
    {
        if (!preg_match('/^[A-Z0-9]{8,40}$/D', $orderId)) throw new InvalidArgumentException('Orden inválida.');
        return $this->api('GET', '/v2/checkout/orders/' . rawurlencode($orderId));
    }
}
