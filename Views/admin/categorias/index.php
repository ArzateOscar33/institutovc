<?php include_once 'Views/template/header-admin.php'; ?>
<section class="ivc-admin-e1 ivc-module-e1">
  <div class="ivc-page-hero ivc-module-hero">
    <div><span class="ivc-eyebrow">CATÁLOGO</span><h1>Categorías</h1><p>Organiza los productos para facilitar la navegación de la tienda.</p></div>
    <div class="ivc-hero-actions"><span class="ivc-page-hero-icon"><i class="bx bx-category"></i></span></div>
  </div>


<button class="btn btn-primary mb-2" type="button" id="nuevo_registro" data-ivc-permiso="categorias.administrar">Nueva categoría</button>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle" style="width: 100%;" id="tblCategorias">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Imagen</th>
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
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="imagen_actual" name="imagen_actual">
                    <div class="form-group mb-2">
                        <label for="categoria">Nombre</label>
                        <input id="categoria" class="form-control" type="text" name="categoria" placeholder="Nombre de la categoría">
                    </div>
                    <div class="form-group">
                        <label for="imagen">Imagen (opcional)</label>
                        <input id="imagen" class="form-control-file" type="file" name="imagen">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit" id="btnAccion" data-ivc-permiso="categorias.administrar">Registrar</button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'Views/template/footer-admin.php'; ?>

<script src="<?php echo BASE_URL . 'assets/js/modulos/categorias.js'; ?>"></script>

</body>

</html>