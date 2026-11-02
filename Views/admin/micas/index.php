<?php include_once 'Views/template/header-admin.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-micas-admin.css?v=2.0.0">
<div class="ivc-micas-admin" id="ivc-micas-admin" data-base-url="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="micasCsrf" value="<?php echo htmlspecialchars($data['csrf'], ENT_QUOTES, 'UTF-8'); ?>">
    <div class="ivc-micas-heading">
        <div>
            <div class="ivc-eyebrow">ADMINISTRACIÓN · CATÁLOGOS</div>
            <h1>Catálogo de micas</h1>
            <p>Administra los precios finales por par, los materiales y los tratamientos de cada opción.</p>
        </div>
        <button type="button" class="btn ivc-btn-primary" id="micasNuevo" data-ivc-permiso="micas.administrar"><i class="fas fa-plus me-2" aria-hidden="true"></i> Nueva mica</button>
    </div>
    <div class="ivc-micas-note" role="note"><i class="fas fa-info-circle me-2" aria-hidden="true"></i> Los precios indicados son <strong>por par de micas en MXN</strong>. Los cambios de precios se aplican a nuevas compras; los pedidos anteriores conservan sus importes registrados.</div>
    <div class="ivc-stats-row" aria-label="Resumen del catálogo">
        <div class="ivc-stat"><span>Modelos registrados</span><strong id="micasTotal">—</strong></div>
        <div class="ivc-stat"><span>Opciones activas</span><strong id="micasActivas">—</strong></div>
        <div class="ivc-stat"><span>Precio mínimo</span><strong id="micasMinimo">—</strong></div>
        <div class="ivc-stat"><span>Precio máximo</span><strong id="micasMaximo">—</strong></div>
    </div>
    <section class="ivc-micas-panel" aria-label="Listado de micas">
        <div class="ivc-panel-toolbar">
            <label class="ivc-search"><i class="fas fa-search" aria-hidden="true"></i><span class="visually-hidden">Buscar mica</span><input type="search" id="micasBusqueda" placeholder="Buscar por SKU, nombre o material…" autocomplete="off"></label>
            <select id="micasFiltroTipo" class="form-select" aria-label="Filtrar por tipo de lente"><option value="">Todos los tipos</option>
                <?php foreach ($data['catalogos']['tipos'] as $tipo): ?>
                <option value="<?php echo (int)$tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="micasFiltroEstado" class="form-select" aria-label="Filtrar por estado"><option value="">Todos los estados</option><option value="1">Activos</option><option value="0">Inactivos</option></select>
            <button class="btn btn-outline-secondary" type="button" id="micasRecargar" aria-label="Actualizar catálogo"><i class="fas fa-sync-alt" aria-hidden="true"></i></button>
        </div>
        <div class="table-responsive">
            <table class="table ivc-micas-table align-middle" id="micasTabla">
                <thead><tr><th>Mica</th><th>Tipo / materiales</th><th>Tratamientos</th><th>Precio / par</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody id="micasCuerpo"><tr><td colspan="6" class="text-center py-5">Cargando micas…</td></tr></tbody>
            </table>
        </div>
        <div id="micasConteo" class="ivc-list-footer" aria-live="polite"></div>
    </section>
    <div id="micasMensaje" class="mt-3" aria-live="polite"></div>

    <!-- Formulario para el catálogo de micas -->
    <div class="modal fade" id="micasModal" tabindex="-1" aria-hidden="true" aria-labelledby="micasModalTitle">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content ivc-modal-content">
            <form id="micasFormulario" novalidate>
                <div class="modal-header"><div><small class="ivc-eyebrow">CATÁLOGO DE MATERIALES</small><h2 class="modal-title h4" id="micasModalTitle">Nueva mica</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id_mica" id="micasId" value="0"><input type="hidden" name="precio_anterior" id="micasPrecioAnterior" value="0.00">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="micasSku">Código / SKU</label><input class="form-control" name="sku" id="micasSku" type="text" maxlength="45" pattern="[A-Za-z0-9_-]+" required placeholder="MON-POLY-AR"></div>
                        <div class="col-md-8"><label class="form-label" for="micasNombre">Nombre comercial</label><input class="form-control" name="nombre_comercial" id="micasNombre" maxlength="170" required placeholder="POLY AR"></div>
                        <div class="col-md-6"><label class="form-label" for="micasTipo">Tipo de lente</label><select class="form-select" id="micasTipo" name="id_tipo_lente" required><option value="">Seleccionar tipo</option>
                            <?php foreach ($data['catalogos']['tipos'] as $tipo): ?>
                            <option value="<?php echo (int)$tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label" for="micasPrecio">Precio final por par (MXN)</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" type="number" inputmode="decimal" min="0.01" max="999999.99" step="0.01" name="precio_venta" id="micasPrecio" required placeholder="800.00"><span class="input-group-text">MXN</span></div></div>
                        <div class="col-12"><label class="form-label">Materiales incluidos <span class="text-danger">*</span></label><div class="ivc-options-grid" id="micasMateriales">
                            <?php foreach ($data['catalogos']['materiales'] as $material): ?>
                            <label class="ivc-option"><input type="checkbox" name="materiales[]" value="<?php echo (int)$material['id']; ?>"><span><?php echo htmlspecialchars($material['nombre'], ENT_QUOTES, 'UTF-8'); ?></span></label>
                            <?php endforeach; ?>
                        </div></div>
                        <div class="col-12"><label class="form-label">Tratamientos incluidos (opcional)</label><div class="ivc-options-grid" id="micasTratamientos">
                            <?php foreach ($data['catalogos']['tratamientos'] as $tratamiento): ?>
                            <label class="ivc-option"><input type="checkbox" name="tratamientos[]" value="<?php echo (int)$tratamiento['id']; ?>"><span><?php echo htmlspecialchars($tratamiento['nombre'], ENT_QUOTES, 'UTF-8'); ?></span></label>
                            <?php endforeach; ?>
                        </div></div>
                        <div class="col-md-12"><label class="form-label" for="micasNotas">Notas internas (opcional)</label><textarea class="form-control" name="notas_internas" id="micasNotas" maxlength="255" rows="2" placeholder="Condiciones del material, rango permitido…"></textarea></div>
                        <div class="col-md-12"><label class="form-label" for="micasMotivo">Motivo del cambio de precio (si aplica)</label><input class="form-control" type="text" name="motivo_precio" maxlength="255" id="micasMotivo" placeholder="Ej. Actualización de lista del proveedor"></div>
                    </div>
                    <div class="ivc-modal-notice mt-3">La unidad de venta se guarda automáticamente como <strong>PAR</strong>. Los precios anteriores quedarán en el historial.</div>
                </div>
                <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn ivc-btn-primary" type="submit" id="micasGuardar" data-ivc-permiso="micas.administrar">Guardar mica</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="micasHistorialModal" tabindex="-1" aria-hidden="true" aria-labelledby="micasHistorialTitulo">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content ivc-modal-content"><div class="modal-header"><h2 class="modal-title h5" id="micasHistorialTitulo">Historial de precios</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Fecha</th><th>Anterior</th><th>Nuevo</th><th>Usuario</th><th>Motivo</th></tr></thead><tbody id="micasHistorialCuerpo"></tbody></table></div></div></div></div>
    </div>
</div>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script defer src="<?php echo BASE_URL; ?>assets/js/modulos/micas.js?v=2.0.0"></script>
</body>
</html>
