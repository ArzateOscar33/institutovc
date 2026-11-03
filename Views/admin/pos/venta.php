<?php
/** POS 4.9.5.3: preparación óptica y carrito; sin cobro ni persistencia. */
$ivcEsc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$ivcTurno = $data['turno'] ?? null;
include_once 'Views/template/header-admin.php';
?>
<link rel="stylesheet" href="<?php echo $ivcEsc(BASE_URL); ?>assets/css/ivc-pos-venta-v4952.css?v=4952">
<link rel="stylesheet" href="<?php echo $ivcEsc(BASE_URL); ?>assets/css/ivc-pos-optica-v4953.css?v=4953">
<link rel="stylesheet" href="<?php echo $ivcEsc(BASE_URL); ?>assets/css/ivc-pos-clientes-v49531.css?v=49531">
<link rel="stylesheet" href="<?php echo $ivcEsc(BASE_URL); ?>assets/css/ivc-pos-activacion-v49532.css?v=49532">
<link rel="stylesheet" href="<?php echo $ivcEsc(BASE_URL); ?>assets/css/ivc-pos-cobro-v4954.css?v=4954">
<main class="ivc-pv" id="ivcPosVenta"
  data-base="<?php echo $ivcEsc(BASE_URL); ?>"
  data-csrf="<?php echo $ivcEsc($data['csrf']); ?>"
  data-turno="<?php echo $ivcTurno ? '1' : '0'; ?>"
  data-descuentos="<?php echo !empty($data['puede_descontar']) ? '1' : '0'; ?>"
  data-cobrar="<?php echo !empty($data['puede_cobrar']) ? '1' : '0'; ?>">

  <header class="ivc-pv-head">
    <div>
      <p class="ivc-pv-kicker"><i class="bx bx-glasses-alt" aria-hidden="true"></i> INSTITUTO VISUAL CALIFORNIA · PUNTO DE VENTA</p>
      <h1>Punto de venta <small>Fase 4.9.5.4</small></h1>
      <p>Prepara armazones con o sin micas y registra el cobro en efectivo o con la terminal física de la óptica.</p>
    </div>
    <div class="ivc-pv-head-actions">
      <a class="ivc-pv-link-head" href="<?php echo $ivcEsc(BASE_URL); ?>pos/historial"><i class="bx bx-receipt"></i> Historial y tickets</a>
      <a class="ivc-pv-link-head" href="<?php echo $ivcEsc(BASE_URL); ?>pos"><i class="bx bx-wallet"></i> Control de caja</a>
      <div class="ivc-pv-turno"><span class="ivc-pv-dot" aria-hidden="true"></span>
        <?php echo $ivcTurno ? $ivcEsc('Turno abierto · '.$ivcTurno['caja_nombre']) : 'Sin caja abierta'; ?></div>
    </div>
  </header>

  <?php if (!$ivcTurno): ?>
    <div class="ivc-pv-alerta" role="alert"><i class="bx bx-lock-alt" aria-hidden="true"></i>
      <div><strong>Abre una caja antes de preparar una venta.</strong><p>Regresa al control de caja y registra tu fondo inicial. Después vuelve a esta pantalla.</p></div>
      <a href="<?php echo $ivcEsc(BASE_URL); ?>pos">Abrir caja</a>
    </div>
  <?php endif; ?>

  <div class="ivc-pv-columns" <?php echo !$ivcTurno ? 'inert' : ''; ?>>
    <section class="ivc-pv-products" aria-labelledby="pvCatalogoTitulo">
      <div class="ivc-pv-card ivc-pv-filtros">
        <div class="ivc-pv-cardtitle">
          <div><span class="ivc-pv-label">CATÁLOGO COMPARTIDO</span><h2 id="pvCatalogoTitulo">Seleccionar productos</h2></div>
          <span class="ivc-pv-tag"><i class="bx bx-shield-quarter"></i> Stock disponible</span>
        </div>
        <div class="ivc-pv-filters-row">
          <label class="ivc-pv-search"><i class="bx bx-search" aria-hidden="true"></i>
            <span class="visually-hidden">Buscar producto</span>
            <input id="pvBuscar" type="search" maxlength="90" autocomplete="off" placeholder="Buscar por nombre o ID de producto…">
          </label>
          <label class="ivc-pv-stock-check"><input type="checkbox" id="pvDisponibles" checked> Solo disponibles</label>
        </div>
        <div id="pvCategorias" class="ivc-pv-categorias" role="group" aria-label="Filtrar por categoría">
          <button type="button" class="ivc-pv-cat activo" data-categoria="0">Todos</button>
        </div>
      </div>
      <div id="pvMensaje" class="ivc-pv-feedback" role="status" aria-live="polite">Cargando catálogo…</div>
      <div id="pvProductos" class="ivc-pv-lista" aria-label="Productos disponibles"></div>
      <div class="ivc-pv-mas"><button type="button" class="ivc-pv-secondary" id="pvCargarMas" hidden>Cargar más productos <i class="bx bx-chevron-down"></i></button></div>
    </section>

    <aside class="ivc-pv-aside" aria-label="Carrito y cliente">
      <section class="ivc-pv-card ivc-pv-ticket">
        <div class="ivc-pv-cardtitle">
          <div><span class="ivc-pv-label">VENTA EN PREPARACIÓN</span><h2><i class="bx bx-cart"></i> Carrito <span class="ivc-pv-bubble" id="pvCantidad">0</span></h2></div>
          <button class="ivc-pv-limpiar" id="pvLimpiar" type="button" disabled>Vaciar</button>
        </div>
        <div id="pvCarrito" class="ivc-pv-carrito"><div class="ivc-pv-empty"><i class="bx bx-shopping-bag"></i><strong>Tu carrito está vacío</strong><span>Selecciona productos del catálogo para comenzar.</span></div></div>
        <div class="ivc-pv-separador"></div>
        <div class="ivc-pv-field">
          <label for="pvClienteBusqueda">Cliente <span>opcional</span></label>
          <div class="ivc-pvc-cliente-tools"><input type="search" id="pvClienteBusqueda" placeholder="Buscar nombre, correo o teléfono…" maxlength="90" autocomplete="off"><button type="button" id="pvNuevoCliente" class="ivc-pvc-nuevo" <?php echo empty($data['puede_crear_cliente']) ? 'disabled title="No tienes permiso para registrar clientes"' : ''; ?>>+ Nuevo cliente</button></div>
          <div id="pvClientes" class="ivc-pv-clientes" role="listbox" hidden></div>
          <div class="ivc-pv-cliente-elegido" id="pvClienteSeleccionado">Venta de mostrador <button type="button" id="pvSinCliente" hidden aria-label="Quitar cliente">×</button></div>
          <div class="ivc-activacion-box"><button type="button" class="ivc-activacion-btn" id="pvaAbrir" <?php echo empty($data['puede_activar_cliente']) ? 'disabled title="No tienes permiso para emitir códigos"' : ''; ?>>🔐 Activar acceso web del cliente</button><p>Disponible para clientes POS que aún no tienen contraseña. Requiere identificar al comprador en persona.</p></div>
          <p class="ivc-pv-hint">Para micas es obligatorio elegir un cliente. Las recetas se muestran exclusivamente para ese cliente; también puedes indicar «Receta pendiente».</p>
        </div>
        <div class="ivc-pv-field">
          <label for="pvDescuento">Descuento preliminar (%)</label>
          <div class="ivc-pv-descuento"><input id="pvDescuento" type="number" min="0" max="100" step="1" value="0" <?php echo empty($data['puede_descontar']) ? 'disabled' : ''; ?>><span>%</span></div>
          <p class="ivc-pv-hint"><?php echo !empty($data['puede_descontar']) ? 'El descuento se validará de nuevo desde PHP.' : '🔒 No tienes permisos para aplicar descuentos.'; ?></p>
        </div>
        <div class="ivc-pv-totales">
          <div><span>Armazones y productos</span><strong id="pvArmazones">$0.00</strong></div>
          <div><span>Micas (por par)</span><strong id="pvMicas">$0.00</strong></div>
          <div><span>Subtotal</span><strong id="pvSubtotal">$0.00</strong></div>
          <div><span>Descuento</span><strong id="pvDescuentoImporte">−$0.00</strong></div>
          <div class="ivc-pv-total"><span>Total estimado</span><strong id="pvTotal">$0.00</strong></div>
        </div>
        <button type="button" class="ivc-pv-primary" id="pvPrevisualizar" disabled><i class="bx bx-check-shield"></i> Validar carrito y total</button>
        <div id="pvValidacion" class="ivc-pv-validacion" role="status" aria-live="polite">La validación no genera una venta ni aparta inventario.</div>
        <button type="button" id="pvCobrar" class="ivc-pv-cobrar" disabled><i class="bx bx-credit-card"></i> Cobrar venta</button>
        <p class="ivc-pv-seguridad"><i class="bx bx-info-circle"></i> Confirma la aprobación en la terminal antes de registrar tarjeta. Si falla el registro, NO repitas el cargo: solicita conciliación.</p>
      </section>
    </aside>
  </div>
  <dialog id="pvModalCobro" class="ivc-pv-modal ivc-pvcob-modal" aria-labelledby="pvCobroTitulo">
    <form id="pvCobroForm" novalidate>
      <div class="ivc-pv-modal-head"><div><span class="ivc-pv-label">PAGO PRESENCIAL</span><h2 id="pvCobroTitulo">Confirmar venta</h2></div><button type="button" id="pvCobroCerrar" class="ivc-pv-modal-x" aria-label="Cerrar">×</button></div>
      <div class="ivc-pvcob-resumen">Total confirmado <strong id="pvCobroTotal">$0.00</strong></div>
      <label class="ivc-pv-modal-label" for="pvCobroForma">Método de pago</label>
      <select id="pvCobroForma" required><option value="EFECTIVO">Efectivo</option><option value="TARJETA">Tarjeta (terminal física)</option><option value="MIXTO">Mixto (efectivo + tarjeta)</option></select>
      <div id="pvCobroTarjetaGrupo" hidden>
        <label class="ivc-pv-modal-label" for="pvCobroTarjeta">Importe aprobado con tarjeta ($ MXN)</label>
        <input type="number" id="pvCobroTarjeta" min="0" step="0.01" value="0.00">
        <label class="ivc-pv-modal-label" for="pvCobroProveedor">Proveedor o banco de la terminal</label>
        <input type="text" id="pvCobroProveedor" maxlength="70" placeholder="Ej. MERCADO PAGO, BANCO..." autocomplete="off">
        <label class="ivc-pv-modal-label" for="pvCobroReferencia">Referencia de autorización del recibo</label>
        <input type="text" id="pvCobroReferencia" maxlength="80" placeholder="Folio único de autorización" autocomplete="off">
        <label class="ivc-pvcob-check"><input type="checkbox" id="pvCobroConfirmacion"><span>Confirmo que el cobro fue <strong>aprobado en la terminal física</strong> y que verifiqué el comprobante.</span></label>
      </div>
      <div id="pvCobroEfectivoGrupo">
        <label class="ivc-pv-modal-label" for="pvCobroRecibido">Efectivo recibido del cliente ($ MXN)</label>
        <input type="number" id="pvCobroRecibido" min="0" step="0.01" value="0.00">
      </div>
      <div class="ivc-pvcob-cuentas"><div>Parte de efectivo <strong id="pvCobroParteEfectivo">$0.00</strong></div><div>Parte de tarjeta <strong id="pvCobroParteTarjeta">$0.00</strong></div><div>Cambio a entregar <strong id="pvCobroCambio">$0.00</strong></div></div>
      <p class="ivc-pvcob-aviso">El sistema NO cobra la tarjeta. Sólo registra el pago ya aprobado externamente. Si hay un error tras cobrar en la terminal, conserva el recibo y solicita conciliación; NO cobres otra vez.</p>
      <div id="pvCobroEstado" role="status" aria-live="polite"></div>
      <div class="ivc-pv-modal-actions"><button type="button" id="pvCobroCancelar" class="ivc-pv-secondary">Regresar</button><button type="submit" id="pvCobroGuardar" class="ivc-pv-primary">Registrar venta pagada</button></div>
    </form>
  </dialog>
  <dialog id="pvModalCliente" class="ivc-pv-modal ivc-pvc-modal" aria-labelledby="pvClienteModalTitulo">
    <form id="pvFormularioNuevoCliente" novalidate>
      <div class="ivc-pv-modal-head"><div><span class="ivc-pv-label">REGISTRO DE MOSTRADOR</span><h2 id="pvClienteModalTitulo">Nuevo cliente</h2></div><button type="button" id="pvCerrarNuevoCliente" class="ivc-pv-modal-x" aria-label="Cerrar">×</button></div>
      <p class="ivc-pv-modal-aviso">No necesita tener cuenta en línea. Los datos de contacto son opcionales; recomendamos teléfono para dar seguimiento a los lentes graduados.</p>
      <div class="ivc-pvc-grid">
        <label>Nombre <strong>*</strong><input id="pvcNombre" name="nombre" required maxlength="100" autocomplete="given-name" placeholder="Nombre(s)"></label>
        <label>Apellido paterno <strong>*</strong><input id="pvcApellidoP" name="apellidoP" required maxlength="100" autocomplete="family-name" placeholder="Apellido paterno"></label>
        <label>Apellido materno <span>opcional</span><input id="pvcApellidoM" name="apellidoM" maxlength="100" placeholder="Apellido materno"></label>
        <label>Teléfono <span>recomendado</span><input id="pvcTelefono" name="telefono" type="tel" maxlength="30" autocomplete="tel" placeholder="664 123 4567"></label>
        <label class="ancho">Correo electrónico <span>opcional</span><input id="pvcCorreo" name="correo" type="email" maxlength="100" autocomplete="email" placeholder="cliente@correo.com"></label>
        <label class="ancho">Observaciones de contacto <span>opcional</span><textarea id="pvcObservaciones" name="observaciones_pos" maxlength="255" rows="2" placeholder="Ej. Prefiere llamadas por la tarde"></textarea></label>
      </div>
      <p class="ivc-pvc-hint">* Obligatorios. No registres datos de graduación ni información médica en observaciones. La receta se gestiona en el módulo óptico.</p>
      <div id="pvcCoincidencias" class="ivc-pvc-coincidencias" role="status" aria-live="polite"></div>
      <div class="ivc-pv-modal-actions"><button type="button" id="pvcCancelar" class="ivc-pv-secondary">Cancelar</button><button type="submit" id="pvcGuardar" class="ivc-pv-primary">Registrar y seleccionar</button></div>
    </form>
  </dialog>
  <dialog id="pvaModal" class="ivc-pv-modal" aria-labelledby="pvaTitulo">
    <form id="pvaForm" novalidate>
      <div class="ivc-pv-modal-head"><div><span class="ivc-pv-label">CUENTA DIGITAL</span><h2 id="pvaTitulo">Entregar código de activación</h2></div><button type="button" id="pvaCerrar" class="ivc-pv-modal-x" aria-label="Cerrar">×</button></div>
      <p class="ivc-pv-modal-aviso">Verifica la identidad del cliente presencialmente. El código debe entregarse directamente al comprador: no se envía por correo ni se guarda en texto legible.</p>
      <label class="ivc-pv-modal-label" for="pvaCorreo">Correo electrónico del cliente</label>
      <input type="email" id="pvaCorreo" required maxlength="100" autocomplete="off" placeholder="cliente@correo.com" class="form-control">
      <label style="display:flex;gap:.55rem;margin-top:1rem;align-items:flex-start"><input type="checkbox" id="pvaIdentidad" required style="margin-top:.3rem"> <span>Confirmo que verifiqué personalmente la identidad del cliente y que este correo le pertenece.</span></label>
      <div id="pvaSalida" role="status" style="margin-top:1rem"></div>
      <div class="ivc-pv-modal-actions"><button type="button" id="pvaCancelar" class="ivc-pv-secondary">Cerrar</button><button type="submit" class="ivc-pv-primary" id="pvaEmitir">Generar código</button></div>
    </form>
  </dialog>
  <dialog id="pvModalOptica" class="ivc-pv-modal" aria-labelledby="pvModalTitulo">
    <form method="dialog" id="pvFormularioOptica">
      <div class="ivc-pv-modal-head"><div><span class="ivc-pv-label">CONFIGURACIÓN ÓPTICA</span><h2 id="pvModalTitulo">Agregar micas al armazón</h2></div><button type="button" id="pvModalCerrar" class="ivc-pv-modal-x" aria-label="Cerrar">×</button></div>
      <p class="ivc-pv-modal-producto" id="pvModalProducto"></p>
      <p class="ivc-pv-modal-aviso">Las micas se cobran por <strong>par</strong>. Sus materiales y tratamientos ya están incluidos en el precio del catálogo; no se cobran nuevamente.</p>
      <label class="ivc-pv-modal-label" for="pvTipoMica">Tipo de lente</label>
      <select id="pvTipoMica" required></select>
      <label class="ivc-pv-modal-label" for="pvMica">Mica comercial</label>
      <select id="pvMica" required></select>
      <div class="ivc-pv-modal-detalle" id="pvMicaDetalle" aria-live="polite"></div>
      <label class="ivc-pv-modal-label" for="pvModalModo">Receta óptica</label>
      <select id="pvModalModo"><option value="PENDIENTE">Receta pendiente de proporcionar</option><option value="RECETA">Asociar receta existente del cliente</option></select>
      <div id="pvCampoReceta" hidden><label class="ivc-pv-modal-label" for="pvModalReceta">Receta disponible del cliente</label><select id="pvModalReceta"></select></div>
      <p class="ivc-pv-hint">Esta pantalla no interpreta la graduación ni sustituye la revisión optométrica. El pedido quedará pendiente de revisión o de receta según corresponda.</p>
      <div class="ivc-pv-modal-actions"><button type="button" id="pvModalQuitar" class="ivc-pv-modal-danger">Sin micas</button><button type="button" id="pvModalGuardar" class="ivc-pv-primary">Guardar configuración</button></div>
    </form>
  </dialog>
</main>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script src="<?php echo $ivcEsc(BASE_URL); ?>assets/js/modulos/pos-venta-v4954.js?v=4955"></script>
<script src="<?php echo $ivcEsc(BASE_URL); ?>assets/js/modulos/pos-activacion-v49532.js?v=49532"></script>
</body></html>
