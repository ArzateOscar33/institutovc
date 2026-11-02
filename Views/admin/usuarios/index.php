<?php include_once 'Views/template/header-admin.php'; ?>
<section class="ivc-admin-e1 ivc-module-e1">
  <div class="ivc-page-hero ivc-module-hero">
    <div><span class="ivc-eyebrow">ADMINISTRACIÓN</span><h1>Usuarios</h1><p>Gestiona los accesos del equipo y mantén actualizado el directorio de usuarios.</p></div>
    <div class="ivc-hero-actions"><span class="ivc-page-hero-icon"><i class="bx bx-group"></i></span></div>
  </div>


<div class="d-flex flex-wrap gap-2 mb-3"><button class="btn btn-primary" type="button" id="nuevo_registro">Nuevo usuario</button>
<?php if (RolesAcceso::permiso('roles.gestionar')): ?><a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>roles"><i class="bx bx-shield-quarter"></i> Administrar roles y permisos</a><?php endif; ?></div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover" style="width: 100%;" id="tblUsuarios">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

</section>
<div id="nuevoModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="my-modal-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="titleModal"></h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
                </button>
            </div>
            <form id="frmRegistro">
                <input type="hidden" name="csrf" id="ivcRolesCsrf" value="<?php echo htmlspecialchars($data['csrf'],ENT_QUOTES,'UTF-8'); ?>">
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">
                    <div class="form-group mb-2">
                        <label for="nombre">Nombres</label>
                        <input id="nombre" class="form-control" type="text" name="nombre" placeholder="Nombres">
                    </div>
                    <div class="form-group mb-2">
                        <label for="apellido">Apellidos</label>
                        <input id="apellido" class="form-control" type="text" name="apellido" placeholder="Apellidos">
                    </div>
                    <div class="form-group mb-2">
                        <label for="correo">Correo</label>
                        <input id="correo" class="form-control" type="email" name="correo" placeholder="Correo electrónico">
                    </div>
                    <div class="form-group mb-2">
                        <label for="id_rol">Rol de acceso</label>
                        <select class="form-select" name="id_rol" id="id_rol" required>
                            <option value="">Selecciona un rol</option>
                            <?php foreach ($data['roles'] as $r): ?>
                            <option value="<?php echo (int)$r['id_rol']; ?>"><?php echo htmlspecialchars($r['nombre'],ENT_QUOTES,'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label for="clave">Contraseña</label>
                        <input id="clave" class="form-control" type="password" name="clave" placeholder="Mínimo 10 caracteres (usuarios nuevos)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit" id="btnAccion">Registrar</button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php include_once 'Views/template/footer-admin.php'; ?>

<script src="<?php echo BASE_URL . 'assets/js/modulos/usuarios.js?v=4940'; ?>"></script>

</body>

</html>