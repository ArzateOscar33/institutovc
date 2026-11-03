<?php
/** IVC 4.9.5.3: caja y preparación óptica. Sin cobros ni confirmación de ventas. */
class Pos extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    private function usuarioId(): int
    {
        $u=RolesAcceso::usuario();
        if (!$u) $this->json(['ok'=>false,'msg'=>'Inicia sesión para continuar.'],401);
        return (int)$u['id'];
    }
    private function json(array $datos, int $codigo=200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($datos,JSON_UNESCAPED_UNICODE);exit;
    }
    private function soloPost(): void
    {
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST') $this->json(['ok'=>false,'msg'=>'Método no permitido.'],405);
        $esperado=$_SESSION['ivc_pos_csrf']??null;
        $recibido=$_POST['csrf']??null;
        if (!is_string($esperado)||$esperado===''||!is_string($recibido)||!hash_equals($esperado,$recibido)) {
            $this->json(['ok'=>false,'msg'=>'Sesión de seguridad caducada. Actualiza la página.'],403);
        }
    }
    private function ejecutar(callable $fn): void
    {
        try {$this->json(['ok'=>true,'datos'=>$fn()]);}
        catch (DomainException $e) {$this->json(['ok'=>false,'msg'=>$e->getMessage()],409);}
        catch (InvalidArgumentException $e) {$this->json(['ok'=>false,'msg'=>$e->getMessage()],422);}
        catch (Throwable $e) {error_log('IVC POS 4.9.5.1: '.$e->getMessage());$this->json(['ok'=>false,'msg'=>'No se pudo completar la operación. Comprueba la migración SQL o consulta al administrador.'],500);}
    }
    public function index(): void
    {
        $this->usuarioId();
        if (empty($_SESSION['ivc_pos_csrf'])) $_SESSION['ivc_pos_csrf']=bin2hex(random_bytes(32));
        $data=['title'=>'Punto de Venta · Caja','csrf'=>$_SESSION['ivc_pos_csrf'],
            'permisos'=>[
                'abrir'=>RolesAcceso::permiso('pos.caja.abrir'),
                'cerrar'=>RolesAcceso::permiso('pos.caja.cerrar'),
                'movimientos'=>RolesAcceso::permiso('pos.caja.movimientos'),
                'supervisar'=>RolesAcceso::permiso('pos.caja.supervisar'),
            ]];
        $this->views->getView('admin/pos','index',$data);
    }
    public function resumen(): void
    {
        $this->ejecutar(fn()=> $this->model->resumen($this->usuarioId(), RolesAcceso::permiso('pos.caja.supervisar')));
    }
    public function abrir(): void
    {
        $this->soloPost();
        $id=(int)($_POST['id_caja']??0);
        $fondo=(string)($_POST['fondo_inicial']??'');
        $this->ejecutar(fn()=> $this->model->abrir($this->usuarioId(),$id,$fondo));
    }
    public function cerrar(): void
    {
        $this->soloPost();
        $contado=(string)($_POST['efectivo_contado']??'');
        $this->ejecutar(fn()=> $this->model->cerrar($this->usuarioId(),$contado));
    }
    public function movimiento(): void
    {
        $this->soloPost();
        $tipo=(string)($_POST['tipo']??'');
        $importe=(string)($_POST['importe']??'');
        $motivo=(string)($_POST['motivo']??'');
        $this->ejecutar(fn()=> $this->model->movimiento($this->usuarioId(),$tipo,$importe,$motivo));
    }
    /** Pantalla de preparación. No reserva productos ni escribe pedidos. */
    public function venta(): void
    {
        $usuario = $this->usuarioId();
        if (empty($_SESSION['ivc_pos_csrf'])) $_SESSION['ivc_pos_csrf'] = bin2hex(random_bytes(32));
        $turno = null;
        try { $turno = $this->model->turnoVenta($usuario); }
        catch (Throwable $e) { error_log('IVC POS vista venta: '.$e->getMessage()); }
        $data = [
            'title' => 'Punto de Venta · Cobrar venta',
            'csrf' => $_SESSION['ivc_pos_csrf'],
            'turno' => $turno,
            'puede_descontar' => RolesAcceso::permiso('pos.descuentos'),
            'puede_cobrar' => RolesAcceso::permiso('pos.vender'),
            'puede_crear_cliente' => RolesAcceso::permiso('pos.clientes.crear'),
            'puede_activar_cliente' => RolesAcceso::permiso('pos.clientes.activar'),
        ];
        $this->views->getView('admin/pos', 'venta', $data);
    }

    public function categoriasventa(): void
    {
        $this->usuarioId();
        $this->soloGet();
        $this->ejecutar(fn() => $this->model->categoriasVenta());
    }

    public function buscarproductos(): void
    {
        $usuario = $this->usuarioId();
        $this->soloGet();
        $texto = trim((string)($_GET['q'] ?? ''));
        $categoria = filter_var($_GET['categoria'] ?? '0', FILTER_VALIDATE_INT);
        $pagina = filter_var($_GET['pagina'] ?? '1', FILTER_VALIDATE_INT);
        $disponibles = (string)($_GET['disponibles'] ?? '0') === '1';
        $this->ejecutar(function () use ($usuario, $texto, $categoria, $pagina, $disponibles) {
            $this->model->exigirTurno($usuario);
            if ($categoria === false || $pagina === false) throw new InvalidArgumentException('Filtro de búsqueda inválido.');
            return $this->model->buscarProductosVenta($texto, (int)$categoria, (int)$pagina, $disponibles);
        });
    }

    public function buscarclientes(): void
    {
        $usuario = $this->usuarioId();
        $this->soloGet();
        $texto = trim((string)($_GET['q'] ?? ''));
        $this->ejecutar(function () use ($usuario, $texto) {
            $this->model->exigirTurno($usuario);
            return $this->model->buscarClientesVenta($texto);
        });
    }

    /** Sólo cotización de lectura. La venta definitiva validará de nuevo todo en MySQL. */
    public function previsualizar(): void
    {
        $this->soloPost();
        $usuario = $this->usuarioId();
        $raw = $_POST['items'] ?? '';
        if (!is_string($raw) || strlen($raw) > 32000) {
            $this->json(['ok'=>false, 'msg'=>'Carrito inválido o demasiado grande.'], 422);
        }
        $items = json_decode($raw, true);
        $porcentaje = $_POST['descuento'] ?? '0';
        $cliente = $_POST['cliente'] ?? '0';
        $this->ejecutar(function () use ($usuario, $items, $porcentaje, $cliente) {
            $this->model->exigirTurno($usuario);
            return $this->model->previsualizarVenta($items, $porcentaje, $cliente, RolesAcceso::permiso('pos.descuentos'));
        });
    }

    /** Cobro definitivo: sesión, permisos, idempotencia y transacción MySQL. */
    public function registrarventa(): void
    {
        $this->soloPost();
        $usuario=$this->usuarioId();
        if (!RolesAcceso::permiso('pos.vender')) {
            $this->json(['ok'=>false,'codigo'=>'IVC_PERMISO_DENEGADO','msg'=>'No tienes permisos para registrar ventas.'],403);
        }
        $raw=$_POST['items']??null;
        if (!is_string($raw) || strlen($raw)>32000) {
            $this->json(['ok'=>false,'msg'=>'El carrito no es válido.'],422);
        }
        $items=json_decode($raw,true);
        $datos=$_POST;
        $datos['items']=$items;
        $this->ejecutar(fn()=>$this->model->registrarVenta($usuario,$datos,RolesAcceso::permiso('pos.descuentos')));
    }

    private function soloGet(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            $this->json(['ok'=>false,'msg'=>'Método no permitido.'],405);
        }
    }

    /** Catálogo óptico exclusivamente de lectura para preparar lentes graduados. */
    public function catalogomicas(): void
    {
        $usuario = $this->usuarioId();
        $this->soloGet();
        $this->ejecutar(function () use ($usuario) {
            $this->model->exigirTurno($usuario);
            return $this->model->micasVenta();
        });
    }

    /** No devuelve datos de graduación; sólo metadatos de las recetas del cliente. */
    public function recetascliente(): void
    {
        $usuario = $this->usuarioId();
        $this->soloGet();
        $cliente = filter_var($_GET['cliente'] ?? null, FILTER_VALIDATE_INT);
        $this->ejecutar(function () use ($usuario, $cliente) {
            $this->model->exigirTurno($usuario);
            if ($cliente === false || $cliente === null || $cliente < 1) {
                throw new InvalidArgumentException('Selecciona un cliente válido para consultar sus recetas.');
            }
            return $this->model->recetasClienteVenta((int)$cliente);
        });
    }

    /** Cliente nuevo presencial. Sólo crea ficha; nunca registra pedidos ni autentica web. */
    public function crearcliente(): void
    {
        $this->soloPost();
        $usuario = $this->usuarioId();
        if (!RolesAcceso::permiso('pos.clientes.crear')) {
            $this->json(['ok'=>false, 'codigo'=>'IVC_PERMISO_DENEGADO', 'msg'=>'No tienes permisos para registrar clientes.'], 403);
        }
        $solicitud = (string)($_POST['solicitud'] ?? '');
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $solicitud)) {
            $this->json(['ok'=>false,'msg'=>'Solicitud inválida. Actualiza la pantalla e inténtalo nuevamente.'],422);
        }
        $this->ejecutar(function () use ($usuario, $solicitud) {
            $this->model->exigirTurno($usuario);
            // Sesión PHP bloquea solicitudes simultáneas de la misma sesión.
            $previos = $_SESSION['ivc_pos_clientes_solicitudes'] ?? [];
            if (isset($previos[$solicitud])) return $previos[$solicitud];
            $cliente = $this->model->registrarClientePos($_POST);
            $previos[$solicitud] = $cliente;
            if (count($previos) > 20) $previos = array_slice($previos, -20, null, true);
            $_SESSION['ivc_pos_clientes_solicitudes'] = $previos;
            return $cliente;
        });
    }

    /** Emitir código presencial: sólo usuario autorizado y caja abierta. */
    public function emitiractivacion(): void
    {
        $this->soloPost();
        $usuario = $this->usuarioId();
        if (!RolesAcceso::permiso('pos.clientes.activar')) {
            $this->json(['ok'=>false,'codigo'=>'IVC_PERMISO_DENEGADO', 'msg'=>'No tienes permiso para emitir códigos.'], 403);
        }
        $cliente=filter_var($_POST['id_cliente']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $correo=$_POST['correo']??'';
        $identidad=(string)($_POST['identidad_confirmada']??'')==='1';
        $this->ejecutar(function() use ($usuario,$cliente,$correo,$identidad) {
            $this->model->exigirTurno($usuario);
            if (!$cliente || !is_string($correo) || !$identidad) {
                throw new InvalidArgumentException('Selecciona cliente, verifica su identidad presencial y confirma el correo.');
            }
            require_once __DIR__.'/../Config/App/ActivacionClientesPos.php';
            return ActivacionClientesPos::emitir((int)$cliente,$usuario,$correo);
        });
    }

    /** Historial POS: los vendedores solo ven sus ventas; supervisores ven todas. */
    public function historial(): void
    {
        $this->usuarioId();
        if (empty($_SESSION['ivc_pos_csrf'])) $_SESSION['ivc_pos_csrf']=bin2hex(random_bytes(32));
        $data=['title'=>'Historial y devoluciones · POS','csrf'=>$_SESSION['ivc_pos_csrf'],
            'puede_devolver'=>RolesAcceso::permiso('pos.devoluciones'),
            'puede_ver_todas'=>RolesAcceso::permiso('pos.ventas.todas')];
        $this->views->getView('admin/pos','historial',$data);
    }
    private function postventa(): PosPostventa
    {
        require_once __DIR__.'/../Models/PosPostventa.php';
        return new PosPostventa();
    }
    public function historialdatos(): void
    {
        $this->soloGet();$id=$this->usuarioId();
        $this->ejecutar(fn()=> $this->postventa()->historial($id,RolesAcceso::permiso('pos.ventas.todas'),$_GET));
    }
    public function detalleventa(): void
    {
        $this->soloGet();$id=$this->usuarioId();
        $venta=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
        $this->ejecutar(function() use($venta,$id) {
            if(!$venta||$venta<1) throw new InvalidArgumentException('Selecciona una venta válida.');
            return $this->postventa()->detalle((int)$venta,$id,RolesAcceso::permiso('pos.ventas.todas'));
        });
    }
    public function devolver(): void
    {
        $this->soloPost();$id=$this->usuarioId();
        if (!RolesAcceso::permiso('pos.devoluciones')) {
            $this->json(['ok'=>false,'codigo'=>'IVC_PERMISO_DENEGADO','msg'=>'No tienes permisos para registrar devoluciones.'],403);
        }
        $this->ejecutar(function() use($id) {
            $json=$_POST['datos']??'';
            if(!is_string($json)||strlen($json)>12000) throw new InvalidArgumentException('Datos de devolución inválidos.');
            try {$datos=json_decode($json,true,40,JSON_THROW_ON_ERROR);}
            catch (JsonException $e) {throw new InvalidArgumentException('El formato de devolución no es válido.');}
            if(!is_array($datos)) throw new InvalidArgumentException('Devolución inválida.');
            return $this->postventa()->devolver($id,$datos);
        });
    }
    public function ticket(): void
    {
        $this->soloGet();$usuario=$this->usuarioId();
        $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
        if(!$id||$id<1) {http_response_code(400);exit('Venta inválida.');}
        try {
            $data=$this->postventa()->detalle((int)$id,$usuario,RolesAcceso::permiso('pos.ventas.todas'));
            header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
            $this->views->getView('admin/pos','ticket',['titulo'=>'Ticket de venta','detalle'=>$data]);
        } catch(Throwable $e) {error_log('IVC POS ticket: '.$e->getMessage());http_response_code(404);exit('Comprobante no disponible.');}
    }
    public function comprobantedevolucion(): void
    {
        $this->soloGet();$usuario=$this->usuarioId();
        $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
        if(!$id||$id<1) {http_response_code(400);exit('Devolución inválida.');}
        try {
            $data=$this->postventa()->comprobanteDevolucion((int)$id,$usuario,RolesAcceso::permiso('pos.ventas.todas'));
            header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
            $this->views->getView('admin/pos','ticket',['titulo'=>'Comprobante de devolución','comprobante'=>$data]);
        } catch(Throwable $e) {error_log('IVC POS ticket devolución: '.$e->getMessage());http_response_code(404);exit('Comprobante no disponible.');}
    }

    public function turnos(): void
    {
        $this->ejecutar(fn()=> $this->model->ultimosTurnos());
    }
}
