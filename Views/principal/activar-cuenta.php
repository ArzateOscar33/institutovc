<?php
$e=static fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$modo=$data['estado']??'inicio';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="referrer" content="no-referrer"><title>Activación de cuenta — Instituto Visual California</title>
<style>
*{box-sizing:border-box}body{margin:0;background:linear-gradient(135deg,#e8f6ff,#f9fdff);color:#194360;font:16px system-ui,-apple-system,sans-serif;min-height:100vh;display:grid;place-items:center;padding:22px}
.card{background:#fff;max-width:510px;width:100%;padding:clamp(22px,5vw,40px);border-radius:20px;box-shadow:0 15px 45px #0f50621c;border:1px solid #cde4f3}
.logo{letter-spacing:.14em;font-size:.75rem;font-weight:750;color:#28799a}h1{font-size:1.7rem;margin:.8rem 0}p{line-height:1.5;color:#547084}label{display:block;margin-top:16px;font-weight:650;font-size:.91rem}
input{width:100%;border:1px solid #a7c7da;border-radius:9px;padding:12px;margin-top:7px;font:inherit;color:#1c4560}input:focus{outline:2px solid #218bc4}
button{width:100%;border:0;border-radius:9px;background:#176c96;color:#fff;font-weight:700;padding:13px;margin-top:22px;cursor:pointer;font-size:1rem}
.alert{border:1px solid #e9adab;background:#fff1f0;color:#913e3a;padding:12px;border-radius:9px}a{color:#096d9e}footer{margin-top:20px;text-align:center;font-size:.9rem}
</style></head><body><main class="card"><div class="logo">INSTITUTO VISUAL CALIFORNIA</div>
<?php if($modo==='inicio'): ?>
<h1>Activar mi cuenta web</h1><p>Si te registraron en la óptica, escribe el correo registrado y el código que te entregaron en mostrador. Te enviaremos un enlace de confirmación.</p>
<?php if(!empty($data['mensaje'])):?><p role="alert" class="alert"><?= $e($data['mensaje']) ?></p><?php endif; ?>
<form action="<?= $e(BASE_URL) ?>activacion/solicitar" method="post">
<input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
<label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" maxlength="100" autocomplete="email" required>
<label for="codigo">Código recibido en la óptica (16 caracteres)</label><input id="codigo" name="codigo" maxlength="19" placeholder="ABCD-EF12-3456-7890" autocomplete="off" required>
<button type="submit">Enviar enlace de activación</button></form>
<p>¿No tienes código o correo registrado? Solicita apoyo presencial en la óptica.</p>
<?php elseif($modo==='enviado'): ?>
<h1>Revisa tu correo</h1><p>Te enviamos un enlace de verificación. Úsalo dentro de los próximos 30 minutos para establecer tu contraseña. Revisa también la carpeta de correo no deseado.</p>
<?php elseif($modo==='clave'): ?>
<h1>Crear contraseña</h1><p>Verificaremos tu correo y activaremos el acceso a la misma ficha con la que compraste en la óptica.</p>
<?php if(!empty($data['mensaje'])):?><p role="alert" class="alert"><?= $e($data['mensaje']) ?></p><?php endif; ?>
<form action="<?= $e(BASE_URL) ?>activacion/finalizar" method="post">
<input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>"><input type="hidden" name="token" value="<?= $e($data['token']) ?>">
<label for="clave">Contraseña nueva (mínimo 12 caracteres)</label><input id="clave" name="clave" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
<label for="confirmar">Confirmar contraseña</label><input id="confirmar" name="confirmar" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
<button type="submit">Activar mi cuenta</button></form>
<?php elseif($modo==='exito'): ?>
<h1>¡Cuenta activada!</h1><p>Tu cuenta ya puede iniciar sesión en la tienda en línea. Tus compras y recetas siguen vinculadas a tu mismo registro de cliente.</p>
<a href="<?= $e(BASE_URL) ?>">Ir a la tienda</a>
<?php else: ?>
<h1>Enlace no disponible</h1><p>Este enlace venció, fue utilizado o ya no es válido. Puedes iniciar nuevamente con tu código o solicitar uno nuevo en la óptica.</p>
<a href="<?= $e(BASE_URL) ?>activacion">Volver a activación</a>
<?php endif; ?>
<footer><a href="<?= $e(BASE_URL) ?>">← Volver a Instituto Visual California</a></footer></main></body></html>
