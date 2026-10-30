<?php
/** API de cotización, independiente del checkout PayPal actual. */
class Cotizaciones extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    private function responder(bool $ok, string $mensaje, array $extra = [], int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(array_merge(['ok'=>$ok, 'msg'=>$mensaje], $extra), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    private function esLista(array $a): bool
    {
        return array_keys($a) === range(0, count($a) - 1);
    }

    private function cliente(): int
    {
        $id = filter_var($_SESSION['idCliente'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $correo = $_SESSION['correoCliente'] ?? null;
        if (!$id || !is_string($correo) || !$this->model->clienteVigente((int)$id, $correo)) {
            $this->responder(false, 'Inicia sesión como cliente y verifica tu cuenta.', [], 401);
        }
        return (int)$id;
    }

    public function index()
    {
        $this->responder(true, 'Servicio de cotización listo. No realiza cobros.', ['fase'=>'4.1']);
    }

    /** Catálogo público; muestra solamente micas activas con precio confirmado por PAR. */
    public function catalogo()
    {
        try {
            $this->responder(true, 'Catálogo vigente.', ['micas'=>$this->model->catalogo()]);
        } catch (Throwable $e) {
            error_log('IVC cotizaciones catalogo: '.$e->getMessage());
            $this->responder(false, 'No se pudo consultar el catálogo.', [], 500);
        }
    }

    public function token()
    {
        $this->cliente();
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') $this->responder(false, 'Método no permitido.', [], 405);
        if (empty($_SESSION['ivc_cotizaciones_csrf'])) {
            $_SESSION['ivc_cotizaciones_csrf'] = bin2hex(random_bytes(32));
        }
        $this->responder(true, 'Token emitido.', ['csrf'=>$_SESSION['ivc_cotizaciones_csrf']]);
    }

    public function crear()
    {
        $cliente = $this->cliente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->responder(false, 'Utiliza POST.', [], 405);
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') === false) {
            $this->responder(false, 'Envía los datos como application/json.', [], 415);
        }
        $cuerpo = file_get_contents('php://input', false, null, 0, 32769);
        if ($cuerpo === false || strlen($cuerpo) > 32768) $this->responder(false, 'Solicitud demasiado grande.', [], 413);
        $datos = json_decode($cuerpo, true);
        if (!is_array($datos) || $this->esLista($datos)) $this->responder(false, 'JSON inválido.', [], 400);
        $csrf = $datos['csrf'] ?? null;
        $esperado = $_SESSION['ivc_cotizaciones_csrf'] ?? null;
        if (!is_string($csrf) || !is_string($esperado) || !hash_equals($esperado, $csrf)) {
            $this->responder(false, 'Token de seguridad inválido. Actualiza tu sesión.', [], 403);
        }
        // Límite modesto de cotizaciones por sesión para evitar spam accidental.
        $ahora = time();
        $_SESSION['ivc_cot_tiempos'] = array_values(array_filter(
            $_SESSION['ivc_cot_tiempos'] ?? [], static function ($t) use ($ahora) {
                return is_int($t) && $t > $ahora - 300;
            }
        ));
        if (count($_SESSION['ivc_cot_tiempos']) >= 15) {
            $this->responder(false, 'Demasiadas solicitudes. Intenta nuevamente en unos minutos.', [], 429);
        }
        if (!isset($datos['productos']) || !is_array($datos['productos'])) {
            $this->responder(false, 'Envía una lista de productos válida.', [], 422);
        }
        try {
            $cotizacion = $this->model->crear($cliente, $datos['productos']);
            $_SESSION['ivc_cot_tiempos'][] = $ahora;
            $this->responder(true, 'Cotización calculada y guardada en MySQL.', ['cotizacion'=>$cotizacion], 201);
        } catch (InvalidArgumentException $e) {
            $this->responder(false, $e->getMessage(), [], 422);
        } catch (DomainException $e) {
            $this->responder(false, $e->getMessage(), [], 409);
        } catch (Throwable $e) {
            error_log('IVC cotizaciones crear: '.$e->getMessage());
            $this->responder(false, 'No fue posible guardar la cotización.', [], 500);
        }
    }

    public function ver($id = '')
    {
        $cliente = $this->cliente();
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') $this->responder(false, 'Método no permitido.', [], 405);
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) $this->responder(false, 'Identificador inválido.', [], 400);
        try {
            $registro = $this->model->obtener((int)$id, $cliente);
            if (!$registro) $this->responder(false, 'Cotización no encontrada.', [], 404);
            $this->responder(true, 'Cotización encontrada.', ['cotizacion'=>$registro]);
        } catch (Throwable $e) {
            error_log('IVC cotizaciones ver: '.$e->getMessage());
            $this->responder(false, 'No fue posible consultar la cotización.', [], 500);
        }
    }
}
