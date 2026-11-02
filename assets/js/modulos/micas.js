/* Catálogo de micas | Instituto Visual California
 * Cargar después de footer-admin.php (Bootstrap está disponible).
 * No altera los scripts de pedidos, carrito o PayPal.
 */
(() => {
  'use strict';
  const root = document.getElementById('ivc-micas-admin');
  if (!root) return;
  const $ = (id) => document.getElementById(id);
  const base = root.dataset.baseUrl.replace(/\/$/, '');
  const token = $('micasCsrf').value;
  const modal = new bootstrap.Modal($('micasModal'));
  const historialModal = new bootstrap.Modal($('micasHistorialModal'));
  const form = $('micasFormulario');
  let catalogo = [];
  const pesos = new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'});

  function celda(tr, contenido, clase = '') {
    const td = document.createElement('td');
    if (clase) td.className = clase;
    if (contenido instanceof Node) td.append(contenido);
    else td.textContent = String(contenido ?? '');
    tr.append(td);
    return td;
  }
  function bloqueTitulo(nombre, sku) {
    const box = document.createElement('div');
    const titulo = document.createElement('span');
    titulo.className = 'ivc-name'; titulo.textContent = nombre;
    const detalle = document.createElement('span');
    detalle.className = 'ivc-sku'; detalle.textContent = sku;
    box.append(titulo, detalle); return box;
  }
  function par(titulo, desc) {
    const box = document.createElement('div');
    const line = document.createElement('span');
    line.className = 'ivc-type'; line.textContent = titulo;
    const small = document.createElement('span');
    small.className = 'ivc-subtype'; small.textContent = desc || '—';
    box.append(line, small); return box;
  }
  function acciones(row) {
    const box = document.createElement('div');
    box.className = 'd-flex justify-content-end align-items-center flex-nowrap';
    const botones = [
      ['editar', 'fas fa-pencil-alt', 'Editar mica'],
      ['historial', 'fas fa-history', 'Historial de precios'],
      ['estado', row.activo == 1 ? 'fas fa-pause' : 'fas fa-play', row.activo == 1 ? 'Desactivar mica' : 'Activar mica']
    ];
    botones.forEach(([action, icon, title]) => {
      const b = document.createElement('button');
      b.type = 'button'; b.className = 'ivc-action'; b.dataset.action = action;
      b.dataset.ivcPermiso = action === 'historial' ? 'micas.leer' : 'micas.administrar';
      b.dataset.id = row.id_mica; b.title = title; b.setAttribute('aria-label', title);
      const i = document.createElement('i'); i.className = icon; i.setAttribute('aria-hidden', 'true');
      b.append(i); box.append(b);
    });
    return box;
  }
  function aviso(texto, tipo = 'info') {
    $('micasMensaje').textContent = texto;
    $('micasMensaje').className = `alert alert-${tipo} mt-3`;
    if (window.Swal && tipo !== 'info') Swal.fire(tipo === 'success' ? 'Correcto' : 'Aviso', texto, tipo === 'danger' ? 'error' : tipo);
  }
  async function api(recurso, opciones = {}) {
    const respuesta = await fetch(base + '/micas/' + recurso, {credentials:'same-origin', cache:'no-store', ...opciones});
    const tipo = respuesta.headers.get('content-type') || '';
    if (!tipo.includes('application/json')) throw new Error('No se pudo consultar la información. Verifica tu sesión y vuelve a intentarlo.');
    const body = await respuesta.json();
    if (!respuesta.ok || !body.ok) throw new Error(body.msg || 'No se pudo completar la operación.');
    return body;
  }
  function formPost(campos) {
    const fd = campos instanceof FormData ? campos : new FormData();
    if (!(campos instanceof FormData)) Object.entries(campos).forEach(([k,v]) => fd.append(k, v));
    fd.set('csrf', token);
    return {method:'POST', body:fd};
  }
  function render() {
    const buscar = $('micasBusqueda').value.trim().toLocaleLowerCase('es-MX');
    const tipo = $('micasFiltroTipo').value;
    const estado = $('micasFiltroEstado').value;
    const rows = catalogo.filter(r => {
      const searchable = [r.sku,r.nombre_comercial,r.tipo_lente,r.materiales,r.tratamientos].join(' ').toLocaleLowerCase('es-MX');
      return (!buscar || searchable.includes(buscar)) && (!tipo || String(r.id_tipo_lente) === tipo) && (!estado || String(r.activo) === estado);
    });
    const tbody = $('micasCuerpo'); tbody.replaceChildren();
    if (!rows.length) {
      const tr = document.createElement('tr');
      celda(tr, 'No se encontraron micas con esos filtros.', 'text-center py-5').colSpan = 6;
      tbody.append(tr);
    }
    rows.forEach(r => {
      const tr = document.createElement('tr');
      celda(tr, bloqueTitulo(r.nombre_comercial, r.sku));
      celda(tr, par(r.tipo_lente, r.materiales));
      celda(tr, r.tratamientos || 'Sin tratamiento');
      const price = par(pesos.format(Number(r.precio_venta)), 'Por par · MXN');
      price.firstChild.className = 'ivc-price'; price.lastChild.className = 'ivc-price-unit';
      celda(tr, price);
      const est = document.createElement('span');
      est.className = `ivc-status ${r.activo == 1 ? 'active' : 'inactive'}`;
      est.textContent = r.activo == 1 ? '● Activo' : '● Inactivo';
      celda(tr, est);
      celda(tr, acciones(r), 'text-end');
      tbody.append(tr);
    });
    $('micasConteo').textContent = `${rows.length} de ${catalogo.length} opciones mostradas`;
    $('micasTotal').textContent = catalogo.length;
    $('micasActivas').textContent = catalogo.filter(r => r.activo == 1).length;
    const precios = catalogo.filter(r => r.activo == 1).map(r => Number(r.precio_venta));
    $('micasMinimo').textContent = precios.length ? pesos.format(Math.min(...precios)) : '—';
    $('micasMaximo').textContent = precios.length ? pesos.format(Math.max(...precios)) : '—';
  }
  async function listar() {
    try {
      $('micasRecargar').disabled = true;
      const result = await api('listar');
      catalogo = result.datos;
      render();
    } catch (e) { aviso(e.message, 'danger'); }
    finally { $('micasRecargar').disabled = false; }
  }
  function seleccionarIds(idContenedor, ids) {
    const set = new Set(ids.map(String));
    root.querySelectorAll(`#${idContenedor} input[type=checkbox]`).forEach(input => {input.checked = set.has(input.value);});
  }
  function nueva() {
    form.reset();
    $('micasId').value = '0'; $('micasPrecioAnterior').value = '0.00';
    $('micasModalTitle').textContent = 'Nueva mica';
    $('micasGuardar').textContent = 'Registrar mica';
    modal.show();
  }
  async function editar(id) {
    try {
      const {mica:m} = await api('obtener/' + encodeURIComponent(id));
      form.reset();
      $('micasId').value = m.id_mica;
      $('micasPrecioAnterior').value = m.precio_venta;
      $('micasSku').value = m.sku;
      $('micasNombre').value = m.nombre_comercial;
      $('micasTipo').value = m.id_tipo_lente;
      $('micasPrecio').value = m.precio_venta;
      $('micasNotas').value = m.notas_internas || '';
      $('micasMotivo').value = '';
      seleccionarIds('micasMateriales', m.materiales);
      seleccionarIds('micasTratamientos', m.tratamientos);
      $('micasModalTitle').textContent = 'Editar mica';
      $('micasGuardar').textContent = 'Guardar cambios';
      modal.show();
    } catch (e) { aviso(e.message, 'danger'); }
  }
  async function historial(id) {
    try {
      const {datos} = await api('historial/' + encodeURIComponent(id));
      const tbody = $('micasHistorialCuerpo'); tbody.replaceChildren();
      const row = catalogo.find(item => String(item.id_mica) === String(id));
      $('micasHistorialTitulo').textContent = `Historial: ${row ? row.nombre_comercial : id}`;
      if (!datos.length) {
        const tr = document.createElement('tr'); celda(tr, 'No hay cambios de precio registrados.', 'text-center py-4').colSpan = 5; tbody.append(tr);
      } else datos.forEach(r => {
        const tr = document.createElement('tr');
        celda(tr, r.fecha_cambio);
        celda(tr, pesos.format(Number(r.precio_anterior)));
        celda(tr, pesos.format(Number(r.precio_nuevo)));
        celda(tr, r.usuario); celda(tr, r.motivo || '—'); tbody.append(tr);
      });
      historialModal.show();
    } catch (e) { aviso(e.message, 'danger'); }
  }
  async function estado(id) {
    const row = catalogo.find(r => String(r.id_mica) === String(id));
    if (!row) return;
    const activo = row.activo == 1 ? 0 : 1;
    const accion = activo ? 'activar' : 'desactivar';
    const confirmado = window.Swal ? (await Swal.fire({title:`¿Deseas ${accion} esta mica?`,text:row.nombre_comercial,icon:'question',showCancelButton:true,confirmButtonText:'Sí, continuar',cancelButtonText:'Cancelar'})).isConfirmed : window.confirm(`¿Deseas ${accion} ${row.nombre_comercial}?`);
    if (!confirmado) return;
    try {
      await api('estado', formPost({id_mica:id,activo:String(activo)}));
      await listar(); aviso(`Se ha ${activo ? 'activado' : 'desactivado'} la mica.`, 'success');
    } catch (e) { aviso(e.message, 'danger'); }
  }
  async function guardar(event) {
    event.preventDefault();
    if (!form.reportValidity()) return;
    if (!form.querySelector('input[name="materiales[]"]:checked')) { aviso('Selecciona al menos un material.', 'warning'); return; }
    const boton = $('micasGuardar'); boton.disabled = true;
    try {
      const fd = new FormData(form);
      await api('guardar', formPost(fd));
      modal.hide(); await listar(); aviso('Mica guardada correctamente.', 'success');
    } catch (e) { aviso(e.message, 'danger'); }
    finally { boton.disabled = false; }
  }
  $('micasNuevo').addEventListener('click', nueva);
  $('micasRecargar').addEventListener('click', listar);
  ['micasBusqueda','micasFiltroTipo','micasFiltroEstado'].forEach(id => {$(id).addEventListener(id === 'micasBusqueda' ? 'input' : 'change', render);});
  $('micasCuerpo').addEventListener('click', (event) => {
    const btn = event.target.closest('button[data-action][data-id]');
    if (!btn) return;
    const {action,id} = btn.dataset;
    if (action === 'editar') editar(id);
    if (action === 'historial') historial(id);
    if (action === 'estado') estado(id);
  });
  form.addEventListener('submit', guardar);
  listar();
})();
