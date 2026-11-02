<?php
/** Autorización central, basada en permisos en BD. ROOT tiene acceso inmutable. */
final class RolesAcceso
{
    private static $usuario = null;
    private static bool $consultado = false;
    private static ?array $permisos = null;

    /** Catálogo cerrado de permisos; códigos desconocidos nunca se conceden. */
    public static function catalogo(): array
    {
        return [
            'dashboard' => 'Ver dashboard y estadísticas',
            'pedidos.listar' => 'Consultar listado de pedidos',
            'pedidos.detalle' => 'Ver detalle y recetas de pedidos',
            'pedidos.administrar' => 'Administrar pedidos y validar micas',
            'recetas.leer' => 'Consultar recetas y adjuntos',
            'recetas.administrar' => 'Revisar y aprobar recetas',
            'citas.leer' => 'Ver calendario y consultar citas',
            'citas.crear' => 'Agendar citas',
            'citas.administrar' => 'Cancelar y administrar citas',
            'productos.leer' => 'Consultar inventario administrativo',
            'productos.administrar' => 'Crear y modificar productos',
            'categorias.leer' => 'Consultar categorías',
            'categorias.administrar' => 'Administrar categorías',
            'micas.leer' => 'Consultar materiales y precios',
            'micas.administrar' => 'Administrar materiales y precios',
            'usuarios.administrar' => 'Crear y editar usuarios',
        ];
    }

    public static function usuario(): ?array
    {
        if (self::$consultado) return self::$usuario;
        self::$consultado = true;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $id = (int)($_SESSION['ivc_admin_id'] ?? 0);
        if ($id < 1 || empty($_SESSION['email'])) return null;
        try {
            $pdo = (new Conexion())->conect();
            $q = $pdo->prepare('SELECT u.id,u.nombres,u.apellidos,u.correo,u.estado,u.id_rol,r.codigo AS rol FROM usuario u JOIN roles r ON r.id_rol=u.id_rol WHERE u.id=? AND r.activo=1 LIMIT 1');
            $q->execute([$id]); $u = $q->fetch(PDO::FETCH_ASSOC);
            if (!$u || (int)$u['estado'] !== 1 || !hash_equals(strtolower((string)$u['correo']), strtolower((string)$_SESSION['email']))) return null;
            $_SESSION['ivc_admin_rol'] = $u['rol'];
            return self::$usuario = $u;
        } catch (Throwable $e) { error_log('IVC RolesAcceso usuario: '.$e->getMessage()); return null; }
    }

    public static function rol(): ?string
    {
        $u = self::usuario(); return $u ? (string)$u['rol'] : null;
    }

    public static function permiso(string $accion): bool
    {
        $u = self::usuario();
        if (!$u) return false;
        if ($accion === 'roles.gestionar') return $u['rol'] === 'ROOT'; // No delegable.
        if (!array_key_exists($accion, self::catalogo())) return false;
        if ($u['rol'] === 'ROOT') return true;
        if (self::$permisos === null) {
            self::$permisos = [];
            try {
                $pdo = (new Conexion())->conect();
                $q = $pdo->prepare('SELECT permiso FROM rol_permisos WHERE id_rol=?');
                $q->execute([(int)$u['id_rol']]);
                foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $p) self::$permisos[(string)$p]=true;
            } catch (Throwable $e) { error_log('IVC RolesAcceso permisos: '.$e->getMessage()); return false; }
        }
        return !empty(self::$permisos[$accion]);
    }

    public static function destinoInicial(): string
    {
        foreach (['dashboard'=>'admin/home','citas.leer'=>'citas','pedidos.listar'=>'pedidos', 'productos.leer'=>'productos', 'categorias.leer'=>'categorias','micas.leer'=>'micas','recetas.leer'=>'recetasAdmin','usuarios.administrar'=>'usuarios'] as $permiso=>$ruta) {
            if (self::permiso($permiso)) return $ruta;
        }
        return 'admin';
    }

    public static function rutaPermitida(string $controlador, string $metodo): bool
    {
        $c=strtolower($controlador);$m=strtolower($metodo);
        if ($c==='admin') {
            if (in_array($m,['index','validar','salir'],true)) return true;
            return self::permiso('dashboard');
        }
        if ($c==='roles') return self::permiso('roles.gestionar');
        if ($c==='usuarios') return self::permiso('usuarios.administrar');
        if ($c==='productos' || $c==='categorias' || $c==='micas') {
            $read = ['index','listar','edit','obtener','historial','vergaleria'];
            return self::permiso($c . (in_array($m,$read,true)?'.leer':'.administrar'));
        }
        if ($c==='recetasadmin') {
            return self::permiso('recetas.'.(in_array($m,['index','listar','detalle','vistaprevia','archivo'],true)?'leer':'administrar'));
        }
        if ($c==='citas') {
            if (in_array($m,['index','listar','gethorariosdisponibles','verificarcorreoagenda'],true)) return self::permiso('citas.leer');
            if (in_array($m,['registrarcita','enviarcorreoagenda'],true)) return self::permiso('citas.crear');
            return self::permiso('citas.administrar');
        }
        if ($c==='pedidos') {
            if (in_array($m,['index','listar'],true)) return self::permiso('pedidos.listar');
            if ($m==='detalle') return self::permiso('pedidos.detalle');
            return self::permiso('pedidos.administrar');
        }
        return true; // Rutas públicas conservan su lógica actual.
    }

    public static function aplicar(string $controlador, string $metodo): void
    {
        $c=strtolower($controlador);$m=strtolower($metodo);
        if ($c==='admin' && in_array($m,['index','validar','salir'],true)) return;
        if (!in_array($c,['admin','roles','usuarios','productos','categorias','micas','recetasadmin','pedidos','citas'],true)) return;
        if (!self::usuario()) self::denegar(401,'Inicia sesión para continuar.');
        if (!self::rutaPermitida($controlador,$metodo)) self::denegar(403,'No tienes permiso para realizar esta operación.');
    }

    /** Respuesta común de autorización: JSON para AJAX y SweetAlert2 para navegación. */
    private static function denegar(int $codigo, string $mensaje): void
    {
        http_response_code($codigo);
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $ajax = str_contains($accept, 'json')
            || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($ajax || strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'codigo' => $codigo === 403 ? 'IVC_PERMISO_DENEGADO' : 'IVC_SESION_REQUERIDA',
                'icono' => $codigo === 403 ? 'warning' : 'info',
                'msg' => $mensaje,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($codigo === 401) {
            header('Location: ' . BASE_URL . 'admin', true, 302);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        $inicio = BASE_URL . self::destinoInicial();
        $inicioHtml = htmlspecialchars($inicio, ENT_QUOTES, 'UTF-8');
        $swalSrc = htmlspecialchars(BASE_URL . 'assets/js/sweetalert2.all.min.js', ENT_QUOTES, 'UTF-8');
        $inicioJs = json_encode($inicio, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>No tienes permisos · Instituto Visual California</title>'
            . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f1f7fc;font:16px system-ui,-apple-system,Segoe UI,Arial;color:#173553}'
            . '.ivc-denegado{max-width:470px;margin:24px;padding:34px;background:#fff;border-radius:22px;box-shadow:0 18px 55px #163c6120;text-align:center}'
            . '.ivc-denegado h1{font-size:26px;margin:14px 0}.ivc-denegado p{line-height:1.55;color:#567087}'
            . '.ivc-denegado a{display:inline-block;background:#1568a6;color:#fff;text-decoration:none;border-radius:12px;padding:13px 25px;font-weight:650}</style></head>'
            . '<body><main class="ivc-denegado"><div aria-hidden="true" style="font-size:40px">&#128274;</div>'
            . '<h1>No tienes permisos</h1><p>Tu usuario no tiene autorización para acceder a esta sección. Solicita el permiso a un administrador si lo necesitas.</p>'
            . '<a href="' . $inicioHtml . '">Volver al panel</a></main>'
            . '<script src="' . $swalSrc . '"></script><script>(function(){'
            . 'var destino=' . $inicioJs . ';'
            . 'if(window.Swal && typeof Swal.fire === "function"){' 
            . 'Swal.fire({icon:"warning",title:"No tienes permisos",text:"Tu usuario no tiene autorización para acceder a esta sección.",'
            . 'confirmButtonText:"Volver al panel",confirmButtonColor:"#1568a6",allowOutsideClick:false,allowEscapeKey:false})'
            . '.then(function(){window.location.assign(destino);});'
            . '}})();</script></body></html>';
        exit;
    }
}
