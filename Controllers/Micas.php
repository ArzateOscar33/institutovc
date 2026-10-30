<?php
/** Administración de micas | MVC de Instituto Visual California. */
class Micas extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        // Usamos ambas claves de sesión que establece Admin::validar().
        if (empty($_SESSION['nombre_usuario']) || empty($_SESSION['email'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
        // Un usuario desactivado no puede editar precios, aunque conserve cookie de sesión.
        $usuario = $this->model->usuarioActual($_SESSION['email']);
        if (!$usuario) {
            http_response_code(403);
            exit('Tu cuenta ya no tiene acceso administrativo.');
        }
        $this->idUsuario = $usuario;
    }

    private $idUsuario;

    private function responder($ok, $mensaje, $extra = [], $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(array_merge(['ok' => $ok, 'msg' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requerirPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(false, 'Utiliza una solicitud POST.', [], 405);
        }
        $tokenSesion = isset($_SESSION['micas_csrf']) ? $_SESSION['micas_csrf'] : '';
        $tokenRecibido = isset($_POST['csrf']) ? $_POST['csrf'] : '';
        if (!is_string($tokenRecibido) || !$tokenSesion || !hash_equals($tokenSesion, $tokenRecibido)) {
            $this->responder(false, 'Sesión caducada. Recarga el módulo.', [], 403);
        }
    }

    public function index()
    {
        if (empty($_SESSION['micas_csrf'])) {
            $_SESSION['micas_csrf'] = bin2hex(random_bytes(32));
        }
        $data['title'] = 'Catálogo de micas';
        $data['catalogos'] = $this->model->catalogos();
        $data['csrf'] = $_SESSION['micas_csrf'];
        $this->views->getView('admin/micas', 'index', $data);
    }

    public function listar()
    {
        try {
            $this->responder(true, 'Catálogo consultado.', ['datos' => $this->model->listar()]);
        } catch (Throwable $e) {
            error_log('IVC Micas listar: ' . $e->getMessage());
            $this->responder(false, 'No se pudo cargar el catálogo.', [], 500);
        }
    }

    public function obtener($id = '')
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) $this->responder(false, 'Identificador no válido.', [], 400);
        $dato = $this->model->obtener($id);
        if (!$dato) $this->responder(false, 'Mica no encontrada.', [], 404);
        $this->responder(true, 'Mica consultada.', ['mica' => $dato]);
    }

    public function historial($id = '')
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) $this->responder(false, 'Identificador no válido.', [], 400);
        if (!$this->model->obtener($id)) $this->responder(false, 'Mica no encontrada.', [], 404);
        $this->responder(true, 'Historial consultado.', ['datos' => $this->model->historial($id)]);
    }

    private function idsFormulario($clave)
    {
        $entrada = isset($_POST[$clave]) ? $_POST[$clave] : [];
        if (!is_array($entrada)) throw new InvalidArgumentException('Selección inválida: ' . $clave);
        if (count($entrada) > 50) throw new InvalidArgumentException('Demasiados elementos seleccionados.');
        $ids = [];
        foreach ($entrada as $valor) {
            $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) throw new InvalidArgumentException('Valor inválido en ' . $clave);
            $ids[$id] = $id;
        }
        return array_values($ids);
    }

    private function datosFormulario()
    {
        $idRaw = isset($_POST['id_mica']) ? (string)$_POST['id_mica'] : '0';
        $id = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($id === false) throw new InvalidArgumentException('Identificador inválido.');
        $sku = strtoupper(trim((string)($_POST['sku'] ?? '')));
        $nombre = trim((string)($_POST['nombre_comercial'] ?? ''));
        $precio = trim((string)($_POST['precio_venta'] ?? ''));
        $precioAnterior = trim((string)($_POST['precio_anterior'] ?? '0.00'));
        $tipo = filter_var($_POST['id_tipo_lente'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $notas = trim((string)($_POST['notas_internas'] ?? ''));
        $motivo = trim((string)($_POST['motivo_precio'] ?? ''));
        if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,44}$/D', $sku)) {
            throw new InvalidArgumentException('SKU: utiliza entre 2 y 45 caracteres (letras, números, guiones).');
        }
        if ($nombre === '' || (function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre)) > 170) {
            throw new InvalidArgumentException('Nombre: debe contener de 1 a 170 caracteres.');
        }
        // Evita redondeos invisibles: 1 a 6 enteros y exactamente hasta dos decimales.
        if (!preg_match('/^(?:0|[1-9][0-9]{0,5})(?:\.[0-9]{1,2})?$/D', $precio) || (float)$precio <= 0) {
            throw new InvalidArgumentException('El precio debe ser mayor a cero, con máximo dos decimales y hasta 999999.99 MXN.');
        }
        if ($id > 0 && !preg_match('/^[0-9]{1,6}(?:\.[0-9]{1,2})?$/D', $precioAnterior)) {
            throw new InvalidArgumentException('Recarga el formulario antes de actualizar el precio.');
        }
        if (!$tipo) throw new InvalidArgumentException('Selecciona un tipo de lente.');
        if ((function_exists('mb_strlen') ? mb_strlen($notas, 'UTF-8') : strlen($notas)) > 255 || (function_exists('mb_strlen') ? mb_strlen($motivo, 'UTF-8') : strlen($motivo)) > 255) {
            throw new InvalidArgumentException('La nota y el motivo aceptan hasta 255 caracteres.');
        }
        return [
            'id_mica' => $id,
            'sku' => $sku,
            'id_tipo_lente' => $tipo,
            'nombre_comercial' => $nombre,
            'precio_venta' => number_format((float)$precio, 2, '.', ''),
            'precio_anterior' => number_format((float)$precioAnterior, 2, '.', ''),
            'notas_internas' => $notas === '' ? null : $notas,
            'motivo_precio' => $motivo,
            'materiales' => $this->idsFormulario('materiales'),
            'tratamientos' => $this->idsFormulario('tratamientos')
        ];
    }

    public function guardar()
    {
        $this->requerirPost();
        try {
            $datos = $this->datosFormulario();
            $id = $this->model->guardar($datos, $this->idUsuario);
            $this->responder(true, 'Mica guardada correctamente. Precio establecido por PAR.', ['id' => $id]);
        } catch (InvalidArgumentException $e) {
            $this->responder(false, $e->getMessage(), [], 422);
        } catch (DomainException $e) {
            $this->responder(false, $e->getMessage(), [], 409);
        } catch (PDOException $e) {
            error_log('IVC Micas guardar (PDO): ' . $e->getMessage());
            $texto = $e->getCode() === '23000' ? 'El SKU ya existe o existe una asociación no válida.' : 'No se pudo guardar. Revisa el registro.';
            $this->responder(false, $texto, [], 409);
        } catch (Throwable $e) {
            error_log('IVC Micas guardar: ' . $e->getMessage());
            $this->responder(false, 'Ocurrió un error al guardar.', [], 500);
        }
    }

    public function estado()
    {
        $this->requerirPost();
        $id = filter_var($_POST['id_mica'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $valor = $_POST['activo'] ?? '';
        if (!$id || !in_array((string)$valor, ['0', '1'], true)) {
            $this->responder(false, 'Datos de estado inválidos.', [], 422);
        }
        try {
            $modificado = $this->model->cambiarEstado($id, (int)$valor);
            $this->responder($modificado, $modificado ? 'Estado actualizado.' : 'No hay cambios o no existe la mica.');
        } catch (Throwable $e) {
            error_log('IVC Micas estado: ' . $e->getMessage());
            $this->responder(false, 'No fue posible cambiar el estado.', [], 500);
        }
    }
}
