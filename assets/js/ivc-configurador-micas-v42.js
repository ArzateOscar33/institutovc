/** Instituto Visual California — Configurador V4.2.
 * Fase exclusiva de selección y cotización. NO toca listaCarrito ni PayPal.
 */
(() => {
  'use strict';
  const drawer = document.getElementById('ivc42Drawer');
  const openBtn = document.getElementById('ivc42Open');
  const closeBtn = document.getElementById('ivc42Close');
  const backdrop = document.getElementById('ivc42Backdrop');
  if (!drawer || !openBtn || !closeBtn || !backdrop || drawer.dataset.ready === '1') return;
  drawer.dataset.ready = '1';
  const base = drawer.dataset.urlBase.endsWith('/') ? drawer.dataset.urlBase : drawer.dataset.urlBase + '/';
  const productId = Number(drawer.dataset.idProducto);
  const stock = Number(drawer.dataset.stock);
  const framePrice = Number(drawer.dataset.precioArmazon);
  const $ = id => document.getElementById(id);
  const typesNode = $('ivc42Types');
  const materialsNode = $('ivc42Materials');
  const quoteBtn = $('ivc42Quote');
  const message = $('ivc42Message');
  const result = $('ivc42Result');
  const fileInput = $('ivc42File');
  const existingSelect = $('ivc42Existing');
  let catalog = [];
  let currentType = '';
  let selectedMica = null;
  let previousFocus = null;
  let loading = false;
  let existingFetched = false;
  const money = n => new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'}).format(Number(n));
  const currentQty = () => {
    const val = Number($('product-quanity')?.value || 1);
    return Number.isInteger(val) && val > 0 && val <= stock && val <= 10 ? val : null;
  };
  const currentMode = () => document.querySelector('input[name="ivc42RecetaModo"]:checked')?.value || 'ARCHIVO';
  function notify(s, error = false) { message.textContent = s; message.classList.toggle('is-error', error); }
  function clearResult() { result.hidden = true; result.replaceChildren(); }
  function setBusy(busy) {
    loading = busy;
    quoteBtn.disabled = busy || !selectedMica || catalog.length === 0 || !currentQty();
    quoteBtn.textContent = busy ? 'Consultando y guardando…' : 'Calcular y guardar cotización';
  }
  function make(tag, cls, txt) {
    const e = document.createElement(tag); if(cls) e.className=cls;
    if(txt !== undefined) e.textContent=String(txt); return e;
  }
  async function fetchJson(path, options = {}) {
    const response = await fetch(base + path, {credentials:'same-origin', cache:'no-store', ...options});
    let obj;
    try { obj = await response.json(); } catch (_) { throw new Error('El servidor no devolvió JSON. Comprueba que el módulo esté instalado.'); }
    if (!response.ok || !obj || !obj.ok) {
      const err = new Error(typeof obj?.msg === 'string' ? obj.msg : 'No se pudo completar la solicitud.');
      err.status = response.status;
      throw err;
    }
    return obj;
  }
  function displayEstimate() {
    const q = currentQty() || 1;
    $('ivc42FrameTotal').textContent = money(framePrice * q);
    $('ivc42LensTotal').textContent = selectedMica ? money(Number(selectedMica.precio_venta) * q) : 'Selecciona un material';
    $('ivc42Estimate').textContent = selectedMica ? money((framePrice + Number(selectedMica.precio_venta)) * q) : '—';
    setBusy(loading);
  }
  function renderMaterials() {
    materialsNode.replaceChildren();
    const listed = catalog.filter(m => m.tipo_lente === currentType);
    listed.forEach(m => {
      const card = make('button','ivc42-material');
      card.type = 'button'; card.setAttribute('role','radio');
      card.setAttribute('aria-checked', String(Number(selectedMica?.id_mica) === Number(m.id_mica)));
      card.append(make('span','ivc42-radio'), make('span','ivc42-material-label',m.nombre_comercial),make('strong','ivc42-material-price',money(m.precio_venta)));
      card.addEventListener('click', () => {selectedMica=m; clearResult(); renderMaterials(); displayEstimate();});
      materialsNode.append(card);
    });
    if(!listed.length) materialsNode.append(make('p', '', 'No hay micas disponibles para este tipo de lente.'));
  }
  function renderTypes() {
    typesNode.replaceChildren();
    [...new Set(catalog.map(m => m.tipo_lente))].forEach(type => {
      const b = make('button','ivc42-type', type);
      b.type = 'button'; b.setAttribute('aria-pressed', String(type === currentType));
      b.addEventListener('click', () => {
        if(currentType === type) return;
        currentType=type; selectedMica=null; clearResult(); renderTypes();renderMaterials(); displayEstimate();
      });
      typesNode.append(b);
    });
  }
  async function loadCatalog() {
    if(catalog.length) return;
    notify('Consultando precios vigentes de la óptica…');
    quoteBtn.disabled = true;
    try {
      const res = await fetchJson('cotizaciones/catalogo');
      catalog = Array.isArray(res.micas) ? res.micas.filter(m => Number(m.precio_venta)>0 && m.unidad_cobro === 'PAR' && m.moneda === 'MXN') : [];
      currentType = catalog[0]?.tipo_lente || '';
      renderTypes(); renderMaterials(); displayEstimate();
      notify(catalog.length ? 'Selecciona un par de micas y cómo proporcionarás tu graduación.' : 'No hay micas habilitadas por par. Contacta a la óptica.', !catalog.length);
    } catch(e) {notify(e.message,true);}
  }
  function renderMode() {
    const mode = currentMode();
    $('ivc42UploadPanel').hidden=mode !== 'ARCHIVO';
    $('ivc42ExistingPanel').hidden=mode !== 'EXISTENTE';
    const notice=$('ivc42SpecialNotice');
    const text = mode === 'VALORACION' ? 'Al habilitarse la compra configurada, podrás pagar el paquete y agendar una valoración. La fabricación requerirá confirmar que las micas seleccionadas sean adecuadas.' :
      mode === 'PENDIENTE' ? 'Podrás entregar tu receta posteriormente. La óptica deberá revisarla antes de elaborar las micas.' : '';
    notice.textContent=text;notice.hidden=!text;
    if(mode==='EXISTENTE' && !existingFetched) loadExisting();
    clearResult();
  }
  async function loadExisting() {
    existingFetched=true;
    try {
      const data = await fetchJson('recetas/misRecetas');
      const items = Array.isArray(data.datos) ? data.datos.filter(r => ['PENDIENTE','APROBADA'].includes(r.estado_revision)) : [];
      existingSelect.replaceChildren(new Option('Selecciona tu receta…',''));
      items.forEach(r => existingSelect.add(new Option(`Receta #${r.id_receta} · ${r.estado_revision}${r.fecha_receta ? ' · '+r.fecha_receta : ''}`,String(r.id_receta))));
      $('ivc42ExistingHelp').textContent = items.length ? 'Solo aparecen recetas pendientes o aprobadas que pertenecen a tu cuenta.' : 'No hay recetas disponibles. Adjunta una nueva o solicita valoración.';
    } catch(e) {
      existingFetched=false;existingSelect.replaceChildren(new Option('Inicia sesión para consultar tus recetas',''));
      $('ivc42ExistingHelp').textContent=e.message;
    }
  }
  function show() {
    previousFocus = document.activeElement;
    backdrop.hidden=false;drawer.hidden=false;drawer.setAttribute('aria-hidden','false');
    document.body.classList.add('ivc42-open');
    closeBtn.focus();loadCatalog();displayEstimate();renderMode();
  }
  function hide() {
    backdrop.hidden=true; drawer.hidden=true; drawer.setAttribute('aria-hidden','true');
    document.body.classList.remove('ivc42-open');previousFocus?.focus?.();
  }
  openBtn.addEventListener('click',show);
  closeBtn.addEventListener('click',hide);
  backdrop.addEventListener('click',hide);
  drawer.addEventListener('keydown', e => {
    if(e.key === 'Escape') { e.preventDefault();hide(); }
    if(e.key !== 'Tab') return;
    const elements=Array.from(drawer.querySelectorAll('button:not([disabled]),input:not([disabled]),select:not([disabled]),a[href]')).filter(el=>el.getClientRects().length && !el.closest('[hidden]'));
    if(!elements.length) return;
    const first=elements[0],last=elements[elements.length-1];
    if(e.shiftKey && document.activeElement===first){e.preventDefault();last.focus();}
    else if(!e.shiftKey && document.activeElement===last){e.preventDefault();first.focus();}
  });
  document.querySelectorAll('input[name="ivc42RecetaModo"]').forEach(i=>i.addEventListener('change',renderMode));
  existingSelect.addEventListener('change',clearResult);
  fileInput.addEventListener('change',clearResult);
  document.querySelectorAll('[data-ivcd-qty]').forEach(b=>b.addEventListener('click',()=>requestAnimationFrame(()=>{clearResult();displayEstimate();})));

  async function saveNewRecipe() {
    const file = fileInput.files?.[0];
    if(!file) throw new Error('Selecciona una fotografía o PDF de la receta.');
    if(file.size > 10485760 || file.size < 1) throw new Error('El archivo debe tener entre 1 byte y 10 MB.');
    if(!/\.(jpg|jpeg|png|pdf)$/i.test(file.name)) throw new Error('Solo JPG, PNG o PDF.');
    const token = await fetchJson('recetas/token');
    const form = new FormData();
    form.set('csrf',token.csrf);form.set('modalidad','ARCHIVO');form.set('receta_archivo',file);
    const saved=await fetchJson('recetas/guardar',{method:'POST',body:form});
    if(!Number.isInteger(Number(saved.id_receta)) || Number(saved.id_receta)<1) throw new Error('No se recibió el identificador de la receta guardada.');
    return Number(saved.id_receta);
  }
  async function createQuote() {
    if(loading || !selectedMica) return;
    clearResult();
    const qty=currentQty();
    if(!qty) {notify('La cantidad no es válida o excede las existencias.',true);return;}
    setBusy(true);notify('Validando tu sesión y preparando la cotización…');
    try {
      const mode=currentMode();let idReceta=null;
      // Autenticación ANTES de almacenar cualquier archivo.
      const auth=await fetchJson('cotizaciones/token');
      if(mode==='EXISTENTE') {
        idReceta=Number(existingSelect.value);
        if(!Number.isInteger(idReceta)||idReceta<1) throw new Error('Selecciona una receta guardada válida.');
      } else if(mode==='ARCHIVO') {
        notify('Guardando tu receta en un directorio privado…');
        idReceta=await saveNewRecipe();
      }
      notify('PHP está consultando los precios y generando la cotización…');
      const payload={csrf:auth.csrf,productos:[{
        id_producto:productId,cantidad:qty,id_mica:Number(selectedMica.id_mica),
        id_receta:idReceta, solicita_valoracion:mode==='VALORACION'
      }]};
      const data=await fetchJson('cotizaciones/crear',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
      const q=data.cotizacion;
      if(!q || !q.id_cotizacion) throw new Error('No se recibió la cotización definitiva.');
      notify('Cotización calculada y registrada correctamente.');
      result.replaceChildren();
      result.append(make('div','','Cotización #'+q.id_cotizacion+' · '+(q.moneda||'MXN')));
      result.append(make('strong','',money(q.total)));
      result.append(make('div','','Armazones: '+money(q.subtotal_armazones)+' · Micas: '+money(q.subtotal_micas)));
      result.append(make('p','','Válida hasta '+(q.expira_en||'dentro de 15 minutos')+'. La receta permanece pendiente de revisión. No se ha realizado ningún cobro ni se han añadido micas al carrito.'));
      // IVC Fase 4.3: se añade la configuración tras una cotización real de PHP.
      const addToCart=make('button','ivc42-add-to-cart','Agregar lentes configurados al carrito →');
      addToCart.type='button';
      addToCart.addEventListener('click', () => {
        if(!window.IVCCart43 || typeof window.IVCCart43.addConfigured !== 'function') {
          notify('Actualiza la página: falta la integración del carrito 4.3.',true);return;
        }
        const saved=window.IVCCart43.addConfigured({
          idProducto:productId,cantidad:qty,idMica:Number(selectedMica.id_mica),
          idReceta:idReceta,solicitaValoracion:mode==='VALORACION',idCotizacion:Number(q.id_cotizacion)
        });
        if(saved) {addToCart.disabled=true;addToCart.textContent='Agregado al carrito ✓';hide();window.IVCCart43.open();}
      });
      result.append(addToCart);
      result.hidden=false;
    } catch(e) {
      if(e.status===401) notify('Para guardar la cotización debes iniciar sesión con una cuenta verificada. Usa el icono de usuario del encabezado.',true);
      else notify(e.message||'No fue posible guardar la cotización.',true);
    } finally {setBusy(false);}
  }
  quoteBtn.addEventListener('click',createQuote);
  displayEstimate();
})();
