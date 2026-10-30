<?php
/** Registro de recetas del cliente autenticado. Estado inicial: PENDIENTE. */
class Recetas extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }
    private function json($ok, $message, array $data = [], $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(array_merge(['ok'=>$ok,'msg'=>$message],$data), JSON_UNESCAPED_UNICODE);
        exit;
    }
    private function cliente()
    {
        $id = filter_var($_SESSION['idCliente'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $correo = (string)($_SESSION['correoCliente'] ?? '');
        if (!$id || !$correo || !$this->model->clienteVigente($id, $correo)) $this->json(false,'Inicia sesión y verifica tu correo.',[],401);
        return (int)$id;
    }
    private function postSeguro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(false,'Método no permitido.',[],405);
        $token = $_POST['csrf'] ?? null;
        if (!is_string($token) || empty($_SESSION['ivc_rx_csrf']) || !hash_equals($_SESSION['ivc_rx_csrf'], $token)) {
            $this->json(false,'Formulario caducado; actualiza la página.',[],403);
        }
    }
    public function token()
    {
        $this->cliente();
        if (empty($_SESSION['ivc_rx_csrf'])) $_SESSION['ivc_rx_csrf'] = bin2hex(random_bytes(32));
        $this->json(true,'Token disponible.',['csrf'=>$_SESSION['ivc_rx_csrf']]);
    }
    public function misRecetas()
    {
        $id = $this->cliente();
        $this->json(true,'Recetas consultadas.',['datos'=>$this->model->misRecetas($id)]);
    }
    public function guardar()
    {
        $id = $this->cliente();
        $this->postSeguro();
        $guardado = null;
        try {
            $modalidad = (string)($_POST['modalidad'] ?? '');
            if (!in_array($modalidad,['MANUAL','ARCHIVO'],true)) throw new InvalidArgumentException('Selecciona captura manual o archivo.');
            $observaciones = trim((string)($_POST['observaciones'] ?? ''));
            if ((function_exists('mb_strlen') ? mb_strlen($observaciones, 'UTF-8') : strlen($observaciones)) > 1000) throw new InvalidArgumentException('Las observaciones aceptan hasta 1,000 caracteres.');
            $fecha = trim((string)($_POST['fecha_receta'] ?? ''));
            if ($fecha !== '') {
                $f = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
                if (!$f || $f->format('Y-m-d') !== $fecha || $f > new DateTimeImmutable('today') || $f < new DateTimeImmutable('2000-01-01')) throw new InvalidArgumentException('La fecha de la receta no es válida.');
            }
            $dp = $this->numero($_POST['dp'] ?? '', 40, 80, 'Distancia pupilar', false);
            $ojos = [];
            foreach (['OD','OI'] as $ojo) {
                $prefix = strtolower($ojo) . '_';
                $esf = $this->numero($_POST[$prefix.'esfera'] ?? '',-30,30,'Esfera '.$ojo,true);
                $cil = $this->numero($_POST[$prefix.'cilindro'] ?? '',-15,15,'Cilindro '.$ojo,true);
                $add = $this->numero($_POST[$prefix.'adicion'] ?? '',0,6,'Adición '.$ojo,true);
                $ejeRaw = trim((string)($_POST[$prefix.'eje'] ?? ''));
                $eje = $ejeRaw === '' ? null : filter_var($ejeRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0,'max_range'=>180]]);
                if ($eje === false) throw new InvalidArgumentException('Eje '.$ojo.' debe estar entre 0 y 180.');
                if ($cil !== null && (float)$cil != 0.0 && $eje === null && $modalidad === 'MANUAL') throw new InvalidArgumentException('Captura el eje del ojo '.$ojo.' cuando hay cilindro.');
                $ojos[$ojo] = ['esfera'=>$esf,'cilindro'=>$cil,'eje'=>$eje,'adicion'=>$add];
            }
            if ($modalidad === 'MANUAL' && $ojos['OD']['esfera'] === null && $ojos['OI']['esfera'] === null) throw new InvalidArgumentException('Captura al menos una esfera; si no conoces los valores, adjunta tu receta.');
            $archivo = null;
            if ($modalidad === 'ARCHIVO') {
                if (empty($_FILES['receta_archivo']) || !is_array($_FILES['receta_archivo']) || is_array($_FILES['receta_archivo']['name'])) throw new InvalidArgumentException('Selecciona un único archivo de receta.');
                $f = $_FILES['receta_archivo'];
                if (($f['error'] ?? -1) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('No se recibió el archivo completo; revisa el límite de subida de PHP.');
                if (!is_uploaded_file($f['tmp_name']) || $f['size'] < 1 || $f['size'] > 10485760) throw new InvalidArgumentException('El archivo debe tener entre 1 byte y 10 MB.');
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
                $tipos = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
                if (!isset($tipos[$mime])) throw new InvalidArgumentException('Solo se admiten JPG, PNG y PDF válidos.');
                $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $mime==='image/jpeg' ? ['jpg','jpeg'] : [$tipos[$mime]], true)) throw new InvalidArgumentException('La extensión no coincide con el tipo de archivo.');
                $nombre = bin2hex(random_bytes(20)).'.'.$tipos[$mime];
                $dir = self::directorioPrivado();
                if (!move_uploaded_file($f['tmp_name'], $dir.'/'.$nombre)) throw new RuntimeException('No fue posible almacenar el archivo de forma privada.');
                @chmod($dir.'/'.$nombre, 0600);
                $guardado = $dir.'/'.$nombre;
                $original = (function_exists('mb_substr') ? mb_substr(basename(str_replace('\\','/',(string)$f['name'])), 0, 255, 'UTF-8') : substr(basename(str_replace('\\','/',(string)$f['name'])), 0, 255));
                $archivo = ['nombre'=>$nombre,'original'=>$original,'mime'=>$mime,'size'=>filesize($guardado),'sha256'=>hash_file('sha256',$guardado)];
            }
            $idReceta = $this->model->registrar($id,[
                'modalidad'=>$modalidad,'fecha'=>$fecha?:null,'dp'=>$dp,
                'observaciones'=>$observaciones?:null,'ojos'=>$ojos
            ],$archivo);
            $this->json(true,'Receta registrada. La óptica debe revisarla antes de fabricar las micas.', ['id_receta'=>$idReceta,'estado'=>'PENDIENTE']);
        } catch (InvalidArgumentException $e) {
            if ($guardado && is_file($guardado)) @unlink($guardado);
            $this->json(false,$e->getMessage(),[],422);
        } catch (Throwable $e) {
            if ($guardado && is_file($guardado)) @unlink($guardado);
            error_log('IVC recetas guardar: '.$e->getMessage());
            $this->json(false,'No fue posible guardar la receta. Revisa los permisos del almacén privado.',[],500);
        }
    }
    private function numero($input, $min, $max, $campo, $cuartos)
    {
        $raw = trim((string)$input);
        if ($raw === '') return null;
        if (!preg_match('/^[+-]?(?:\d{1,2})(?:\.\d{1,2})?$/D',$raw)) throw new InvalidArgumentException($campo.': número inválido.');
        $value = (float)$raw;
        if ($value < $min || $value > $max || ($cuartos && abs(round($value*4)-$value*4)>0.00001)) throw new InvalidArgumentException($campo.': valor fuera de rango o paso de 0.25 incorrecto.');
        return number_format($value,2,'.','');
    }
    public static function directorioPrivado()
    {
        // Fuera de htdocs. Permitir sobreescribir mediante variable de entorno del servidor.
        $dir = getenv('IVC_RX_PRIVATE_DIR') ?: '/opt/lampp/var/ivc-recetas';
        $real = realpath($dir);
        if (!$real || !is_dir($real) || !is_writable($real)) throw new RuntimeException('Almacén privado no configurado.');
        $raiz = realpath(__DIR__.'/..');
        if ($raiz && (str_starts_with($real.'/', $raiz.'/') || str_starts_with($raiz.'/', $real.'/'))) throw new RuntimeException('Almacén privado mal ubicado.');
        // Rechazar htdocs y cualquier ruta dentro del document root Apache si es detectable.
        $docroot = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        if ($docroot && str_starts_with($real.'/', $docroot.'/')) throw new RuntimeException('Almacén no puede ser público.');
        return $real;
    }
    /** Envía desde el directorio privado. Solo el administrador puede solicitar inline. */
    public static function enviarArchivo(array $archivo, bool $inline = false)
    {
        $dir = self::directorioPrivado();
        $name = basename((string)($archivo['ruta_privada'] ?? ''));
        if ($name !== ($archivo['ruta_privada'] ?? '') || !preg_match('/^[a-f0-9]{40}\.(jpg|png|pdf)$/D', $name)) {
            http_response_code(404); exit;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $tipos = ['jpg'=>'image/jpeg', 'png'=>'image/png', 'pdf'=>'application/pdf'];
        if (!isset($tipos[$ext]) || ($archivo['tipo_mime'] ?? '') !== $tipos[$ext]) {
            http_response_code(404); exit;
        }
        $path = $dir.'/'.$name;
        if (!is_file($path) || !is_readable($path)) { http_response_code(404); exit; }
        // Verifica la firma del archivo para no servir otro formato dentro de un iframe.
        if ((new finfo(FILEINFO_MIME_TYPE))->file($path) !== $tipos[$ext]) { http_response_code(404); exit; }
        $disposicion = $inline ? 'inline' : 'attachment';
        header('Content-Type: '.$tipos[$ext]);
        header('Content-Disposition: '.$disposicion.'; filename="receta-'.(int)$archivo['id_archivo'].'.'.$ext.'"');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: frame-ancestors 'self'");
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: private, no-store, max-age=0');
        header('Content-Length: '.filesize($path));
        readfile($path);
        exit;
    }
    public function archivo($id='')
    {
        $cliente = $this->cliente();
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) { http_response_code(404); exit; }
        $a = $this->model->archivo($id);
        if (!$a || (int)$a['id_cliente'] !== $cliente) { http_response_code(404); exit; }
        self::enviarArchivo($a);
    }
}
