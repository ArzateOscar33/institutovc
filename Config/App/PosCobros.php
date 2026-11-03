<?php
/** POS 4.9.5.4. Validación de importes exactos y cobro manual en terminal externa. */
final class PosCobros
{
    public static function cents($s): int
    {
        if (!is_string($s) || !preg_match('/\A(?:0|[1-9][0-9]{0,7})(?:\.([0-9]{1,2}))?\z/D', $s, $m)) {
            throw new InvalidArgumentException('Importe inválido: usa pesos y hasta dos decimales.');
        }
        [$int] = explode('.', $s);
        return (int)$int * 100 + (int)str_pad($m[1] ?? '', 2, '0');
    }
    public static function money(int $c): string
    {
        return ($c < 0 ? '-' : '') . intdiv(abs($c),100) . '.' . str_pad((string)(abs($c)%100),2,'0',STR_PAD_LEFT);
    }
    /**
     * El dinero recibido incluye cambio: sólo entra en caja el importe de la venta.
     * La tarjeta se confirma manualmente fuera del sistema; NO se capturan datos de tarjeta.
     */
    public static function validar(array $datos, int $total): array
    {
        if ($total <= 0 || $total > 9999999999) throw new DomainException('El total debe ser mayor a cero y estar dentro del rango admitido.');
        $forma=$datos['forma'] ?? null;
        if (!is_string($forma) || !in_array($forma,['EFECTIVO','TARJETA','MIXTO'],true)) {
            throw new InvalidArgumentException('Forma de pago incorrecta.');
        }
        $tarjeta=self::cents($datos['tarjeta_importe'] ?? '0');
        $recibido=self::cents($datos['efectivo_recibido'] ?? '0');
        if ($forma==='EFECTIVO' && $tarjeta!==0) throw new InvalidArgumentException('Una venta en efectivo no registra tarjeta.');
        if ($forma==='TARJETA' && $tarjeta!==$total) throw new InvalidArgumentException('El importe con tarjeta debe coincidir con el total.');
        if ($forma==='MIXTO' && ($tarjeta<1 || $tarjeta>=$total)) throw new InvalidArgumentException('El pago mixto exige tarjeta y efectivo mayores a cero.');
        $efectivo=$total-$tarjeta;
        if ($forma==='TARJETA' && $recibido!==0) throw new InvalidArgumentException('Un cobro exclusivo con tarjeta no recibe efectivo.');
        if ($recibido<$efectivo) throw new DomainException('El efectivo recibido es insuficiente.');
        $proveedor=null; $referencia=null;
        if ($tarjeta>0) {
            if (($datos['confirmacion_terminal'] ?? null)!=='1') throw new DomainException('Confirma que la terminal física ya aprobó el cobro.');
            $proveedor=trim((string)($datos['proveedor'] ?? ''));
            $referencia=trim((string)($datos['referencia_terminal'] ?? ''));
            if (!preg_match('/\A[A-Za-z0-9][A-Za-z0-9 ._-]{1,69}\z/D',$proveedor)) throw new InvalidArgumentException('Indica el proveedor de la terminal (2–70 caracteres).');
            if (!preg_match('/\A[A-Za-z0-9][A-Za-z0-9._\/-]{3,79}\z/D',$referencia)) throw new InvalidArgumentException('Ingresa la referencia de autorización de la terminal (4–80 caracteres).');
            $proveedor=strtoupper($proveedor);$referencia=strtoupper($referencia);
        } elseif (trim((string)($datos['referencia_terminal'] ?? ''))!=='' || trim((string)($datos['proveedor'] ?? ''))!=='') {
            throw new InvalidArgumentException('Los datos de terminal sólo corresponden a pagos con tarjeta.');
        }
        return ['forma'=>$forma,'efectivo'=>$efectivo,'tarjeta'=>$tarjeta,
            'recibido'=>$recibido,'cambio'=>$recibido-$efectivo,
            'proveedor'=>$proveedor,'referencia'=>$referencia];
    }
}
