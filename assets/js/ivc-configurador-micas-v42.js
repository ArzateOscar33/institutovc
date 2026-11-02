/** Instituto Visual California — Configurador de lentes graduados. */
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
  const nextBtn = $('ivc42Next');
  const backBtn = $('ivc42Back');
  const toPriceBtn = $('ivc42ToPrice');
  const backRecipeBtn = $('ivc42BackToRecipe');
  const retryBtn = $('ivc42Retry');
  const panel1 = $('ivc42Panel1');
  const panel2 = $('ivc42Panel2');
  const panel3 = $('ivc42Panel3');
  const progress1 = $('ivc42Progress1');
  const progress2 = $('ivc42Progress2');
  const progress3 = $('ivc42Progress3');
  let currentStep = 1;
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
  let prepared = null;
  let uploaded = {file:null,id:null};
  let quoteGeneration = 0;
  const money = n => new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'}).format(Number(n));
  const currentQty = () => {
    const val = Number($('product-quanity')?.value || 1);
    return Number.isInteger(val) && val > 0 && val <= stock && val <= 10 ? val : null;
  };
  const currentMode = () => document.querySelector('input[name="ivc42RecetaModo"]:checked')?.value || 'ARCHIVO';
  function notify(s, error = false) { message.textContent = s; message.classList.toggle('is-error', error); }
  function setStep(step) {
    if (step > 1 && (!selectedMica || !currentQty())) {
      notify('Selecciona tus micas y comprueba la cantidad antes de continuar.', true);
      return false;
    }
    if (step === 3) {
      const mode = currentMode();
      if (mode === 'ARCHIVO' && !fileInput.files?.length) {
        notify('Adjunta tu receta para continuar o selecciona otra opción de graduación.', true);
        return false;
      }
      if (mode === 'EXISTENTE' && !(Number(existingSelect.value) > 0)) {
        notify('Elige una receta guardada para continuar.', true);
        return false;
      }
    }
    currentStep = step;
    panel1.hidden = step !== 1;
    panel2.hidden = step !== 2;
    panel3.hidden = step !== 3;
    nextBtn.hidden = step !== 1;
    $('ivc42Step2Actions').hidden = step !== 2;
    $('ivc42Step3Actions').hidden = step !== 3;
    for (const [index,node] of [[1,progress1],[2,progress2],[3,progress3]]) {
      node.classList.toggle('is-active',index===step);
      node.classList.toggle('is-complete',index<step);
      if (index===step) node.setAttribute('aria-current','step');
      else node.removeAttribute('aria-current');
    }
    if (step > 1) $('ivc42ChosenLens').textContent = selectedMica.nombre_comercial;
    if (step === 3) $('ivc42FinalLens').textContent = selectedMica.nombre_comercial;
    const hints = {
      1:'Selecciona las micas que necesitas para continuar.',
      2:'Adjunta tu receta, elige una guardada o solicita una valoración.',
      3:'Consulta el total y agrega tus lentes al carrito sin desplazarte.'
    };
    $('ivc42FooterHelp').textContent=hints[step];
    notify('');
    $('ivc42Content').scrollTop = 0;
    const title = step === 1 ? $('ivc42StepOne') : step === 2 ? $('ivc42StepTwo') : $('ivc42StepThree');
    title.focus({preventScroll:true});
    return true;
  }
  function clearResult() {
    prepared=null;
    result.hidden=true;result.replaceChildren();
    if (currentStep===3) {
      $('ivc42FrameTotal').textContent='—';
      $('ivc42LensTotal').textContent='—';
      $('ivc42Estimate').textContent='—';
    }
    quoteBtn.disabled=true;
  }
  function setBusy(busy) {
    loading=busy;
    quoteBtn.disabled=busy || !prepared || currentStep!==3;
    nextBtn.disabled=busy || !selectedMica || catalog.length===0 || !currentQty();
    backBtn.disabled=busy;
    backRecipeBtn.disabled=busy;
    toPriceBtn.disabled=busy;
    retryBtn.disabled=busy;
    quoteBtn.textContent = busy ? 'Preparando precio…' : 'Agregar al carrito';
  }
  function make(tag, cls, txt) {
    const e = document.createElement(tag); if(cls) e.className=cls;
    if(txt !== undefined) e.textContent=String(txt); return e;
  }
  async function fetchJson(path, options = {}) {
    const response = await fetch(base + path, {credentials:'same-origin', cache:'no-store', ...options});
    let obj;
    try { obj = await response.json(); } catch (_) { throw new Error('No pudimos consultar la información. Intenta nuevamente.'); }
    if (!response.ok || !obj || !obj.ok) {
      const err = new Error(typeof obj?.msg === 'string' ? obj.msg : 'No se pudo completar la solicitud.');
      err.status = response.status;
      throw err;
    }
    return obj;
  }
  function displayEstimate() {
    // Los importes de catálogo se muestran al elegir micas; el total final solo proviene de PHP.
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
      notify(catalog.length ? 'Elige las micas que prefieras y continúa al paso 2 para adjuntar tu receta.' : 'Por el momento no hay micas disponibles. Contacta a la óptica.', !catalog.length);
    } catch(e) {notify(e.message,true);}
  }
  function renderMode() {
    const mode = currentMode();
    $('ivc42UploadPanel').hidden=mode !== 'ARCHIVO';
    $('ivc42ExistingPanel').hidden=mode !== 'EXISTENTE';
    const notice=$('ivc42SpecialNotice');
    const text = mode === 'VALORACION' ? 'Podrás completar tu compra y después agendar una valoración. La óptica confirmará tu graduación antes de preparar los lentes.' :
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
    prepared=null;
    clearResult();
    backdrop.hidden=false;drawer.hidden=false;drawer.setAttribute('aria-hidden','false');
    document.body.classList.add('ivc42-open');
    setStep(1);
    closeBtn.focus();loadCatalog();displayEstimate();renderMode();
  }
  function hide() {
    backdrop.hidden=true; drawer.hidden=true; drawer.setAttribute('aria-hidden','true');
    document.body.classList.remove('ivc42-open');previousFocus?.focus?.();
  }
  nextBtn.addEventListener('click', () => setStep(2));
  backBtn.addEventListener('click', () => setStep(1));
  backRecipeBtn.addEventListener('click', () => setStep(2));
  toPriceBtn.addEventListener('click', () => {if(setStep(3)) createQuote();});
  retryBtn.addEventListener('click',createQuote);
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
  fileInput.addEventListener('change',()=>{uploaded={file:null,id:null};clearResult();});
  document.querySelectorAll('[data-ivcd-qty]').forEach(b=>b.addEventListener('click',()=>requestAnimationFrame(()=>{clearResult();displayEstimate();})));

  async function saveNewRecipe() {
    const file = fileInput.files?.[0];
    if(!file) throw new Error('Selecciona una fotografía o PDF de la receta.');
    if(file.size > 10485760 || file.size < 1) throw new Error('El archivo debe tener entre 1 byte y 10 MB.');
    if(!/\.(jpg|jpeg|png|pdf)$/i.test(file.name)) throw new Error('Solo JPG, PNG o PDF.');
    if (uploaded.file===file && uploaded.id) return uploaded.id;
    const token = await fetchJson('recetas/token');
    const form = new FormData();
    form.set('csrf',token.csrf);form.set('modalidad','ARCHIVO');form.set('receta_archivo',file);
    const saved=await fetchJson('recetas/guardar',{method:'POST',body:form});
    if(!Number.isInteger(Number(saved.id_receta)) || Number(saved.id_receta)<1) throw new Error('No se recibió el identificador de la receta guardada.');
    uploaded={file,id:Number(saved.id_receta)};
    return Number(saved.id_receta);
  }
  async function createQuote() {
    if(loading || !selectedMica || currentStep !== 3) return;
    clearResult();
    retryBtn.hidden=true;
    const qty=currentQty();
    if(!qty) {notify('La cantidad no es válida o excede las existencias.',true);retryBtn.hidden=false;return;}
    const generation=++quoteGeneration;
    setBusy(true);notify('Calculando el precio de tus lentes…');
    try {
      const mode=currentMode();let idReceta=null;
      // Comprobar sesión antes de subir la receta o consultar el total.
      const auth=await fetchJson('cotizaciones/token');
      if(mode==='EXISTENTE') {
        idReceta=Number(existingSelect.value);
        if(!Number.isInteger(idReceta)||idReceta<1) throw new Error('Selecciona una receta guardada válida.');
      } else if(mode==='ARCHIVO') {
        notify('Guardando tu receta de forma segura…');
        idReceta=await saveNewRecipe();
      }
      notify('Confirmando el total de tus lentes…');
      const payload={csrf:auth.csrf,productos:[{
        id_producto:productId,cantidad:qty,id_mica:Number(selectedMica.id_mica),
        id_receta:idReceta, solicita_valoracion:mode==='VALORACION'
      }]};
      const data=await fetchJson('cotizaciones/crear',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
      const q=data.cotizacion;
      if(!q || !q.id_cotizacion || !Number.isFinite(Number(q.total))) throw new Error('No pudimos calcular el total. Intenta nuevamente.');
      if (generation!==quoteGeneration || currentStep!==3) return;
      prepared={q,qty,idReceta,mode,idMica:Number(selectedMica.id_mica)};
      $('ivc42FrameTotal').textContent=money(q.subtotal_armazones);
      $('ivc42LensTotal').textContent=money(q.subtotal_micas);
      $('ivc42Estimate').textContent=money(q.total);
      result.replaceChildren(make('p','','Precio confirmado. Se añadirá la configuración al carrito; todavía no se ha realizado ningún cobro.'));
      result.hidden=false;
      notify('Tu precio está listo. Puedes agregar los lentes al carrito.');
    } catch(e) {
      notify(e.status===401 ? 'Inicia sesión para continuar. Usa el icono de usuario del encabezado.' : (e.message||'No fue posible consultar el precio.'),true);
      retryBtn.hidden=false;
    } finally {setBusy(false);}
  }
  quoteBtn.addEventListener('click',()=>{
    if(loading || currentStep!==3 || !prepared) return;
    const {q,qty,idReceta,mode,idMica}=prepared;
    if (currentQty()!==qty) {
      clearResult();notify('La cantidad cambió. Consulta el precio nuevamente.',true);
      retryBtn.hidden=false;return;
    }
    if(!window.IVCCart43 || typeof window.IVCCart43.addConfigured!=='function') {
      notify('No pudimos abrir el carrito. Actualiza la página e inténtalo de nuevo.',true);return;
    }
    const saved=window.IVCCart43.addConfigured({
      idProducto:productId,cantidad:qty,idMica,idReceta,
      solicitaValoracion:mode==='VALORACION',idCotizacion:Number(q.id_cotizacion)
    });
    if(saved) {quoteBtn.disabled=true;quoteBtn.textContent='Agregado ✓';hide();window.IVCCart43.open();}
  });
  displayEstimate();
})();
