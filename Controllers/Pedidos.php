<?php
/** Instituto Visual California — Fase 4.7. Administrador de pedidos con seguridad de servidor. */
class Pedidos extends Controller
{
    private array $usuario;

    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $correo = (string)($_SESSION['email'] ?? '');
        $nombre = trim((string)($_SESSION['nombre_usuario'] ?? ''));
        $usuario = ($correo !== '' && $nombre !== '') ? $this->model->usuarioVigente($correo) : null;
        if (!$usuario) {
            if ($this->pideJson()) $this->json(false, 'Sesión administrativa inválida.', [], 401);
            header('Location: '.BASE_URL.'admin');
            exit;
        }
        $this->usuario = $usuario;
    }

    private function pideJson(): bool
    {
        return str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/x-www-form-urlencoded')
            || str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json');
    }

    private function json(bool $ok, string $mensaje, array $extra = [], int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(array_merge(['ok'=>$ok, 'msg'=>$mensaje], $extra), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    private function exigirPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') $this->json(false, 'Método no permitido.', [], 405);
        $s = $_POST['csrf'] ?? null;
        if (!is_string($s) || !isset($_SESSION['ivc_pedidos_admin_csrf'])
            || !hash_equals((string)$_SESSION['ivc_pedidos_admin_csrf'], $s)) {
            $this->json(false, 'Sesión vencida: actualiza la página.', [], 403);
        }
    }

    public function index(): void
    {
        if (empty($_SESSION['ivc_pedidos_admin_csrf'])) {
            $_SESSION['ivc_pedidos_admin_csrf'] = bin2hex(random_bytes(32));
        }
        $this->views->getView('admin/pedidos', 'index', [
            'title'=>'Administración de pedidos',
            'csrf'=>$_SESSION['ivc_pedidos_admin_csrf'],
        ]);
    }

    public function listar(): void
    {
        try {
            $q = trim((string)($_GET['q'] ?? ''));
            $proceso = (string)($_GET['proceso'] ?? '');
            if (strlen($q) > 100) $this->json(false, 'Búsqueda demasiado larga.', [], 422);
            if ($proceso !== '' && !in_array($proceso, ['1','2','3'], true)) $this->json(false, 'Filtro inválido.', [], 422);
            $this->json(true, 'Pedidos consultados.', ['pedidos'=>$this->model->listarAdmin($q, $proceso)]);
        } catch (Throwable $e) {
            error_log('IVC pedidos listar: '.$e->getMessage());
            $this->json(false, 'No se pudo consultar la lista.', [], 500);
        }
    }

    public function detalle($id = ''): void
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) $this->json(false, 'Pedido inválido.', [], 422);
        try {
            $pedido = $this->model->detalleAdmin((int)$id);
            if (!$pedido) $this->json(false, 'Pedido inexistente.', [], 404);
            $this->json(true, 'Detalle consultado.', ['pedido'=>$pedido]);
        } catch (Throwable $e) {
            error_log('IVC pedidos detalle: '.$e->getMessage());
            $this->json(false, 'No fue posible consultar el pedido.', [], 500);
        }
    }

    /** Solo POST. NUNCA se avanza a producción con recetas o valoraciones pendientes. */
    public function avanzar(): void
    {
        $this->exigirPost();
        $id = filter_var($_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) $this->json(false, 'Pedido inválido.', [], 422);
        try {
            $nuevo = $this->model->avanzar((int)$id);
            $this->json(true, 'Pedido actualizado.', ['proceso'=>$nuevo]);
        } catch (DomainException $e) {
            $this->json(false, $e->getMessage(), [], 409);
        } catch (Throwable $e) {
            error_log('IVC pedidos avanzar: '.$e->getMessage());
            $this->json(false, 'No se pudo actualizar el pedido.', [], 500);
        }
    }

    /** Confirmación óptica manual posterior a la aprobación de la receta. */
    public function aprobarMica(): void
    {
        $this->exigirPost();
        $id = filter_var($_POST['id_detalle'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) $this->json(false, 'Detalle incorrecto.', [], 422);
        try {
            $this->model->aprobarMica((int)$id, (int)$this->usuario['id']);
            $this->json(true, 'Micas validadas para preparación.');
        } catch (DomainException $e) {
            $this->json(false, $e->getMessage(), [], 409);
        } catch (Throwable $e) {
            error_log('IVC pedidos aprobar mica: '.$e->getMessage());
            $this->json(false, 'No fue posible aprobar las micas.', [], 500);
        }
    }

    /** Una única operación atómica para revisar la receta y validar las micas del artículo. */
    public function revisarOptica(): void
    {
        $this->exigirPost();
        $detalle = filter_var($_POST['id_detalle'] ?? null, FILTER_VALIDATE_INT,
            ['options'=>['min_range'=>1]]);
        $accion = (string)($_POST['accion'] ?? '');
        $confirmacion = (string)($_POST['confirmacion'] ?? '');
        $nota = trim((string)($_POST['observaciones'] ?? ''));
        if (!$detalle || !in_array($accion, ['APROBAR','CORRECCION'], true)) {
            $this->json(false, 'Datos de revisión inválidos.', [], 422);
        }
        if ($accion === 'APROBAR' && $confirmacion !== '1') {
            $this->json(false, 'Debes confirmar que revisaste la receta y las micas.', [], 422);
        }
        if ($accion === 'CORRECCION' && $nota === '') {
            $this->json(false, 'Especifica el motivo de la corrección.', [], 422);
        }
        if ((function_exists('mb_strlen') ? mb_strlen($nota, 'UTF-8') : strlen($nota)) > 1500) {
            $this->json(false, 'La observación supera 1,500 caracteres.', [], 422);
        }
        try {
            $resultado = $this->model->revisarOpticaUnificada((int)$detalle,
                (int)$this->usuario['id'], $accion, $nota);
            $mensaje = $accion === 'APROBAR'
                ? 'Receta y micas verificadas. El pedido ya puede avanzar si no hay otras validaciones pendientes.'
                : 'Se solicitó corrección de la receta. La fabricación permanecerá bloqueada.';
            $this->json(true, $mensaje, ['revision'=>$resultado]);
        } catch (DomainException $e) {
            $this->json(false, $e->getMessage(), [], 409);
        } catch (Throwable $e) {
            error_log('IVC revisión óptica unificada: '.$e->getMessage());
            $this->json(false, 'No fue posible completar la revisión óptica.', [], 500);
        }
    }

    public function vincularCita(): void
    {
        $this->exigirPost();
        $pedido=filter_var($_POST['id_pedido'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $cita=filter_var($_POST['id_cita'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$pedido||!$cita) $this->json(false,'Pedido o cita inválidos.',[],422);
        try {$this->model->vincularCita((int)$pedido,(int)$cita);$this->json(true,'Cita vinculada al pedido.');}
        catch(DomainException $e){$this->json(false,$e->getMessage(),[],409);}
        catch(Throwable $e){error_log('IVC vincular cita: '.$e->getMessage());$this->json(false,'No se pudo vincular la cita.',[],500);}
    }

    public function realizarValoracion(): void
    {
        $this->exigirPost();
        $pedido=filter_var($_POST['id_pedido'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $cita=filter_var($_POST['id_cita'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$pedido||!$cita) $this->json(false,'Pedido o cita inválidos.',[],422);
        try {$this->model->marcarValoracionRealizada((int)$pedido,(int)$cita);$this->json(true,'Valoración realizada. Registra y asocia una receta válida.');}
        catch(DomainException $e){$this->json(false,$e->getMessage(),[],409);}
        catch(Throwable $e){error_log('IVC realizar valoración: '.$e->getMessage());$this->json(false,'No fue posible actualizar la valoración.',[],500);}
    }

    public function asociarReceta(): void
    {
        $this->exigirPost();
        $detalle=filter_var($_POST['id_detalle'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $receta=filter_var($_POST['id_receta'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$detalle||!$receta) $this->json(false,'Detalle o receta inválidos.',[],422);
        try {$this->model->asociarReceta((int)$detalle,(int)$receta);$this->json(true,'Receta asociada. Debes revisarla y validar las micas.');}
        catch(DomainException $e){$this->json(false,$e->getMessage(),[],409);}
        catch(Throwable $e){error_log('IVC asociar receta: '.$e->getMessage());$this->json(false,'No fue posible asociar la receta.',[],500);}
    }

    public function crearRecetaValoracion(): void
    {
        $this->exigirPost();
        $detalle=filter_var($_POST['id_detalle']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$detalle) $this->json(false,'Detalle inválido.',[],422);
        try {
            $id=$this->model->crearRecetaInstituto((int)$detalle,(int)$this->usuario['id'],$_POST);
            $this->json(true,'Receta del Instituto registrada. Debe aprobarse antes de validar las micas.',['id_receta'=>$id]);
        } catch(DomainException $e){$this->json(false,$e->getMessage(),[],409);}
        catch(Throwable $e){error_log('IVC receta de valoración: '.$e->getMessage());$this->json(false,'No se pudo registrar la receta.',[],500);}
    }

    /** Bloquea la ruta antigua GET pedidos/update/id/proceso (insegura). */
    public function update($datos = ''): void
    {
        $this->json(false, 'Operación retirada por seguridad. Utiliza el panel actualizado.', [], 405);
    }
}
