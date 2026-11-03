<?php
/** IVC 4.9.5.5: lectura de ventas POS y devoluciones auditadas; no modifica pedidos web. */
final class PosPostventa
{
    private PDO $db;

    public function __construct()
    {
        $this->db=(new Conexion())->conect();
        if (!$this->db) throw new RuntimeException('No hay conexión con MySQL.');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    private static function cents($str): int
    {
        if (!is_string($str) && !is_int($str)) throw new InvalidArgumentException('Importe inválido.');
        $str=(string)$str;
        if (!preg_match('/\A(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?\z/D',$str,$m)) throw new InvalidArgumentException('Importe monetario inválido.');
        return (int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
    }
    private static function money(int $n): string
    {
        return ($n<0?'-':'').intdiv(abs($n),100).'.'.str_pad((string)(abs($n)%100),2,'0',STR_PAD_LEFT);
    }
    private function one(string $sql,array $args=[]): ?array
    {
        $q=$this->db->prepare($sql);$q->execute($args);return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }
    private function all(string $sql,array $args=[]): array
    {
        $q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    private function fecha(string $fecha): string
    {
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
        if (!$d || $d->format('Y-m-d')!==$fecha) throw new InvalidArgumentException('Fecha incorrecta.');
        return $fecha;
    }
    public function historial(int $usuario,bool $todas,array $filtros): array
    {
        $desde=$this->fecha((string)($filtros['desde']??(new DateTimeImmutable('-30 days'))->format('Y-m-d')));
        $hasta=$this->fecha((string)($filtros['hasta']??date('Y-m-d')));
        if ($desde>$hasta || strtotime($hasta)-strtotime($desde)>366*86400) throw new InvalidArgumentException('Selecciona un rango máximo de 366 días.');
        $pagina=filter_var($filtros['pagina']??1,FILTER_VALIDATE_INT);
        if (!$pagina || $pagina<1 || $pagina>500) throw new InvalidArgumentException('Página inválida.');
        $q=trim((string)($filtros['q']??''));
        if (strlen($q)>70) throw new InvalidArgumentException('Búsqueda demasiado larga.');
        $where=['v.creada_en >= ?','v.creada_en < DATE_ADD(?,INTERVAL 1 DAY)'];$args=[$desde,$hasta];
        if (!$todas) {$where[]='v.id_vendedor=?';$args[]=$usuario;}
        if ($q!=='') {
            $where[]="(CAST(v.id_venta AS CHAR)=? OR CAST(v.id_pedido AS CHAR)=? OR CONCAT(p.nombre,' ',p.apellido) LIKE ?)";
            $args[]=$q;$args[]=$q;$args[]='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$q).'%';
        }
        $whereSql=implode(' AND ',$where);
        $rows=$this->all("SELECT v.id_venta,v.id_pedido,v.total,v.estado,v.creada_en,
            CONCAT(u.nombres,' ',u.apellidos) AS vendedor,pc.nombre AS caja,
            CONCAT(p.nombre,' ',p.apellido) AS cliente,
            COALESCE((SELECT SUM(d.total) FROM pos_devoluciones d WHERE d.id_venta=v.id_venta),0) AS reembolsado
            FROM pos_ventas v JOIN usuario u ON u.id=v.id_vendedor JOIN pos_turnos t ON t.id_turno=v.id_turno
            JOIN pos_cajas pc ON pc.id_caja=t.id_caja JOIN pedidos p ON p.id=v.id_pedido
            WHERE $whereSql ORDER BY v.id_venta DESC LIMIT 21 OFFSET ".(($pagina-1)*20),$args);
        $hayMas=count($rows)>20;
        if ($hayMas) array_pop($rows);
        return ['ventas'=>$rows,'pagina'=>(int)$pagina,'hay_mas'=>$hayMas,'desde'=>$desde,'hasta'=>$hasta];
    }
    private function venta(int $id,int $usuario,bool $todas,bool $lock=false): array
    {
        if ($id<1) throw new InvalidArgumentException('Venta inválida.');
        $sql="SELECT v.*, p.id_cliente,p.nombre AS cliente_nombre,p.apellido AS cliente_apellido,
            u.nombres AS vendedor_nombre,u.apellidos AS vendedor_apellido,
            c.codigo AS caja_codigo,c.nombre AS caja_nombre
            FROM pos_ventas v JOIN pedidos p ON p.id=v.id_pedido
            JOIN usuario u ON u.id=v.id_vendedor
            JOIN pos_turnos t ON t.id_turno=v.id_turno JOIN pos_cajas c ON c.id_caja=t.id_caja
            WHERE v.id_venta=?";
        $args=[$id];if (!$todas) {$sql.=' AND v.id_vendedor=?';$args[]=$usuario;}
        if ($lock) $sql.=' FOR UPDATE';
        $v=$this->one($sql,$args);
        if (!$v) throw new DomainException('Venta no encontrada o sin permiso para consultarla.');
        return $v;
    }
    /** Reparte centavos netos exactos entre renglones. No recalcula precios de catálogo. */
    private function lineas(array $venta): array
    {
        $lineas=$this->all("SELECT d.id,d.id_producto,d.producto,d.cantidad,d.precio,
            m.nombre_mica_snapshot,m.precio_unitario_mica,m.estado_optico,
            COALESCE((SELECT SUM(dd.cantidad) FROM pos_devolucion_detalles dd
              JOIN pos_devoluciones dv ON dv.id_devolucion=dd.id_devolucion
              WHERE dd.id_detalle_pedido=d.id AND dv.id_venta=?),0) AS devueltas,
            COALESCE((SELECT SUM(dd.importe) FROM pos_devolucion_detalles dd
              JOIN pos_devoluciones dv ON dv.id_devolucion=dd.id_devolucion
              WHERE dd.id_detalle_pedido=d.id AND dv.id_venta=?),0) AS reembolsado
            FROM detalle_pedidos d LEFT JOIN detalle_pedido_micas m ON m.id_detalle_pedido=d.id
            WHERE d.id_pedido=? ORDER BY d.id",[(int)$venta['id_venta'],(int)$venta['id_venta'],(int)$venta['id_pedido']]);
        $grossTotal=0;
        foreach ($lineas as &$l) {
            $q=(int)$l['cantidad'];if ($q<1) throw new DomainException('Detalle con cantidad inválida.');
            $gross=(self::cents($l['precio'])+self::cents((string)($l['precio_unitario_mica']??'0')))*$q;
            $l['bruto_centavos']=$gross;$grossTotal+=$gross;
            $l['devueltas']=(int)$l['devueltas'];
        } unset($l);
        $subtotal=self::cents((string)$venta['subtotal']);
        $net=self::cents((string)$venta['total']);
        if (!$lineas || $grossTotal!==$subtotal || $grossTotal===0 || $net>$grossTotal) {
            throw new DomainException('Los importes históricos no concuerdan. Requieren conciliación antes de una devolución.');
        }
        if ($grossTotal>2000000000) throw new DomainException('Importe fuera del rango de devolución automática; solicita conciliación.');
        $restos=[];$asignado=0;
        foreach ($lineas as $i=>&$l) {
            $producto=$l['bruto_centavos']*$net;
            $l['neto_centavos']=intdiv($producto,$grossTotal);
            $restos[]=['i'=>$i,'r'=>$producto%$grossTotal];
            $asignado+=$l['neto_centavos'];
        } unset($l);
        usort($restos,static fn($a,$b)=>($b['r']<=>$a['r']) ?: ($a['i']<=>$b['i']));
        for($i=0;$i<$net-$asignado;$i++) $lineas[$restos[$i]['i']]['neto_centavos']++;
        foreach ($lineas as &$l) {
            $l['neto_linea']=self::money($l['neto_centavos']);
            $l['disponibles_devolucion']=(int)$l['cantidad']-$l['devueltas'];
            $l['reembolsado_centavos']=self::cents((string)$l['reembolsado']);
        } unset($l);
        return $lineas;
    }
    public function detalle(int $venta,int $usuario,bool $todas): array
    {
        $v=$this->venta($venta,$usuario,$todas);
        $lineas=$this->lineas($v);
        $pagos=$this->all('SELECT metodo,importe,proveedor,referencia_terminal FROM pos_pagos WHERE id_venta=? ORDER BY id_pago',[$venta]);
        $devoluciones=$this->all('SELECT d.id_devolucion,d.total,d.motivo,d.creada_en,
            CONCAT(u.nombres,\' \',u.apellidos) AS responsable FROM pos_devoluciones d
            JOIN usuario u ON u.id=d.id_usuario WHERE d.id_venta=? ORDER BY d.id_devolucion DESC',[$venta]);
        $reembolsado=0;foreach($devoluciones as $d) $reembolsado+=self::cents($d['total']);
        $metodos=['EFECTIVO'=>0,'TARJETA'=>0];$devMetodos=['EFECTIVO'=>0,'TARJETA'=>0];
        foreach($pagos as $p)$metodos[$p['metodo']]+=self::cents($p['importe']);
        foreach($this->all('SELECT dp.metodo,SUM(dp.importe) AS importe FROM pos_devolucion_pagos dp
            JOIN pos_devoluciones d ON d.id_devolucion=dp.id_devolucion WHERE d.id_venta=? GROUP BY dp.metodo',[$venta]) as $p) {
            $devMetodos[$p['metodo']]=self::cents($p['importe']);
        }
        return ['venta'=>$v,'lineas'=>$lineas,'pagos'=>$pagos,'devoluciones'=>$devoluciones,
            'reembolsado'=>self::money($reembolsado),'pendiente'=>self::money(self::cents($v['total'])-$reembolsado),
            'disponible_efectivo'=>self::money($metodos['EFECTIVO']-$devMetodos['EFECTIVO']),
            'disponible_tarjeta'=>self::money($metodos['TARJETA']-$devMetodos['TARJETA'])];
    }
    /** Total que corresponde a las unidades acumuladas devueltas (no redondea cada devolución). */
    private static function acumulado(int $neto,int $unidades,int $devueltas): int
    {
        return intdiv($neto*$devueltas+intdiv($unidades,2),$unidades);
    }
    public function devolver(int $usuario,array $input): array
    {
        $sol=(string)($input['solicitud']??'');
        if (!preg_match('/\A[a-f0-9]{32}\z/D',$sol)) throw new InvalidArgumentException('Solicitud de devolución inválida.');
        $venta=filter_var($input['id_venta']??null,FILTER_VALIDATE_INT);
        if (!$venta || $venta<1) throw new InvalidArgumentException('Selecciona una venta.');
        $detalle=$input['lineas']??null;
        if (!is_array($detalle) || !array_is_list($detalle) || count($detalle)<1 || count($detalle)>40) throw new InvalidArgumentException('Selecciona al menos un artículo.');
        $razon=trim((string)($input['motivo']??''));
        $largo=function_exists('mb_strlen')?mb_strlen($razon,'UTF-8'):strlen($razon);
        if ($largo<8 || $largo>255) throw new InvalidArgumentException('Especifica un motivo de 8 a 255 caracteres.');
        $cash=self::cents((string)($input['efectivo']??''));
        $card=self::cents((string)($input['tarjeta']??''));
        $proveedor=trim((string)($input['proveedor']??''));
        $referencia=trim((string)($input['referencia_reembolso']??''));
        if ($card>0 && (($input['tarjeta_confirmada']??null)!=='1'||strlen($proveedor)<2||strlen($proveedor)>80||strlen($referencia)<4||strlen($referencia)>100)) {
            throw new InvalidArgumentException('Confirma la devolución REAL en terminal y registra proveedor y referencia (mínimo 4 caracteres).');
        }
        if ($card===0 && ($referencia!==''||$proveedor!=='')) throw new InvalidArgumentException('La referencia de terminal solo se utiliza si hay reembolso a tarjeta.');
        $hash=hash('sha256',json_encode([$venta,$detalle,$razon,$cash,$card,$proveedor,$referencia],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        $prev=$this->one('SELECT id_devolucion,id_venta,solicitud_hash,total FROM pos_devoluciones WHERE solicitud=?',[$sol]);
        if($prev) return $this->repetida($prev,$hash);
        $db=$this->db;$db->beginTransaction();
        try {
            // Bloquear el turno antes de cualquier operación para serializar arqueo y devoluciones.
            $turno=$this->one("SELECT t.id_turno,t.id_caja FROM pos_turnos t WHERE t.id_usuario=? AND t.estado='ABIERTO' ORDER BY t.id_turno DESC LIMIT 1 FOR UPDATE",[$usuario]);
            if (!$turno) throw new DomainException('Abre tu caja antes de registrar una devolución.');
            $v=$this->venta((int)$venta,$usuario,true,true);
            if (!in_array($v['estado'],['PAGADA','DEVUELTA_PARCIAL'],true)) throw new DomainException('Esta venta ya no admite devoluciones.');
            $lineas=$this->lineas($v);
            $index=[];foreach($lineas as $l)$index[(int)$l['id']]=$l;
            $seleccion=[];
            foreach($detalle as $d) {
                if (!is_array($d)||!isset($d['id'],$d['cantidad'])||!is_int($d['id'])||!is_int($d['cantidad'])||$d['cantidad']<1||$d['cantidad']>99||!array_key_exists($d['id'],$index)||isset($seleccion[$d['id']])) throw new InvalidArgumentException('Cantidad o artículo de devolución inválido.');
                $linea=$index[$d['id']];$cantidad=$d['cantidad'];
                if ($cantidad>$linea['disponibles_devolucion']) throw new DomainException('Este producto ya fue devuelto total o parcialmente. Actualiza el historial.');
                if ($linea['nombre_mica_snapshot']!==null && $cantidad!==$linea['disponibles_devolucion']) {
                    throw new DomainException('Los renglones con micas deben devolverse completos para cancelar su preparación óptica.');
                }
                $reponer=$d['reingresa_stock']??false;
                if (!is_bool($reponer)) throw new InvalidArgumentException('Confirmación de inventario inválida.');
                $antes=self::acumulado($linea['neto_centavos'],(int)$linea['cantidad'],$linea['devueltas']);
                $despues=self::acumulado($linea['neto_centavos'],(int)$linea['cantidad'],$linea['devueltas']+$cantidad);
                $importe=$despues-$antes;
                if ($importe<0 || $importe+self::cents($linea['reembolsado'])>$linea['neto_centavos']) throw new DomainException('El importe ya fue devuelto. Revisa el historial.');
                $seleccion[$d['id']]=['linea'=>$linea,'cantidad'=>$cantidad,'reponer'=>$reponer,'importe'=>$importe];
            }
            $total=array_sum(array_column($seleccion,'importe'));
            if($total<1||$cash+$card!==$total) throw new DomainException('La distribución de reembolso no coincide con el importe exacto calculado por MySQL.');
            $datos=$this->detalle((int)$venta,$usuario,true);
            if ($cash>self::cents($datos['disponible_efectivo'])||$card>self::cents($datos['disponible_tarjeta'])) throw new DomainException('El reembolso excede lo recibido originalmente por ese método.');
            if($card>0) {
                $orig=$this->one("SELECT proveedor,referencia_terminal FROM pos_pagos WHERE id_venta=? AND metodo='TARJETA' LIMIT 1",[$venta]);
                if (!$orig || !hash_equals((string)$orig['proveedor'],$proveedor)) throw new DomainException('Utiliza el mismo proveedor de la tarjeta original.');
                if ((string)$orig['referencia_terminal']===$referencia) throw new DomainException('La referencia de devolución debe ser distinta a la autorización de venta.');
                if($this->one('SELECT id_devolucion_pago FROM pos_devolucion_pagos WHERE proveedor=? AND referencia_reembolso=?',[$proveedor,$referencia])) throw new DomainException('Este reembolso de terminal ya está registrado. No lo vuelvas a procesar.');
            }
            if($cash>0) {
                $m=$this->one("SELECT COALESCE(SUM(CASE WHEN tipo IN ('INGRESO','VENTA') THEN importe ELSE -importe END),0) AS saldo FROM pos_movimientos_caja WHERE id_turno=?",[(int)$turno['id_turno']]);
                $fondo=$this->one('SELECT fondo_inicial FROM pos_turnos WHERE id_turno=?',[(int)$turno['id_turno']]);
                if (self::cents($fondo['fondo_inicial'])+($m['saldo']>=0?self::cents($m['saldo']):-self::cents(substr((string)$m['saldo'],1)))<$cash) throw new DomainException('No hay suficiente efectivo esperado en caja para entregar el reembolso.');
            }
            // Bloqueo ascendente de productos devueltos; el stock solo vuelve si fue inspeccionado.
            $productos=[];foreach ($seleccion as $s) if($s['reponer']) $productos[(int)$s['linea']['id_producto']]=true;
            ksort($productos,SORT_NUMERIC);
            $stock=[];
            foreach(array_keys($productos) as $p) {
                $r=$this->one('SELECT id,cantidad FROM productos WHERE id=? FOR UPDATE',[$p]);
                if (!$r) throw new DomainException('El producto ya no existe. Registra la devolución sin reingreso de stock.');
                $stock[$p]=(int)$r['cantidad'];
            }
            $fecha=(new DateTimeImmutable('now',new DateTimeZone('America/Tijuana')))->format('Y-m-d H:i:s');
            $q=$db->prepare('INSERT INTO pos_devoluciones(id_venta,id_turno,id_usuario,solicitud,solicitud_hash,motivo,total,creada_en) VALUES(?,?,?,?,?,?,?,?)');
            $q->execute([$venta,(int)$turno['id_turno'],$usuario,$sol,$hash,$razon,self::money($total),$fecha]);
            $idDev=(int)$db->lastInsertId();
            $qDet=$db->prepare('INSERT INTO pos_devolucion_detalles(id_devolucion,id_detalle_pedido,cantidad,importe,reingresa_stock,estado_optico_anterior) VALUES(?,?,?,?,?,?)');
            $qInv=$db->prepare("INSERT INTO pos_inventario_movimientos(id_pedido,id_detalle_pedido,id_producto,tipo,cantidad,stock_antes,stock_despues,registrado_en) VALUES(?,?,?,'DEVOLUCION',?,?,?,?)");
            foreach($seleccion as $idLinea=>$s) {
                $l=$s['linea'];$p=(int)$l['id_producto'];$qty=$s['cantidad'];
                $qDet->execute([$idDev,$idLinea,$qty,self::money($s['importe']),$s['reponer']?1:0,$l['estado_optico']]);
                if($s['reponer']) {
                    $antes=$stock[$p];$despues=$antes+$qty;
                    $db->prepare('UPDATE productos SET cantidad=? WHERE id=?')->execute([$despues,$p]);
                    $qInv->execute([(int)$v['id_pedido'],$idLinea,$p,$qty,$antes,$despues,$fecha]);
                    $stock[$p]=$despues;
                }
                if ($l['nombre_mica_snapshot']!==null && $qty===(int)$l['cantidad']-$l['devueltas']) {
                    $db->prepare("UPDATE detalle_pedido_micas SET estado_optico='CANCELADA',observaciones=CONCAT(COALESCE(observaciones,''),' | Devolución POS #',?) WHERE id_detalle_pedido=?")
                        ->execute([$idDev,$idLinea]);
                }
            }
            $qPago=$db->prepare('INSERT INTO pos_devolucion_pagos(id_devolucion,metodo,importe,proveedor,referencia_reembolso) VALUES(?,?,?,?,?)');
            if($cash>0) {
                $qPago->execute([$idDev,'EFECTIVO',self::money($cash),null,null]);
                $db->prepare("INSERT INTO pos_movimientos_caja(id_turno,id_usuario,id_pedido,tipo,importe,motivo,creado_en)
                    VALUES(?,?,?,'DEVOLUCION',?,?,?)")->execute([(int)$turno['id_turno'],$usuario,(int)$v['id_pedido'],self::money($cash),'Reembolso venta POS #'.$venta.' / devolución #'.$idDev,$fecha]);
            }
            if($card>0) $qPago->execute([$idDev,'TARJETA',self::money($card),$proveedor,$referencia]);
            $devTotal=$this->one('SELECT COALESCE(SUM(total),0) AS devuelto FROM pos_devoluciones WHERE id_venta=?',[$venta]);
            $nuevoEstado=self::cents($devTotal['devuelto'])===self::cents($v['total'])?'DEVUELTA_TOTAL':'DEVUELTA_PARCIAL';
            $db->prepare('UPDATE pos_ventas SET estado=? WHERE id_venta=?')->execute([$nuevoEstado,$venta]);
            // Una autorización de venta sigue histórica; únicamente marcar pagos totalmente reembolsados.
            foreach ($this->all('SELECT metodo, SUM(importe) AS total FROM pos_pagos WHERE id_venta=? GROUP BY metodo',[$venta]) as $original) {
                $reembolsos=$this->one('SELECT COALESCE(SUM(dp.importe),0) AS total FROM pos_devolucion_pagos dp JOIN pos_devoluciones d ON d.id_devolucion=dp.id_devolucion WHERE d.id_venta=? AND dp.metodo=?',[$venta,$original['metodo']]);
                if (self::cents($original['total'])===self::cents($reembolsos['total'])) {
                    $db->prepare("UPDATE pos_pagos SET estado='REEMBOLSADO' WHERE id_venta=? AND metodo=?")
                        ->execute([$venta,$original['metodo']]);
                }
            }
            $db->commit();
            return ['id_devolucion'=>$idDev,'id_venta'=>(int)$venta,'total'=>self::money($total),'estado'=>$nuevoEstado,'ya_existia'=>false];
        } catch(Throwable $e) {
            if($db->inTransaction())$db->rollBack();
            if ($e instanceof PDOException && $e->getCode()==='23000') {
                $prev=$this->one('SELECT id_devolucion,id_venta,solicitud_hash,total FROM pos_devoluciones WHERE solicitud=?',[$sol]);
                if($prev) return $this->repetida($prev,$hash);
                throw new DomainException('Referencia ya registrada o conflicto de operación. Conciliar antes de reintentar el reembolso.');
            }
            throw $e;
        }
    }
    private function repetida(array $d,string $hash): array
    {
        if (!hash_equals((string)$d['solicitud_hash'],$hash)) throw new DomainException('La clave de devolución ya se utilizó con otros datos.');
        return ['id_devolucion'=>(int)$d['id_devolucion'],'id_venta'=>(int)$d['id_venta'],'total'=>(string)$d['total'],'ya_existia'=>true];
    }
    public function comprobanteDevolucion(int $id,int $usuario,bool $todas): array
    {
        $dev=$this->one('SELECT dv.*,v.id_pedido,v.id_vendedor,p.nombre,p.apellido,
            CONCAT(u.nombres,\' \',u.apellidos) AS responsable FROM pos_devoluciones dv
            JOIN pos_ventas v ON v.id_venta=dv.id_venta JOIN pedidos p ON p.id=v.id_pedido
            JOIN usuario u ON u.id=dv.id_usuario WHERE dv.id_devolucion=?',[$id]);
        if (!$dev || (!$todas && (int)$dev['id_vendedor']!==$usuario)) throw new DomainException('Comprobante no disponible.');
        $items=$this->all('SELECT dd.*,d.producto,m.nombre_mica_snapshot FROM pos_devolucion_detalles dd
            JOIN detalle_pedidos d ON d.id=dd.id_detalle_pedido
            LEFT JOIN detalle_pedido_micas m ON m.id_detalle_pedido=d.id WHERE dd.id_devolucion=? ORDER BY dd.id_devolucion_detalle',[$id]);
        $pagos=$this->all('SELECT metodo,importe,proveedor,referencia_reembolso FROM pos_devolucion_pagos WHERE id_devolucion=? ORDER BY id_devolucion_pago',[$id]);
        return ['devolucion'=>$dev,'lineas'=>$items,'pagos'=>$pagos];
    }
}
