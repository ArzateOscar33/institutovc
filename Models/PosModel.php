<?php
/** Caja y previsualización comercial-óptica. La venta definitiva se implementará en 4.9.5.4. */
require_once __DIR__ . '/../Config/App/PosConfiguracionOptica.php';
require_once __DIR__ . '/../Config/App/PosClientes.php';
require_once __DIR__ . '/../Config/App/PosCobros.php';

class PosModel extends Query
{
    private function db(): PDO
    {
        $pdo=(new Conexion())->conect();
        if (!$pdo) throw new RuntimeException('No disponible MySQL.');
        return $pdo;
    }
    private function ahora(): string
    {
        return (new DateTimeImmutable('now',new DateTimeZone('America/Tijuana')))->format('Y-m-d H:i:s');
    }
    /** Parseo de centavos, sin aritmética flotante en arqueos. */
    private function centavos(string $numero): int
    {
        if (!preg_match('/^(0|[1-9][0-9]{0,8})(?:\.([0-9]{1,2}))?$/D',$numero,$m)) {
            throw new InvalidArgumentException('El importe debe ser positivo y tener máximo dos decimales.');
        }
        return ((int)$m[1]*100)+(int)str_pad($m[2]??'',2,'0');
    }
    private function decimal(int $centavos): string
    {
        $signo=$centavos<0?'-':'';$c=abs($centavos);
        return $signo.intdiv($c,100).'.'.str_pad((string)($c%100),2,'0',STR_PAD_LEFT);
    }
    private function uno(PDO $db,string $sql,array $args=[]): ?array
    {
        $q=$db->prepare($sql);$q->execute($args);return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }
    private function varios(PDO $db,string $sql,array $args=[]): array
    {
        $q=$db->prepare($sql);$q->execute($args);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    private function turno(PDO $db,int $usuario,bool $bloquear=false): ?array
    {
        return $this->uno($db,'SELECT t.*,c.nombre AS caja_nombre FROM pos_turnos t JOIN pos_cajas c ON c.id_caja=t.id_caja
            WHERE t.id_usuario=? AND t.estado=\'ABIERTO\' ORDER BY t.id_turno DESC LIMIT 1'.($bloquear?' FOR UPDATE':''),[$usuario]);
    }
    /** Calcula caja físicamente esperada: nunca incluye tarjeta. */
    private function calculo(PDO $db,array $turno): array
    {
        $res=$this->uno($db,"SELECT
            COALESCE(SUM(CASE WHEN tipo IN ('INGRESO','VENTA') THEN importe ELSE 0 END),0) AS entradas,
            COALESCE(SUM(CASE WHEN tipo IN ('RETIRO','DEVOLUCION') THEN importe ELSE 0 END),0) AS salidas,
            COUNT(*) AS cantidad
            FROM pos_movimientos_caja WHERE id_turno=?",[(int)$turno['id_turno']]);
        $base=$this->centavos((string)$turno['fondo_inicial']);
        $entradas=$this->centavos((string)$res['entradas']);
        $salidas=$this->centavos((string)$res['salidas']);
        return ['fondo_inicial'=>$this->decimal($base),'entradas'=>$this->decimal($entradas),
            'salidas'=>$this->decimal($salidas),'esperado'=>$this->decimal($base+$entradas-$salidas),
            'movimientos'=>(int)$res['cantidad']];
    }
    public function resumen(int $usuario,bool $supervisor): array
    {
        $db=$this->db();
        $cajas=$this->varios($db,"SELECT c.id_caja,c.codigo,c.nombre,c.activa,
            t.id_turno AS turno_abierto,t.id_usuario AS responsable_id,
            CONCAT(u.nombres,' ',u.apellidos) AS responsable
            FROM pos_cajas c LEFT JOIN pos_turnos t ON t.id_caja=c.id_caja AND t.estado='ABIERTO'
            LEFT JOIN usuario u ON u.id=t.id_usuario WHERE c.activa=1 ORDER BY c.id_caja");
        $turno=$this->turno($db,$usuario);
        $movimientos=[];$arqueo=null;
        if ($turno) {
            $arqueo=$this->calculo($db,$turno);
            $movimientos=$this->varios($db,'SELECT id_movimiento,tipo,importe,motivo,creado_en
                FROM pos_movimientos_caja WHERE id_turno=? ORDER BY id_movimiento DESC LIMIT 30',[(int)$turno['id_turno']]);
        }
        return ['cajas'=>$cajas,'turno'=>$turno,'arqueo'=>$arqueo,'movimientos'=>$movimientos,
            'supervisor'=>$supervisor];
    }
    public function abrir(int $usuario,int $caja,string $fondo): array
    {
        $centavos=$this->centavos($fondo);
        if ($caja<1) throw new InvalidArgumentException('Selecciona una caja válida.');
        $db=$this->db();$db->beginTransaction();
        try {
            // Primero bloquea usuario: evita abrir dos cajas simultáneas por el mismo usuario.
            $u=$this->uno($db,'SELECT id FROM usuario WHERE id=? AND estado=1 FOR UPDATE',[$usuario]);
            if (!$u) throw new DomainException('El usuario está inactivo.');
            $c=$this->uno($db,'SELECT id_caja FROM pos_cajas WHERE id_caja=? AND activa=1 FOR UPDATE',[$caja]);
            if (!$c) throw new DomainException('La caja ya no está disponible.');
            if ($this->uno($db,"SELECT id_turno FROM pos_turnos WHERE id_usuario=? AND estado='ABIERTO' LIMIT 1",[$usuario])) {
                throw new DomainException('Ya tienes un turno abierto. Ciérralo antes de abrir otro.');
            }
            if ($this->uno($db,"SELECT id_turno FROM pos_turnos WHERE id_caja=? AND estado='ABIERTO' LIMIT 1",[$caja])) {
                throw new DomainException('Esta caja ya tiene un turno abierto.');
            }
            $q=$db->prepare("INSERT INTO pos_turnos(id_caja,id_usuario,fondo_inicial,apertura_en,estado)
                VALUES(?,?,?,?,'ABIERTO')");
            $q->execute([$caja,$usuario,$this->decimal($centavos),$this->ahora()]);
            $id=(int)$db->lastInsertId();$db->commit();
            return ['id_turno'=>$id,'mensaje'=>'Caja abierta correctamente.'];
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
    public function movimiento(int $usuario,string $tipo,string $importe,string $motivo): array
    {
        if (!in_array($tipo,['INGRESO','RETIRO'],true)) throw new InvalidArgumentException('Movimiento no permitido.');
        $centavos=$this->centavos($importe);
        if ($centavos<1) throw new InvalidArgumentException('El importe debe ser mayor a cero.');
        $motivo=trim($motivo);
        if ($motivo==='' || strlen($motivo)>220) throw new InvalidArgumentException('Describe el motivo (máximo 220 caracteres).');
        $db=$this->db();$db->beginTransaction();
        try {
            $turno=$this->turno($db,$usuario,true);
            if (!$turno) throw new DomainException('Debes abrir tu caja para registrar movimientos.');
            $calculo=$this->calculo($db,$turno);
            if ($tipo==='RETIRO' && $centavos>$this->centavos($calculo['esperado'])) {
                throw new DomainException('El retiro excede el efectivo esperado.');
            }
            $q=$db->prepare('INSERT INTO pos_movimientos_caja(id_turno,id_usuario,id_pedido,tipo,importe,motivo,creado_en)
                VALUES(?,?,NULL,?,?,?,?)');
            $q->execute([(int)$turno['id_turno'],$usuario,$tipo,$this->decimal($centavos),$motivo,$this->ahora()]);
            $id=(int)$db->lastInsertId();$db->commit();
            return ['id_movimiento'=>$id,'mensaje'=>'Movimiento guardado.'];
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
    public function cerrar(int $usuario,string $contado): array
    {
        $importe=$this->centavos($contado);
        $db=$this->db();$db->beginTransaction();
        try {
            $turno=$this->turno($db,$usuario,true);
            if (!$turno) throw new DomainException('No tienes una caja abierta.');
            $arqueo=$this->calculo($db,$turno);
            $esperado=$this->centavos($arqueo['esperado']);
            $diferencia=$importe-$esperado;
            $q=$db->prepare("UPDATE pos_turnos SET estado='CERRADO',cierre_en=?,efectivo_esperado=?,efectivo_contado=?,diferencia=?
                WHERE id_turno=? AND estado='ABIERTO'");
            $q->execute([$this->ahora(),$this->decimal($esperado),$this->decimal($importe),
                $this->decimal($diferencia),(int)$turno['id_turno']]);
            if ($q->rowCount()!==1) throw new DomainException('El turno ya fue cerrado.');
            $db->commit();
            return ['mensaje'=>'Turno cerrado.','esperado'=>$this->decimal($esperado),
                'contado'=>$this->decimal($importe),'diferencia'=>$this->decimal($diferencia)];
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
    /** Consulta exclusiva: POS 4.9.5.2 no realiza ninguna venta. */
    public function turnoVenta(int $usuario): ?array
    {
        $turno = $this->turno($this->db(), $usuario);
        return $turno ? [
            'id_turno' => (int)$turno['id_turno'],
            'caja_nombre' => (string)$turno['caja_nombre'],
            'apertura_en' => (string)$turno['apertura_en'],
        ] : null;
    }

    public function exigirTurno(int $usuario): void
    {
        if ($this->turnoVenta($usuario) === null) {
            throw new DomainException('Abre una caja para preparar la venta.');
        }
    }

    public function categoriasVenta(): array
    {
        return $this->varios($this->db(),
            'SELECT id, categoria FROM categorias WHERE estado=1 ORDER BY categoria ASC, id ASC');
    }

    /** p.cantidad ya excluye las reservas web, según ReservasInventario.php. */
    public function buscarProductosVenta(string $texto, int $categoria, int $pagina, bool $disponibles): array
    {
        if (strlen($texto) > 180) throw new InvalidArgumentException('Búsqueda demasiado larga.');
        if ($categoria < 0 || $pagina < 1 || $pagina > 1000) throw new InvalidArgumentException('Filtro o página inválidos.');
        $where = ['p.estado=1']; $args = [];
        if ($texto !== '') {
            $literal = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $texto);
            $where[] = "(p.nombre LIKE ? OR CAST(p.id AS CHAR) LIKE ?)";
            $args[] = '%'.$literal.'%'; $args[] = '%'.$literal.'%';
        }
        if ($categoria > 0) { $where[] = 'p.id_categoria=?'; $args[] = $categoria; }
        if ($disponibles) $where[] = 'p.cantidad>0';
        $offset = ($pagina-1)*24;
        $sql = 'SELECT p.id,p.nombre,p.precio,p.cantidad,p.imagen,p.id_categoria,
                COALESCE(c.categoria,\'Sin categoría\') AS categoria
                FROM productos p LEFT JOIN categorias c ON c.id=p.id_categoria
                WHERE '.implode(' AND ', $where).' ORDER BY p.id DESC LIMIT 25 OFFSET '.$offset;
        $items = $this->varios($this->db(), $sql, $args);
        $hayMas = count($items) > 24;
        if ($hayMas) array_pop($items);
        foreach ($items as &$p) {
            $p['id'] = (int)$p['id'];
            $p['cantidad'] = (int)$p['cantidad'];
            $p['id_categoria'] = (int)$p['id_categoria'];
            $p['precio'] = (string)$p['precio'];
        }
        unset($p);
        return ['productos'=>$items,'pagina'=>$pagina,'hay_mas'=>$hayMas];
    }

    public function buscarClientesVenta(string $texto): array
    {
        if (strlen($texto) < 2) return [];
        if (strlen($texto) > 180) throw new InvalidArgumentException('Búsqueda demasiado larga.');
        $literal = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $texto);
        $busqueda = '%'.$literal.'%';
        $clientes = $this->varios($this->db(),
            'SELECT id, nombre, apellidoP, apellidoM, correo, telefono, origen FROM clientes
             WHERE nombre LIKE ? OR apellidoP LIKE ? OR apellidoM LIKE ? OR correo LIKE ? OR telefono LIKE ?
             ORDER BY nombre, apellidoP, id LIMIT 15',
             [$busqueda,$busqueda,$busqueda,$busqueda,$busqueda]);
        foreach ($clientes as &$c) $c['id'] = (int)$c['id'];
        unset($c);
        return $clientes;
    }

    /**
     * Alta presencial sin credenciales inventadas. GET_LOCK serializa las altas POS
     * entre vendedores (no altera registros web ni crea cuenta web).
     */
    public function registrarClientePos(array $datos): array
    {
        $d = PosClientes::validar($datos);
        $db = $this->db();
        $candado = $this->uno($db, "SELECT GET_LOCK('ivc_pos_alta_cliente', 8) AS obtenido");
        if ((int)($candado['obtenido'] ?? 0) !== 1) {
            throw new DomainException('Hay otro registro en curso. Inténtalo nuevamente.');
        }
        try {
            $db->beginTransaction();
            if ($d['correo'] !== null) {
                $coincide = $this->uno($db,'SELECT id FROM clientes WHERE correo=? LIMIT 1',[$d['correo']]);
                if ($coincide) throw new DomainException('Ya existe un cliente con ese correo (#'.$coincide['id'].'). Búscalo antes de registrarlo.');
            }
            $parametros = [$d['nombre'],$d['apellidoP'],$d['apellidoM'],$d['apellidoM']];
            $sql = "SELECT id,telefono FROM clientes WHERE nombre=? AND apellidoP=? AND (? IS NULL OR apellidoM IS NULL OR apellidoM='' OR apellidoM=?)";
            if ($d['telefono'] !== null) {
                $sql .= ' AND telefono=?'; $parametros[]=$d['telefono'];
            } else {
                $sql .= ' AND (telefono IS NULL OR telefono=\'\')';
            }
            $sql .= ' ORDER BY id DESC LIMIT 1';
            $coincide = $this->uno($db,$sql,$parametros);
            if ($coincide) {
                throw new DomainException('Hay un cliente con ese nombre y teléfono (#'.$coincide['id'].'). Selecciónalo en el buscador.');
            }
            $q = $db->prepare("INSERT INTO clientes
                (nombre, apellidoP, apellidoM, correo, clave, telefono, origen, observaciones_pos, token, `verify`)
                VALUES (?, ?, ?, ?, NULL, ?, 'POS', ?, NULL, 0)");
            $q->execute([$d['nombre'],$d['apellidoP'],$d['apellidoM'],$d['correo'],$d['telefono'],$d['observaciones_pos']]);
            $id = (int)$db->lastInsertId();
            $db->commit();
            return ['id'=>$id,'nombre'=>$d['nombre'],'apellidoP'=>$d['apellidoP'],
                'apellidoM'=>$d['apellidoM'],'correo'=>$d['correo'],'telefono'=>$d['telefono'],
                'origen'=>'POS','mensaje'=>'Cliente registrado y seleccionado.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        } finally {
            try { $db->query("SELECT RELEASE_LOCK('ivc_pos_alta_cliente')"); }
            catch (Throwable $e) { error_log('POS: no se pudo liberar candado alta cliente: '.$e->getMessage()); }
        }
    }

    public function micasVenta(): array
    {
        return PosConfiguracionOptica::catalogo($this->db());
    }

    public function recetasClienteVenta(int $cliente): array
    {
        return PosConfiguracionOptica::recetas($this->db(), $cliente);
    }

    /**
     * Venta en preparación. Una línea óptica es exactamente 1 armazón + 1 par de micas.
     * Se admiten varias líneas del mismo armazón, con distintas recetas/micas;
     * el límite de stock se verifica sobre la SUMA de todas las líneas.
     * No se reserva, no se cobra, no se inserta pedido, ni se altera inventario.
     */
    public function previsualizarVenta($items, $porcentaje, $cliente, bool $puedeDescontar): array
    {
        return $this->cotizarEn($this->db(),$items,$porcentaje,$cliente,$puedeDescontar);
    }

    private function cotizarEn(PDO $db, $items, $porcentaje, $cliente, bool $puedeDescontar): array
    {
        if (!is_array($items) || !array_is_list($items) || count($items)<1 || count($items)>40) {
            throw new InvalidArgumentException('Selecciona entre 1 y 40 renglones de venta.');
        }
        if (!is_string($porcentaje) || !preg_match('/^(?:0|[1-9][0-9]?|100)$/D',$porcentaje)) {
            throw new InvalidArgumentException('Descuento inválido (0–100 %).');
        }
        $descuento = (int)$porcentaje;
        if ($descuento>0 && !$puedeDescontar) throw new DomainException('No tienes permisos para aplicar descuentos.');
        if (!is_string($cliente) || !preg_match('/^(?:0|[1-9][0-9]{0,9})$/D',$cliente)) {
            throw new InvalidArgumentException('Cliente inválido.');
        }
        $clienteId=(int)$cliente;
        if ($clienteId>0 && !$this->uno($db,'SELECT id FROM clientes WHERE id=?',[$clienteId])) {
            throw new InvalidArgumentException('El cliente seleccionado ya no existe.');
        }
        $lineas=[];$porProducto=[];$preciosProducto=[];$subtotalArmazon=0;$subtotalMicas=0;$unidades=0;
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['id'],$item['cantidad']) ||
                !is_int($item['id']) || !is_int($item['cantidad']) ||
                $item['id']<1 || $item['cantidad']<1 || $item['cantidad']>99) {
                throw new InvalidArgumentException('Carrito con artículos o cantidades inválidos.');
            }
            $id=$item['id'];$cantidad=$item['cantidad'];
            $p=$preciosProducto[$id]??null;
            if (!$p) {
                $p=$this->uno($db,'SELECT id,nombre,precio,cantidad,estado FROM productos WHERE id=?',[$id]);
                if (!$p || (int)$p['estado']!==1) {
                    throw new DomainException('Uno de los productos ya no está disponible.');
                }
                $preciosProducto[$id]=$p;
            }
            $porProducto[$id]=($porProducto[$id]??0)+$cantidad;
            if ($porProducto[$id]>99 || $porProducto[$id]>(int)$p['cantidad']) {
                throw new DomainException('Existencias insuficientes para producto #'.$id.'. Disponibles: '.(int)$p['cantidad']);
            }
            $precio=$this->centavos((string)$p['precio']);
            $config=array_key_exists('optica',$item)?$item['optica']:null;
            $optica=PosConfiguracionOptica::validar($db,$config,$clienteId,$cantidad);
            $precioMica=$optica?$this->centavos($optica['precio_mica']):0;
            $subtotalArmazon+=$precio*$cantidad;
            $subtotalMicas+=$precioMica*$cantidad;
            $unidades+=$cantidad;
            $lineas[]=[
                'id'=>$id,'nombre'=>(string)$p['nombre'],'cantidad'=>$cantidad,
                'precio'=>$this->decimal($precio),
                'precio_mica'=>$this->decimal($precioMica),
                'importe'=>$this->decimal(($precio+$precioMica)*$cantidad),
                'disponibles'=>(int)$p['cantidad'],'optica'=>$optica,
            ];
        }
        $subtotal=$subtotalArmazon+$subtotalMicas;
        if ($subtotal>9999999999) throw new InvalidArgumentException('El total excede el rango admitido para un pedido.');
        $descuentoCents=intdiv($subtotal*$descuento+50,100);
        return [
            'lineas'=>$lineas,'cliente_id'=>$clienteId?:null,'unidades'=>$unidades,
            'subtotal_armazones'=>$this->decimal($subtotalArmazon),
            'subtotal_micas'=>$this->decimal($subtotalMicas),
            'subtotal'=>$this->decimal($subtotal),
            'descuento_porcentaje'=>$descuento,
            'descuento_importe'=>$this->decimal($descuentoCents),
            'total'=>$this->decimal($subtotal-$descuentoCents),
            'requiere_seguimiento_optico'=>$subtotalMicas>0,
            'observacion'=>'Previsualización sin reserva ni cobro. No reemplaza la revisión profesional de una receta. El precio y el inventario se recalcularán al cobrar.',
        ];
    }

    /**
     * Registro sin doble cobro lógico: transacción única (pedido + detalles + POS + pagos + stock + caja).
     * La terminal física se cobra FUERA del sistema; si falla el registro, conciliar SIN repetir el cargo.
     */
    public function registrarVenta(int $usuario, array $datos, bool $puedeDescontar): array
    {
        $solicitud=$datos['solicitud']??null;
        if (!is_string($solicitud) || !preg_match('/\A[a-f0-9]{32}\z/D',$solicitud)) {
            throw new InvalidArgumentException('Solicitud de venta inválida. Vuelve a abrir la ventana de cobro.');
        }
        $items=$datos['items']??null;
        if (!is_array($items) || !array_is_list($items) || count($items)<1 || count($items)>40) {
            throw new InvalidArgumentException('Carrito inválido.');
        }
        $cliente=(string)($datos['cliente']??'0');
        $descuento=(string)($datos['descuento']??'0');
        $esperado=$datos['total_esperado']??null;
        if (!is_string($esperado)) throw new InvalidArgumentException('Valida el carrito antes de cobrar.');
        $totalEsperado=PosCobros::cents($esperado);
        $huella=hash('sha256',json_encode([$items,$cliente,$descuento,
            $datos['forma']??null,$datos['tarjeta_importe']??null,
            $datos['efectivo_recibido']??null,$datos['proveedor']??null,
            $datos['referencia_terminal']??null],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));

        // Primer chequeo sin locks: una respuesta perdida NO exige volver a cobrar terminal.
        $db=$this->db();$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $repetida=$this->ventaRepetida($db,$solicitud,$huella);
        if ($repetida) return $repetida;
        $db->beginTransaction();
        try {
            $turno=$this->turno($db,$usuario,true);
            if (!$turno) throw new DomainException('No tienes una caja abierta. No se registró la venta.');
            // IDs estables y orden ascendente compartido con el checkout web.
            $unidades=[];
            foreach ($items as $item) {
                if (!is_array($item)||!isset($item['id'],$item['cantidad'])||!is_int($item['id'])||!is_int($item['cantidad'])
                    ||$item['id']<1||$item['cantidad']<1||$item['cantidad']>99) {
                    throw new InvalidArgumentException('Los artículos del carrito no son válidos.');
                }
                $id=$item['id'];$unidades[$id]=($unidades[$id]??0)+$item['cantidad'];
                if ($unidades[$id]>99) throw new InvalidArgumentException('Cantidad excesiva para un mismo producto.');
            }
            ksort($unidades,SORT_NUMERIC);
            // Bloquear catálogo óptico y recetas para que no cambien durante la venta.
            // Los IDs se ordenan para reducir riesgo de interbloqueos.
            $idsMica=[];$idsReceta=[];
            foreach ($items as $i) {
                $op=$i['optica']??null;
                if (!is_array($op)) continue;
                if (isset($op['id_mica']) && is_int($op['id_mica']) && $op['id_mica']>0) $idsMica[$op['id_mica']]=true;
                if (isset($op['id_receta']) && is_int($op['id_receta']) && $op['id_receta']>0) $idsReceta[$op['id_receta']]=true;
            }
            ksort($idsMica,SORT_NUMERIC);ksort($idsReceta,SORT_NUMERIC);
            $stock=[];
            foreach ($unidades as $id=>$cant) {
                $r=$this->uno($db,'SELECT id,cantidad,estado FROM productos WHERE id=? FOR UPDATE',[$id]);
                if (!$r || (int)$r['estado']!==1 || (int)$r['cantidad']<$cant) {
                    throw new DomainException('Existencias insuficientes del producto #'.$id.'. No se registró la venta.');
                }
                $stock[$id]=(int)$r['cantidad'];
            }
            foreach (array_keys($idsMica) as $idMica) {
                if (!$this->uno($db,'SELECT id_mica FROM catalogo_micas WHERE id_mica=? FOR UPDATE',[$idMica])) {
                    throw new DomainException('La mica seleccionada ya no está disponible.');
                }
            }
            foreach (array_keys($idsReceta) as $idReceta) {
                if (!$this->uno($db,'SELECT id_receta FROM recetas_opticas WHERE id_receta=? FOR UPDATE',[$idReceta])) {
                    throw new DomainException('La receta seleccionada ya no existe.');
                }
            }
            $cot=$this->cotizarEn($db,$items,$descuento,$cliente,$puedeDescontar);
            $total=PosCobros::cents($cot['total']);
            if ($total!==$totalEsperado) {
                throw new DomainException('El importe cambió. Vuelve a validar el carrito antes de cobrar o registrar la operación.');
            }
            $pago=PosCobros::validar($datos,$total);
            if ($pago['tarjeta']>0) {
                $anterior=$this->uno($db,"SELECT id_pago FROM pos_pagos WHERE proveedor=? AND referencia_terminal=? LIMIT 1",
                    [$pago['proveedor'],$pago['referencia']]);
                if ($anterior) throw new DomainException('Esa autorización de terminal ya fue registrada. Verifica el historial; NO repitas el cobro.');
            }
            $idCliente=$cot['cliente_id'];
            $email='';$nombre='VENTA';$apellido='MOSTRADOR';
            if ($idCliente) {
                $c=$this->uno($db,'SELECT nombre,apellidoP,apellidoM,correo FROM clientes WHERE id=? FOR UPDATE',[$idCliente]);
                if (!$c) throw new DomainException('El cliente ya no existe.');
                if (strlen((string)($c['correo']??''))>80) throw new DomainException('El correo supera los 80 caracteres del campo histórico pedidos.email.');
                $email=(string)($c['correo']??'');
                $nombre=(function_exists('mb_substr') ? mb_substr((string)$c['nombre'],0,80,'UTF-8') : substr((string)$c['nombre'],0,80));
                $apellido=(function_exists('mb_substr') ? mb_substr(trim((string)$c['apellidoP'].' '.(string)($c['apellidoM']??'')),0,80,'UTF-8') : substr(trim((string)$c['apellidoP'].' '.(string)($c['apellidoM']??'')),0,80));
            }
            $fecha=$this->ahora();
            $stmt=$db->prepare("INSERT INTO pedidos (id_transaccion,monto,estado,fecha,email,nombre,apellido,id_cliente,proceso)
                VALUES (?,?,'COMPLETED',?,?,?, ?,?,'1')");
            $stmt->execute(['POS-'.$solicitud,$cot['total'],substr($fecha,0,10),$email,$nombre,$apellido,$idCliente]);
            $idPedido=(int)$db->lastInsertId();
            if ($idPedido<1) throw new RuntimeException('No se pudo crear el pedido.');
            $stmt=$db->prepare("INSERT INTO pos_ventas
                (id_pedido,id_turno,id_vendedor,creada_en,estado,solicitud,solicitud_hash,subtotal,descuento,total,efectivo_recibido,cambio)
                VALUES (?,?,?,?,'PAGADA',?,?,?,?,?,?,?)");
            $stmt->execute([$idPedido,(int)$turno['id_turno'],$usuario,$fecha,$solicitud,$huella,
                $cot['subtotal'],$cot['descuento_importe'],$cot['total'],
                PosCobros::money($pago['recibido']),PosCobros::money($pago['cambio'])]);
            $idVenta=(int)$db->lastInsertId();
            $detalle=$db->prepare('INSERT INTO detalle_pedidos (producto,precio,cantidad,id_pedido,id_producto) VALUES (?,?,?,?,?)');
            $mica=$db->prepare('INSERT INTO detalle_pedido_micas (id_detalle_pedido,id_mica,id_receta,nombre_mica_snapshot,precio_unitario_mica,estado_optico) VALUES (?,?,?,?,?,?)');
            $auditar=$db->prepare("INSERT INTO pos_inventario_movimientos (id_pedido,id_detalle_pedido,id_producto,tipo,cantidad,stock_antes,stock_despues,registrado_en)
                VALUES (?,?,?,'VENTA',?,?,?,?)");
            foreach ($cot['lineas'] as $linea) {
                $id=$linea['id'];$cant=$linea['cantidad'];
                $detalle->execute([$linea['nombre'],$linea['precio'],$cant,$idPedido,$id]);
                $idDetalle=(int)$db->lastInsertId();
                if ($idDetalle<1) throw new RuntimeException('No se registró el detalle.');
                if ($linea['optica']!==null) {
                    $op=$linea['optica'];
                    $mica->execute([$idDetalle,$op['id_mica'],$op['id_receta'],
                        $op['nombre_mica'],$op['precio_mica'],$op['estado_optico']]);
                }
                $despues=$stock[$id]-$cant;
                $auditar->execute([$idPedido,$idDetalle,$id,$cant,$stock[$id],$despues,$fecha]);
                $stock[$id]=$despues;
            }
            $restar=$db->prepare('UPDATE productos SET cantidad=cantidad-? WHERE id=? AND estado=1 AND cantidad>=?');
            foreach ($unidades as $id=>$cant) {
                $restar->execute([$cant,$id,$cant]);
                if ($restar->rowCount()!==1) throw new DomainException('Inventario modificado durante la venta. No se registró ningún cambio.');
            }
            $pagoStmt=$db->prepare("INSERT INTO pos_pagos(id_venta,metodo,importe,referencia_terminal,proveedor,estado,registrado_en)
                VALUES (?,?,?,?,?,'APROBADO',?)");
            if ($pago['efectivo']>0) {
                $pagoStmt->execute([$idVenta,'EFECTIVO',PosCobros::money($pago['efectivo']),null,null,$fecha]);
                $mov=$db->prepare("INSERT INTO pos_movimientos_caja (id_turno,id_usuario,id_pedido,tipo,importe,motivo,creado_en)
                    VALUES (?,?,?,'VENTA',?,'Venta POS en efectivo',?)");
                $mov->execute([(int)$turno['id_turno'],$usuario,$idPedido,PosCobros::money($pago['efectivo']),$fecha]);
            }
            if ($pago['tarjeta']>0) {
                $pagoStmt->execute([$idVenta,'TARJETA',PosCobros::money($pago['tarjeta']),$pago['referencia'],$pago['proveedor'],$fecha]);
            }
            $db->commit();
            return ['id_pedido'=>$idPedido,'id_venta'=>$idVenta,'total'=>$cot['total'],
                'efectivo'=>PosCobros::money($pago['efectivo']),'tarjeta'=>PosCobros::money($pago['tarjeta']),
                'cambio'=>PosCobros::money($pago['cambio']),'ya_existia'=>false,
                'seguimiento_optico'=>$cot['requiere_seguimiento_optico']];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Colisión concurrente de la MISMA solicitud: se devuelve el pedido original.
            if ($e instanceof PDOException && $e->getCode()==='23000') {
                $previo=$this->ventaRepetida($db,$solicitud,$huella);
                if ($previo) return $previo;
                throw new DomainException('Conflicto de referencia de pago o registro. NO vuelvas a cobrar la tarjeta; solicita conciliación.');
            }
            throw $e;
        }
    }

    private function ventaRepetida(PDO $db,string $solicitud,string $huella): ?array
    {
        $previa=$this->uno($db,'SELECT v.id_venta,v.id_pedido,v.total,v.solicitud_hash
            FROM pos_ventas v WHERE v.solicitud=? LIMIT 1',[$solicitud]);
        if (!$previa) return null;
        if (!hash_equals((string)$previa['solicitud_hash'],$huella)) {
            throw new DomainException('Este identificador de venta ya se utilizó con información distinta. Revisa el historial.');
        }
        return ['id_pedido'=>(int)$previa['id_pedido'],'id_venta'=>(int)$previa['id_venta'],
            'total'=>(string)$previa['total'],'ya_existia'=>true,
            'mensaje'=>'La venta ya estaba registrada. No se descontó inventario nuevamente.'];
    }

    public function ultimosTurnos(): array
    {
        return $this->varios($this->db(),"SELECT t.id_turno,c.codigo AS caja,
            CONCAT(u.nombres,' ',u.apellidos) AS responsable,t.apertura_en,t.cierre_en,
            t.estado,t.fondo_inicial,t.efectivo_esperado,t.efectivo_contado,t.diferencia
            FROM pos_turnos t JOIN pos_cajas c ON c.id_caja=t.id_caja JOIN usuario u ON u.id=t.id_usuario
            ORDER BY t.id_turno DESC LIMIT 50");
    }
}
