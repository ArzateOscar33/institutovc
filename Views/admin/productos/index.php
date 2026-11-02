<?php include_once 'Views/template/header-admin.php'; ?>
<section class="ivc-admin-e1 ivc-module-e1">
  <div class="ivc-page-hero ivc-module-hero">
    <div><span class="ivc-eyebrow">INVENTARIO Y CATÁLOGO</span><h1>Productos</h1><p>Administra armazones, precios, fotografías y existencias disponibles.</p></div>
    <div class="ivc-hero-actions"><span class="ivc-page-hero-icon"><i class="bx bx-glasses"></i></span></div>
  </div>


<div class="ivc-module-toolbar"><div><h2>Gestión de productos</h2><p>Consulta las existencias o registra un nuevo artículo.</p></div></div>
<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#listaProducto" type="button" role="tab" aria-controls="listaProducto" aria-selected="true">Productos</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="profile-tab" data-ivc-permiso="productos.administrar" data-bs-toggle="tab" data-bs-target="#nuevoProducto" type="button" role="tab" aria-controls="nuevoProducto" aria-selected="false">Agregar producto</button>
    </li>
</ul>
<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="listaProducto" role="tabpanel" aria-labelledby="home-tab">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle" style="width: 100%;" id="tblProductos">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
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
    </div>
    <div class="tab-pane fade" id="nuevoProducto" role="tabpanel" aria-labelledby="profile-tab">
        <div class="card">
            <div class="card-body p-5">
                <form id="frmRegistro">
                    <div class="row">
                        <input type="hidden" id="id" name="id">
                        <input type="hidden" id="imagen_actual" name="imagen_actual">
                        <div class="col-md-5">
                            <div class="form-group mb-2">
                                <label for="nombre">Nombre del producto</label>
                                <input id="nombre" class="form-control" type="text" name="nombre" placeholder="Nombre del producto">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-2">
                                <label for="precio">Precio</label>
                                <input id="precio" class="form-control" type="text" name="precio" placeholder="Precio">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-2">
                                <label for="cantidad">Existencias</label>
                                <input id="cantidad" class="form-control" type="number" name="cantidad" placeholder="Cantidad">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="categoria">Categoría</label>
                                <select id="categoria" class="form-control" name="categoria">
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($data['categorias'] as $categoria) { ?>
                                        <option value="<?php echo $categoria['id']; ?>"><?php echo $categoria['categoria']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea id="descripcion" class="form-control" rows="3" placeholder="Descripción"></textarea>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="imagen">Imagen (opcional)</label>
                                <input id="imagen" class="form-control" type="file" name="imagen">
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-primary" type="submit" id="btnAccion" data-ivc-permiso="productos.administrar">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</section>
<div id="modalGaleria" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="my-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Imágenes del producto</h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
                </button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL . 'productos/galeriaImagenes'; ?>" class="dropzone">
                    <input type="hidden" id="idProducto" name="idProducto">
                </form>
                <div class="text-end mt-3">
                    <button class="btn btn-primary" type="button" id="btnProcesar" data-ivc-permiso="productos.administrar">Subir imágenes</button>
                </div>
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between" id="containerGaleria">
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'Views/template/footer-admin.php'; ?>

<script src="<?php echo BASE_URL . 'assets/js/ckeditor.js'; ?>"></script>

<script src="<?php echo BASE_URL . 'assets/js/modulos/productos.js'; ?>"></script>

</body>

</html>