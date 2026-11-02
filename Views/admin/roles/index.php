<?php include_once 'Views/template/header-admin.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-roles-admin-v4941.css?v=4941a">
<section class="ivc-roles-page">
  <div class="ivc-roles-hero">
    <div><span class="ivc-roles-eyebrow">CONFIGURACIÓN · SOLO ROOT</span><h1>Roles y permisos</h1>
      <p>Define el acceso de cada puesto sin modificar código. Los permisos se aplican también a las rutas administrativas.</p></div>
    <button type="button" class="btn btn-light ivc-roles-nuevo" id="ivcNuevoRol"><i class="bx bx-plus-circle"></i> Crear rol</button>
  </div>
  <div class="ivc-roles-note"><i class="bx bx-info-circle"></i> ROOT siempre conserva todos los permisos. Los roles Administrador y Vendedor/a son editables, pero no pueden eliminarse.</div>
  <div class="card ivc-roles-card"><div class="card-header bg-transparent d-flex justify-content-between align-items-center"><h5 class="mb-0">Roles registrados</h5><span class="small text-muted" id="ivcRolesCantidad">Cargando…</span></div>
    <div class="card-body"><div class="table-responsive"><table class="table align-middle ivc-roles-table"><thead><tr><th>Rol</th><th>Código</th><th>Usuarios activos</th><th>Permisos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
      <tbody id="ivcRolesFilas"><tr><td colspan="6" class="text-center py-4">Consultando roles…</td></tr></tbody></table></div></div></div>
</section>

<div class="modal fade" id="ivcModalRol" tabindex="-1" aria-labelledby="ivcRolTitulo" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered"><div class="modal-content ivc-roles-modal">
    <div class="modal-header"><div><span class="ivc-roles-eyebrow">ADMINISTRACIÓN DE ACCESO</span><h4 class="modal-title" id="ivcRolTitulo">Crear rol</h4></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <form id="ivcRolFormulario">
      <input type="hidden" name="csrf" id="ivcRolCsrf" value="<?php echo htmlspecialchars($data['csrf'],ENT_QUOTES,'UTF-8'); ?>">
      <input type="hidden" name="id_rol" id="ivcRolId" value="0">
      <div class="modal-body">
        <div class="row g-3 mb-4">
          <div class="col-lg-5"><label class="form-label" for="ivcRolNombre">Nombre del rol</label><input class="form-control" id="ivcRolNombre" name="nombre" maxlength="70" placeholder="Ej. Recepción" required></div>
          <div class="col-lg-3"><label class="form-label" for="ivcRolCodigo">Código interno</label><input class="form-control" id="ivcRolCodigo" name="codigo" maxlength="30" placeholder="RECEPCION" pattern="[A-Z][A-Z0-9_]{2,29}" required><div class="form-text">Mayúsculas y guion bajo.</div></div>
          <div class="col-lg-4"><label class="form-label" for="ivcRolDescripcion">Descripción</label><input class="form-control" id="ivcRolDescripcion" name="descripcion" maxlength="220" placeholder="Responsabilidades del puesto"></div>
        </div>
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2"><h5 class="mb-0">Permisos del rol</h5><div><button type="button" class="btn btn-outline-secondary btn-sm" id="ivcRolTodo">Seleccionar todos</button> <button type="button" class="btn btn-outline-secondary btn-sm" id="ivcRolNinguno">Limpiar</button></div></div>
        <p class="text-muted small mb-3">Marca las acciones autorizadas. Las dependencias de consulta se agregan automáticamente al guardar.</p>
        <?php
        $grupos = [
            'Panel' => ['dashboard'],
            'Pedidos y recetas' => ['pedidos.listar','pedidos.detalle','pedidos.administrar','recetas.leer','recetas.administrar'],
            'Citas' => ['citas.leer','citas.crear','citas.administrar'],
            'Catálogo e inventario' => ['productos.leer','productos.administrar','categorias.leer','categorias.administrar','micas.leer','micas.administrar'],
            'Administración' => ['usuarios.administrar'],
        ];
        foreach ($grupos as $grupo=>$acciones): ?>
          <fieldset class="ivc-roles-grupo"><legend><?php echo htmlspecialchars($grupo,ENT_QUOTES,'UTF-8'); ?></legend><div class="row g-2">
          <?php foreach ($acciones as $codigo): ?><div class="col-lg-6"><label class="ivc-roles-permiso"><input type="checkbox" name="permisos[]" value="<?php echo htmlspecialchars($codigo,ENT_QUOTES,'UTF-8'); ?>"><span><?php echo htmlspecialchars($data['catalogo'][$codigo],ENT_QUOTES,'UTF-8'); ?></span></label></div><?php endforeach; ?>
          </div></fieldset>
        <?php endforeach; ?>
        <div class="ivc-roles-note ivc-roles-note-compact">La gestión de roles y la asignación de ROOT nunca se delegan. Solo ROOT tiene ese acceso.</div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" id="ivcRolGuardar"><i class="bx bx-save"></i> Guardar rol</button></div>
    </form>
  </div></div>
</div>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/modulos/roles.js?v=4941"></script>
</body></html>
