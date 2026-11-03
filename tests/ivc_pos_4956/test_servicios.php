<?php
/** Pruebas puras POS 4.9.5.6: NUNCA accede a MySQL ni altera datos. */
if (PHP_SAPI!=='cli') {http_response_code(403);exit;}
error_reporting(E_ALL);
$root=dirname(__DIR__,2);
foreach (['Config/App/PosCobros.php','Config/App/PosClientes.php','Models/PosPostventa.php'] as $p) {
    if (!is_file($root.'/'.$p)) {fwrite(STDERR,"FALTA $p\n");exit(2);}
    require_once $root.'/'.$p;
}
$passes=0;$failed=0;
function check(string $name, callable $fn): void {
    global $passes,$failed;
    try {if ($fn() !== true) throw new RuntimeException('Resultado inesperado');$passes++;echo "[OK] $name\n";}
    catch(Throwable $e) {$failed++;echo "[FALLO] $name: ".$e->getMessage()."\n";}
}
function throws(callable $fn): bool {try {$fn();return false;} catch(InvalidArgumentException|DomainException $e){return true;}}
check('Pesos a centavos: entero, décimas, centésimas',fn()=>PosCobros::cents('0')===0 && PosCobros::cents('10')===1000 && PosCobros::cents('10.5')===1050 && PosCobros::cents('10.05')===1005);
check('Centavos a pesos: monto y signo',fn()=>PosCobros::money(101)==='1.01' && PosCobros::money(-350)==='-3.50');
check('Rechaza importes negativos, cero mal escrito y precisión excesiva',fn()=> throws(fn()=>PosCobros::cents('-1')) && throws(fn()=>PosCobros::cents('00.5')) && throws(fn()=>PosCobros::cents('10.123')) && throws(fn()=>PosCobros::cents('10,50')));
check('Pago efectivo: cambio exacto sin inflar caja',static function(){ $r=PosCobros::validar(['forma'=>'EFECTIVO','efectivo_recibido'=>'1000.00','tarjeta_importe'=>'0'],85000);return $r['cambio']===15000 && $r['efectivo']===85000 && $r['tarjeta']===0;});
check('Pago efectivo insuficiente rechazado',fn()=>throws(fn()=>PosCobros::validar(['forma'=>'EFECTIVO','efectivo_recibido'=>'849.99','tarjeta_importe'=>'0'],85000)));
check('Tarjeta aprobada y referencia normalizada',static function(){$r=PosCobros::validar(['forma'=>'TARJETA','efectivo_recibido'=>'0','tarjeta_importe'=>'1500.00','confirmacion_terminal'=>'1','proveedor'=>'terminal banco','referencia_terminal'=>'aut123456'],150000);return $r['efectivo']===0 && $r['tarjeta']===150000 && $r['referencia']==='AUT123456';});
check('Tarjeta sin aprobación rechazada',fn()=>throws(fn()=>PosCobros::validar(['forma'=>'TARJETA','efectivo_recibido'=>'0','tarjeta_importe'=>'1500.00','proveedor'=>'BANCO','referencia_terminal'=>'AUT123456'],150000)));
check('Tarjeta sin referencia rechazada',fn()=>throws(fn()=>PosCobros::validar(['forma'=>'TARJETA','efectivo_recibido'=>'0','tarjeta_importe'=>'1500.00','confirmacion_terminal'=>'1','proveedor'=>'BANCO','referencia_terminal'=>''],150000)));
check('Mixto divide el total correctamente',static function(){$r=PosCobros::validar(['forma'=>'MIXTO','efectivo_recibido'=>'1000.00','tarjeta_importe'=>'2000.00','confirmacion_terminal'=>'1','proveedor'=>'BANCO','referencia_terminal'=>'AUT123456'],300000);return $r['efectivo']===100000 && $r['tarjeta']===200000 && $r['cambio']===0;});
check('Mixto no admite todo tarjeta ni efectivo insuficiente',fn()=>throws(fn()=>PosCobros::validar(['forma'=>'MIXTO','efectivo_recibido'=>'0','tarjeta_importe'=>'3000','confirmacion_terminal'=>'1','proveedor'=>'BANCO','referencia_terminal'=>'AUT123456'],300000)) && throws(fn()=>PosCobros::validar(['forma'=>'MIXTO','efectivo_recibido'=>'999.99','tarjeta_importe'=>'2000','confirmacion_terminal'=>'1','proveedor'=>'BANCO','referencia_terminal'=>'AUT123456'],300000)));
check('No admite datos de terminal para efectivo',fn()=>throws(fn()=>PosCobros::validar(['forma'=>'EFECTIVO','efectivo_recibido'=>'100','tarjeta_importe'=>'0','referencia_terminal'=>'AUT123456'],10000)));
check('Monto de venta cero no se puede cobrar',fn()=>throws(fn()=>PosCobros::validar(['forma'=>'EFECTIVO','efectivo_recibido'=>'0','tarjeta_importe'=>'0'],0)));
$valid=['nombre'=>'María Elena','apellidoP'=>'López','apellidoM'=>'','telefono'=>'(664) 123-4567','correo'=>'','observaciones_pos'=>'Primera compra'];
check('Cliente POS sin correo ni contraseña',static function()use($valid){$x=PosClientes::validar($valid);return $x['correo']===null && $x['apellidoM']===null && $x['telefono']==='6641234567' && !isset($x['clave']);});
check('Nombre acentuado aceptado',static function()use($valid){$x=PosClientes::validar($valid);return $x['nombre']==='María Elena';});
check('Correo inválido rechazado',static function()use($valid){$valid['correo']='invalido@@test';return throws(fn()=>PosClientes::validar($valid));});
check('Teléfono inválido rechazado',static function()use($valid){$valid['telefono']='hola';return throws(fn()=>PosClientes::validar($valid));});
check('Nombre vacío rechazado',static function()use($valid){$valid['nombre']='';return throws(fn()=>PosClientes::validar($valid));});
check('Observaciones muy extensas rechazadas',static function()use($valid){$valid['observaciones_pos']=str_repeat('a',260);return throws(fn()=>PosClientes::validar($valid));});
$acc=(new ReflectionMethod(PosPostventa::class,'acumulado'));
check('Devolución acumulada 3 unidades: sin pérdida de centavos',static function()use($acc){$a=$acc->invoke(null,10000,3,1);$b=$acc->invoke(null,10000,3,2);$c=$acc->invoke(null,10000,3,3);return $a===3333 && $b-$a===3334 && $c-$b===3333 && $c===10000;});
check('Devolución acumulada nunca supera monto original',static function()use($acc){for($net=1;$net<900;$net+=11){for($q=1;$q<=9;$q++){ $old=0;for($d=1;$d<=$q;$d++){$next=$acc->invoke(null,$net,$q,$d);if($next<$old || $next>$net)return false;$old=$next;}if($old!==$net)return false;}}return true;});
printf("\nRESULTADO: %d correctas, %d fallidas.\n",$passes,$failed);
exit($failed?1:0);
