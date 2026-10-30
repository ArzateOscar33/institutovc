/** IVC Fase 4.3 — Carrito combinado, sin habilitar aún pagos de micas.
 * Los IDs de mica/receta son referencias, nunca importes ni documentos de salud.
 * Los importes mostrados son ESTIMADOS de APIs PHP; el cobro se implementará en 4.4/4.5.
 */
(() => {
  'use strict';
  if (window.IVCCart43) return;
  const KEY = 'ivcCarritoConfiguradoV43';
  const OLD = 'listaCarrito';
  const tbody = document.querySelector('#tableListaCarrito tbody');
  const drawer = document.getElementById('myModal');
  const totalEl = document.getElementById('totalGeneral');
  const counter = document.getElementById('btnCantidadCarrito');
  const base = typeof base_url === 'string' ? base_url : '/institutovc/';
  let renderTicket = 0;
  const peso = n => new Intl.NumberFormat('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2}).format(n);
  const notify = (msg, kind = 'success') => {
    if (typeof window.alertaPersonalizada === 'function') window.alertaPersonalizada(msg, kind);
    else if (window.toastr && typeof window.toastr[kind] === 'function') window.toastr[kind](msg);
    else console.info(msg);
  };
  const safeArray = (key) => {
    try { const value = JSON.parse(localStorage.getItem(key) || '[]'); return Array.isArray(value) ? value : []; }
    catch (_) { return []; }
  };
  const positiveInt = n => Number.isSafeInteger(Number(n)) && Number(n) > 0 && String(n).trim() !== '';
  const configuredItems = () => safeArray(KEY).filter(x => x && positiveInt(x.idProducto) && positiveInt(x.idMica) && positiveInt(x.cantidad) && typeof x.uid === 'string' && x.uid.length > 0);
  const oldItems = () => safeArray(OLD).filter(x => x && positiveInt(x.idProducto) && positiveInt(x.cantidad));
  const countConfigured = () => configuredItems().length;
  const hasConfigured = () => countConfigured() > 0;
  const uid = () => {
    // crypto.randomUUID requiere HTTPS o localhost; fallback solo para identificadores locales.
    if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
    return String(Date.now()) + '-' + String(Math.floor(Math.random() * 1000000000));
  };
  const stockCount = (id) => [...oldItems(), ...configuredItems()]
    .filter(x => Number(x.idProducto) === Number(id))
    .reduce((s, x) => s + Number(x.cantidad), 0);
  function syncCount() {
    const n = oldItems().length + countConfigured();
    if(counter) counter.textContent = String(n);
    const right = document.getElementById('ivcCartItemCount');
    if(right) right.textContent = String(n);
    const empty = document.getElementById('ivcCartEmpty');
    const list = document.getElementById('ivcCartList');
    if(empty) empty.hidden = n > 0;
    if(list) list.hidden = n === 0;
    drawer?.classList.toggle('is-empty', n === 0);
  }
  function paymentGuard() {
    if(!drawer) return;
    const link = drawer.querySelector('.ivc-cart-drawer__checkout');
    let note = drawer.querySelector('#ivc43PendingPay');
    if(!note) {
      note = document.createElement('div'); note.id = 'ivc43PendingPay';
      note.className = 'ivc43-pay-notice';
      note.setAttribute('role', 'status');
      note.textContent = 'Tus lentes graduados están en el carrito. Entra al checkout para validar precios desde PHP y realizar una compra de prueba con PayPal Sandbox.';
      link?.before(note);
    }
    const blocked = hasConfigured();
    note.hidden = !blocked;
    if(link) {
      // IVC_F44_ACCESS_CHECKOUT: volver a mostrar enlace al checkout, no habilita PayPal.
      link.style.display = '';
      link.setAttribute('aria-disabled', 'false');
    }
  }
  function checkoutGuard() {
    const shell = document.getElementById('ivcCheckoutFlow');
    if(!shell || !hasConfigured()) return false;
    let alert = document.getElementById('ivc43CheckoutAlert');
    if(!alert) {
      alert = document.createElement('section');
      alert.id='ivc43CheckoutAlert'; alert.className='ivc43-checkout-alert'; alert.setAttribute('role','alert');
      const title=document.createElement('strong');title.textContent='Compra con micas: utiliza el checkout de cotización PHP';
      const info=document.createElement('p');info.textContent='Tu carrito conserva los armazones, las micas y la receta seleccionada. Utiliza el checkout de cotización PHP para pagar el total en PayPal Sandbox.';
      const back=document.createElement('button');back.type='button';back.className='ivc43-outline';back.textContent='Ver mi carrito';
      back.addEventListener('click', open);
      alert.append(title,info,back);shell.prepend(alert);
    }
    const next=document.getElementById('ivcFlowToRx');if(next) next.disabled = true;
    const status=document.getElementById('ivcOrderStatus');if(status) status.textContent='Utiliza el checkout con cotización PHP para esta compra.';
    const pay=document.getElementById('ivcPayReal');if(pay) pay.style.display='none';
    const paypal=document.getElementById('paypal-button-container');if(paypal) paypal.style.display='none';
    return true;
  }
  function readProductRecords() {
    const originals=oldItems();
    const configured=configuredItems();
    return {originals, configured, request:[...originals,...configured].map(x=>({idProducto:Number(x.idProducto),cantidad:Number(x.cantidad)}))};
  }
  async function api(path, options) {
    const res = await fetch(base + path, {credentials:'same-origin',cache:'no-store',...options});
    if(!res.ok) throw new Error('Error del servidor ('+res.status+').');
    let data;
    try {data=await res.json();} catch(e) {throw new Error('El servidor no devolvió un JSON válido.');}
    return data;
  }
  const element = (tag, className, text) => {
    const el=document.createElement(tag);
    if(className) el.className=className;
    if(text !== undefined) el.textContent=String(text);
    return el;
  };
  function addCell(tr, content) {const td=element('td'); if(content instanceof Node) td.append(content);else td.textContent=content;tr.append(td);return td;}
  function renderRow(info, item, configured, i, mica) {
    const row=element('tr',configured?'ivc43-row ivc43-row-rx':'ivc43-row');
    const photo=element('img','ivc43-image');
    photo.alt=''; photo.width=74; photo.height=60; photo.loading='lazy'; photo.src=info.imagen || '';
    addCell(row,photo);
    const name=element('div','ivc43-name',info.nombre || 'Armazón');
    const details=element('div','ivc43-details');
    if(configured) {
      details.append(element('span','ivc43-lens','Micas: '+mica.nombre_comercial+' · '+mica.tipo_lente));
      details.append(element('span','',item.idReceta ? 'Receta #'+item.idReceta+' · Revisión pendiente' :
        (item.solicitaValoracion ? 'Valoración pendiente' : 'Receta pendiente de entregar')));
      details.append(element('small','','Configuración independiente. Para modificarla, elimina y vuelve a configurar.'));
    } else details.append(element('small','', 'Solo armazón'));
    const productInfo=element('div','ivc43-product');productInfo.append(name,details);addCell(row,productInfo);
    const frameP=Number(info.precio);
    const lensP=configured?Number(mica.precio_venta):0;
    addCell(row,'$'+peso(frameP+lensP));
    if(configured) addCell(row,element('span','ivc43-quantity-fixed',String(item.cantidad)));
    else {
      const input=element('input','form-control agregarCantidad');input.type='number';input.min='1';input.max='10';
      input.value=String(item.cantidad);input.dataset.ivc43Kind='plain';input.dataset.ivc43Index=String(i);
      input.setAttribute('aria-label','Cantidad de '+(info.nombre||'armazón'));
      addCell(row,input);
    }
    addCell(row,'$'+peso((frameP+lensP)*Number(item.cantidad)));
    const btn=element('button','btn ivc43-remove','×');btn.type='button';btn.title='Eliminar artículo';
    btn.setAttribute('aria-label','Eliminar '+(info.nombre||'producto'));btn.dataset.ivc43Kind=configured?'rx':'plain';btn.dataset.ivc43Index=String(i);
    addCell(row,btn);
    return row;
  }
  async function render() {
    if(!tbody) return;
    const ticket=++renderTicket;
    syncCount();paymentGuard();
    const {originals,configured,request}=readProductRecords();
    tbody.replaceChildren();
    if (!request.length) {if(totalEl) totalEl.textContent='0.00';return;}
    const row=element('tr');const col=addCell(row,'Consultando precios vigentes…');col.colSpan=6;tbody.append(row);
    try {
      const [frames, lensData] = await Promise.all([
        api('principal/listaProductos',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(request)}),
        configured.length ? api('cotizaciones/catalogo') : Promise.resolve({ok:true,micas:[]})
      ]);
      if(ticket!==renderTicket) return;
      if(!Array.isArray(frames.productos) || frames.productos.length !== request.length || lensData.ok!==true || !Array.isArray(lensData.micas)) {
        throw new Error('No fue posible verificar todos los conceptos del carrito.');
      }
      const byMica=new Map(lensData.micas.map(x=>[Number(x.id_mica),x]));
      const fragment=document.createDocumentFragment();
      let cents=0;
      for(let i=0;i<request.length;i++) {
        const isRx=i>=originals.length;
        const item=isRx?configured[i-originals.length]:originals[i];
        const record=frames.productos[i];
        if(Number(record.id)!==Number(item.idProducto)) throw new Error('La respuesta del servidor no coincide con el producto solicitado.');
        const mica=isRx?byMica.get(Number(item.idMica)):null;
        if(isRx && (!mica || mica.unidad_cobro!=='PAR' || mica.moneda!=='MXN')) {
          throw new Error('Un material dejó de estar disponible. Elimina esa configuración o contacta a la óptica.');
        }
        const frameMoney=String(record.precio);
        const lensMoney=isRx?String(mica.precio_venta):'0';
        if(!/^(?:\d+)(?:\.\d{1,2})?$/.test(frameMoney) || !/^(?:\d+)(?:\.\d{1,2})?$/.test(lensMoney)) throw new Error('Precio inválido devuelto por el servidor.');
        const priceCent=s=>{const [whole,dec='']=s.split('.');return Number(whole)*100+Number(dec.padEnd(2,'0'));};
        cents+=(priceCent(frameMoney)+priceCent(lensMoney))*Number(item.cantidad);
        fragment.append(renderRow(record,item,isRx,isRx?i-originals.length:i,mica));
      }
      tbody.replaceChildren(fragment);
      if(totalEl) totalEl.textContent=peso(cents/100);
      syncCount();paymentGuard();
    } catch(error) {
      if(ticket!==renderTicket)return;
      tbody.replaceChildren();
      const tr=element('tr');const td=addCell(tr,'No se pudo actualizar el carrito: '+error.message);td.colSpan=6;td.className='ivc43-error';tbody.append(tr);
      if(totalEl) totalEl.textContent='—';
      paymentGuard();
    }
  }
  function open() {
    syncCount();paymentGuard();
    if(drawer && window.bootstrap?.Offcanvas) bootstrap.Offcanvas.getOrCreateInstance(drawer).show();
    render();
  }
  function addConfigured(entry) {
    const idProducto=Number(entry.idProducto),idMica=Number(entry.idMica), cantidad=Number(entry.cantidad),idReceta=entry.idReceta==null?null:Number(entry.idReceta);
    const requestedValuation=entry.solicitaValoracion===true;
    if(!positiveInt(idProducto)||!positiveInt(idMica)||!positiveInt(cantidad)||cantidad>10 ||
       (idReceta!==null&&!positiveInt(idReceta)) || (idReceta!==null&&requestedValuation) ||
       !positiveInt(entry.idCotizacion)) {
       notify('No se pudo guardar la configuración. Vuelve a cotizar las micas.','error');return false;
    }
    const configured=configuredItems();
    if(configured.length+oldItems().length>=30) {notify('Máximo 30 líneas por carrito.','warning');return false;}
    configured.push({uid:uid(),idProducto,cantidad,idMica,idReceta,solicitaValoracion:requestedValuation,
      idCotizacion:Number(entry.idCotizacion),creadaEn:new Date().toISOString()});
    try {localStorage.setItem(KEY,JSON.stringify(configured));}
    catch(e) {notify('No hay espacio para guardar el carrito.','error');return false;}
    syncCount();paymentGuard();notify('Lentes con micas agregados al carrito.');
    document.dispatchEvent(new CustomEvent('ivc:carrito-actualizado',{detail:{graduados:configured.length}}));
    return true;
  }
  function remove(kind,index) {
    const key=kind==='rx'?KEY:OLD;
    const arr=kind==='rx'?configuredItems():oldItems();
    if(!Number.isInteger(index)||index<0||index>=arr.length)return;
    arr.splice(index,1);localStorage.setItem(key,JSON.stringify(arr));
    if(kind==='plain' && typeof listaCarrito !== 'undefined') listaCarrito=arr;
    render();syncCount();
  }
  if(tbody) {
    tbody.addEventListener('click',e=>{
      const b=e.target.closest('button[data-ivc43-kind]');
      if(b) remove(b.dataset.ivc43Kind,Number(b.dataset.ivc43Index));
    });
    tbody.addEventListener('change',e=>{
      const input=e.target.closest('input[data-ivc43-kind="plain"]');if(!input)return;
      const qty=Number(input.value),arr=oldItems(), index=Number(input.dataset.ivc43Index);
      if(!Number.isInteger(qty)||qty<1||qty>10||!arr[index]) {notify('La cantidad debe estar entre 1 y 10.','warning');render();return;}
      arr[index].cantidad=qty;localStorage.setItem(OLD,JSON.stringify(arr));if(typeof listaCarrito !== 'undefined') listaCarrito=arr;render();
    });
  }
  window.IVCCart43={addConfigured,configuredItems,countConfigured,hasConfigured,render,open,blockCheckout:checkoutGuard};
  window.getListaCarrito=render;
  window.cantidadCarrito=syncCount;
  document.addEventListener('DOMContentLoaded',()=>{syncCount();paymentGuard();checkoutGuard();});
  window.addEventListener('storage',e=>{if(e.key===KEY||e.key===OLD){syncCount();paymentGuard();checkoutGuard();render();}});
  syncCount();paymentGuard();
})();
