<?php
/** Comprobante no fiscal, vista de impresión térmica / impresora común. Sin datos clínicos de recetas. */
$h=static fn($s)=>htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$din=static fn($s)=>'$'.number_format((float)$s,2,'.',',');
$esDevolucion=isset($data['comprobante']);
$t=$esDevolucion?$data['comprobante']:$data['detalle'];
$enc=$esDevolucion?$t['devolucion']:$t['venta'];
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $esDevolucion?'Devolución':'Ticket'; ?> · Instituto Visual California</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#eaf2f9;font:12px/1.45 system-ui,-apple-system,Arial,sans-serif;color:#20334a}
.papel{width:80mm;max-width:100%;margin:26px auto;padding:16px;background:white;box-shadow:0 8px 28px #1b3a581f}
h1{margin:0;font-size:17px;text-align:center;letter-spacing:.03em}h2{font-size:12px;text-align:center;margin:4px 0 12px;font-weight:500}p{margin:4px 0}.centro{text-align:center}.linea{border:0;border-top:1px dashed #8c9dac;margin:10px 0}
table{width:100%;border-collapse:collapse;font-size:11px}th,td{vertical-align:top;text-align:left;padding:5px 2px}td:last-child,th:last-child{text-align:right}.total{font-weight:800;font-size:15px}.acciones{display:flex;gap:12px;justify-content:center;margin:15px}.acciones button{padding:10px 20px;border:0;border-radius:8px;background:#1568a6;color:white;cursor:pointer}
@page{size:80mm auto;margin:3mm}@media print{body{background:white}.papel{padding:0;margin:0 auto;box-shadow:none;width:74mm}.acciones{display:none}}@media screen and (max-width:500px){.papel{margin:5px auto}}
</style></head><body>
<div class="acciones"><button onclick="window.print()">Imprimir</button><button onclick="window.close()">Cerrar</button></div>
<article class="papel">
 <h1>INSTITUTO VISUAL CALIFORNIA</h1>
 <h2><?php echo $esDevolucion?'COMPROBANTE DE DEVOLUCIÓN':'COMPROBANTE DE VENTA'; ?></h2>
 <p class="centro">Documento comercial · No es factura fiscal</p><hr class="linea">
 <?php if($esDevolucion): ?>
 <p><strong>Devolución:</strong> #<?php echo (int)$enc['id_devolucion']; ?></p>
 <p><strong>Venta:</strong> #<?php echo (int)$enc['id_venta']; ?> · <strong>Pedido:</strong> #<?php echo (int)$enc['id_pedido']; ?></p>
 <p><strong>Fecha:</strong> <?php echo $h($enc['creada_en']); ?></p>
 <p><strong>Cliente:</strong> <?php echo $h(trim($enc['nombre'].' '.$enc['apellido'])); ?></p>
 <p><strong>Autorizó:</strong> <?php echo $h($enc['responsable']); ?></p><hr class="linea">
 <table><thead><tr><th>Artículo / Cant.</th><th>Reembolso</th></tr></thead><tbody>
 <?php foreach($t['lineas'] as $l): ?><tr><td><?php echo $h($l['producto']); ?><?php if($l['nombre_mica_snapshot']!==null): ?><br>Micas: <?php echo $h($l['nombre_mica_snapshot']); ?><?php endif; ?><br>Unidades: <?php echo (int)$l['cantidad']; ?> · Stock: <?php echo ((int)$l['reingresa_stock'] ? 'reingresó' : 'no reingresó'); ?></td><td><?php echo $din($l['importe']); ?></td></tr><?php endforeach; ?>
 </tbody></table><hr class="linea"><p class="total">Total devuelto: <?php echo $din($enc['total']); ?></p><p><strong>Motivo:</strong> <?php echo $h($enc['motivo']); ?></p>
 <?php else: ?>
 <p><strong>Venta:</strong> #<?php echo (int)$enc['id_venta']; ?> · <strong>Pedido:</strong> #<?php echo (int)$enc['id_pedido']; ?></p>
 <p><strong>Fecha:</strong> <?php echo $h($enc['creada_en']); ?></p>
 <p><strong>Cliente:</strong> <?php echo $h(trim($enc['cliente_nombre'].' '.$enc['cliente_apellido'])); ?></p>
 <p><strong>Vendedor:</strong> <?php echo $h($enc['vendedor_nombre'].' '.$enc['vendedor_apellido']); ?></p><p><strong>Caja:</strong> <?php echo $h($enc['caja_codigo']); ?></p><hr class="linea">
 <table><thead><tr><th>Concepto / Cant.</th><th>Importe</th></tr></thead><tbody>
 <?php foreach($t['lineas'] as $l): ?><tr><td><?php echo $h($l['producto']); ?> <br>x<?php echo (int)$l['cantidad']; ?> · <?php echo $din($l['precio']); ?> c/u
 <?php if($l['nombre_mica_snapshot']!==null): ?><br>Micas: <?php echo $h($l['nombre_mica_snapshot']); ?> · <?php echo $din($l['precio_unitario_mica']); ?>/par<?php endif; ?></td>
 <td><?php echo $din($l['bruto_centavos']/100); ?></td></tr><?php endforeach; ?></tbody></table>
 <hr class="linea"><p>Subtotal: <?php echo $din($enc['subtotal']); ?></p><p>Descuento: -<?php echo $din($enc['descuento']); ?></p><p class="total">Total pagado: <?php echo $din($enc['total']); ?></p>
 <?php endif; ?>
 <hr class="linea"><p><strong><?php echo $esDevolucion?'Reembolsos registrados':'Formas de pago'; ?></strong></p>
 <?php foreach($t['pagos'] as $p): ?><p><?php echo $h($p['metodo']); ?>: <?php echo $din($p['importe']); ?>
 <?php if(($p['proveedor']??'')!==''): ?><br><?php echo $h($p['proveedor']); ?> · Ref. <?php echo $h($esDevolucion?$p['referencia_reembolso']:$p['referencia_terminal']); ?><?php endif; ?></p><?php endforeach; ?>
 <?php if(!$esDevolucion && count($t['devoluciones'])>0): ?><hr class="linea"><p><strong>Devoluciones posteriores:</strong> <?php echo $din($t['reembolsado']); ?></p><p><strong>Importe vigente:</strong> <?php echo $din($t['pendiente']); ?></p><?php endif; ?>
 <hr class="linea"><p class="centro">Gracias por confiar en nosotros.</p>
 <p class="centro">Para aclaraciones conserva este comprobante y tu referencia de terminal cuando corresponda.</p>
</article></body></html>
