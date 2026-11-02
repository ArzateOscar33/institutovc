/** IVC Fase 4.6 — Cotización del carrito completo (armazones solos o con micas). */
(() => {
  'use strict';
  const root = document.getElementById('ivcCheckoutQuote44');
  if (!root || window.IVCCheckout44) return;
  const $ = id => document.getElementById(id);
  const KEY_RX = 'ivcCarritoConfiguradoV43';
  const KEY_PLAIN = 'listaCarrito';
  const base = root.dataset.base || (typeof base_url === 'string' ? base_url : '/institutovc/');
  const formatter = new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'});
  let busy = false;
  let signature = '';
  let quotedId = null;
  let refreshTimer = null;
  let needsRequote = false;
  let currentStep = 1;
  const btnPay = $('ivc44ToPayment');
  const btnBack = $('ivc44BackSummary');
  function setStep(step) {
    if(step===2 && (!quotedId || busy)) return;
    currentStep=step;
    $('ivc44PanelSummary').hidden=step!==1;
    $('ivc44PanelPayment').hidden=step!==2;
    for (const [n,id] of [[1,'ivc44ProgressSummary'],[2,'ivc44ProgressPayment']]) {
      const el=$(id);el.classList.toggle('ivc44-current',n===step);
      el.classList.toggle('ivc44-done',n<step);
      if(n===step)el.setAttribute('aria-current','step');else el.removeAttribute('aria-current');
    }
    const title=$(step===1?'ivc44Title':'ivc44PaymentTitle');
    title.focus({preventScroll:true});
    root.scrollIntoView({block:'start',behavior:'auto'});
  }
  btnPay.addEventListener('click',()=>{
    if(!quotedId || busy) return;
    setStep(2);
    document.dispatchEvent(new CustomEvent('ivc:mostrar-pago',{detail:{id:Number(quotedId)}}));
  });
  btnBack.addEventListener('click',()=>{
    document.dispatchEvent(new Event('ivc:volver-resumen'));
    setStep(1);
  });
  const read = key => {
    let raw;
    try { raw = JSON.parse(localStorage.getItem(key) || '[]'); }
    catch (_) {throw new Error('Hay un carrito con formato incorrecto. Revisa sus artículos antes de continuar.');}
    if (!Array.isArray(raw)) throw new Error('El carrito no tiene un formato válido.');
    return raw;
  };
  const positiveInt = value => Number.isSafeInteger(Number(value)) && Number(value) > 0 && String(value ?? '').trim() !== '';
  const parseId = (value, label, optional = false) => {
    if (optional && (value == null || value === '')) return null;
    if (!positiveInt(value) || Number(value) > 2147483647) throw new Error(label+' inválido.');
    return Number(value);
  };
  const make = (tag, text, cls) => {
    const el=document.createElement(tag);
    if (cls) el.className=cls;
    if (text != null) el.textContent=String(text);
    return el;
  };
  function payload() {
    const normal=read(KEY_PLAIN), rx=read(KEY_RX);
    if (!normal.length && !rx.length) return {active:false, lines:[], signature:'', descriptions:[]};
    if (normal.length + rx.length > 30) throw new Error('Máximo 30 líneas por cotización.');
    const lines=[], descriptions=[];
    for (const item of normal) {
      if (!item || typeof item!=='object') throw new Error('Se encontró un armazón inválido.');
      const id=parseId(item.idProducto,'Producto');
      const qty=parseId(item.cantidad,'Cantidad');
      if (qty>10) throw new Error('Cada línea admite hasta 10 unidades.');
      lines.push({id_producto:id,cantidad:qty,id_mica:null,id_receta:null,solicita_valoracion:false});
      descriptions.push({product:id,quantity:qty,lens:'Sin micas',rx:'Solo armazón'});
    }
    for (const item of rx) {
      if (!item || typeof item!=='object') throw new Error('Se encontró una configuración de micas inválida.');
      const id=parseId(item.idProducto,'Producto');
      const qty=parseId(item.cantidad,'Cantidad');
      const mica=parseId(item.idMica,'Material de mica');
      const receta=parseId(item.idReceta,'Receta',true);
      const valuation=item.solicitaValoracion === true;
      if (qty>10 || (receta!==null && valuation)) throw new Error('Una configuración de micas contiene opciones contradictorias.');
      lines.push({id_producto:id,cantidad:qty,id_mica:mica,id_receta:receta,solicita_valoracion:valuation});
      descriptions.push({product:id,quantity:qty,lens:'Mica #'+mica,rx:receta ? 'Receta #'+receta : (valuation?'Valoración pendiente':'Receta por entregar')});
    }
    if (lines.reduce((sum,x)=>sum+x.cantidad,0)>30) throw new Error('Máximo 30 unidades por cotización.');
    return {active:true,lines,descriptions,signature:JSON.stringify(lines)};
  }
  function setStatus(msg, error=false) {
    const box=$('ivc44Status');box.textContent=msg;box.classList.toggle('is-error',error);
  }
  function clearQuote(reason) {
    quotedId=null;signature='';
    btnPay.disabled=true;
    if(currentStep===2) {
      document.dispatchEvent(new Event('ivc:volver-resumen'));
      setStep(1);
    }
    document.dispatchEvent(new Event('ivc:cotizacion-invalidada'));
    $('ivc44Quote').hidden=true;
    $('ivc44BeforeQuote').hidden=false;
    $('ivc44BeforeQuote').textContent='Calculando automáticamente el total de tu compra…';
    $('ivc44BeforeQuote').classList.add('is-loading');
    if(reason) setStatus(reason);
  }
  function showPreview(data) {
    const body=$('ivc44Preview');body.replaceChildren();
    $('ivc44Count').textContent=String(data.lines.length)+' líneas';
    for(const item of data.descriptions) {
      const tr=make('tr');
      tr.append(make('td','Armazón #'+item.product),make('td',item.rx+' · '+item.lens),make('td',String(item.quantity)));
      body.append(tr);
    }
  }
  async function request(path, options={}) {
    const resp=await fetch(base+'cotizaciones/'+path, {cache:'no-store',credentials:'same-origin',...options});
    const data=await resp.json().catch(()=>{throw new Error('No pudimos obtener la información de tu compra. Inténtalo nuevamente.');});
    if(!resp.ok || data.ok!==true) throw new Error(data.msg || 'No fue posible consultar la cotización.');
    return data;
  }
  function display(data) {
    const quote=data.cotizacion;
    if(!quote || !Array.isArray(quote.items) || !quote.items.length || quote.vencida || quote.moneda!=='MXN' || quote.estado!=='ABIERTA') {
      throw new Error('El resumen de tu compra venció o ya no está disponible. Actualízalo para continuar.');
    }
    if (String(quote.puede_pagarse)==='true') throw new Error('Configuración de pagos inesperada.');
    const r=$('ivc44QuoteItems');r.replaceChildren();
    for(const line of quote.items) {
      const row=make('div',null,'ivc44-item');
      const desc=make('div');desc.append(make('strong',line.nombre_producto_snapshot),
          make('small',line.id_mica ? ('Micas: '+line.nombre_mica_snapshot+' · '+line.estado_optico.replaceAll('_',' ').toLowerCase()) : 'Solo armazón'));
      const amount=Number(line.precio_unitario_armazon) + Number(line.precio_unitario_mica);
      row.append(desc,make('span',formatter.format(amount*Number(line.cantidad))));r.append(row);
    }
    $('ivc44QuoteId').textContent='#'+quote.id_cotizacion;
    $('ivc44QuoteState').textContent='Lista para pagar';
    $('ivc44Frames').textContent=formatter.format(Number(quote.subtotal_armazones));
    $('ivc44Lenses').textContent=formatter.format(Number(quote.subtotal_micas));
    $('ivc44Total').textContent=formatter.format(Number(quote.total));
    $('ivc44Quote').hidden=false;$('ivc44BeforeQuote').hidden=true;
    $('ivc44BeforeQuote').classList.remove('is-loading');
    quotedId=quote.id_cotizacion;
    $('ivc44PaymentTotal').textContent=formatter.format(Number(quote.total));
    btnPay.disabled=false;
    // IVC_F45_EVENT_QUOTES: avisar a PayPal Sandbox cuando el total ya fue verificado.
    document.dispatchEvent(new CustomEvent('ivc:cotizacion-lista', {detail:{id:quote.id_cotizacion}}));
    setStatus('Este es el total de tu compra. Todavía no se ha realizado ningún cargo.');
  }
  async function generate() {
    if (busy) {needsRequote = true; return;}
    let current;
    try {current=payload();if(!current.active) throw new Error('Agrega productos al carrito antes de continuar.');}
    catch(e){clearQuote('');setStatus(e.message,true);$('ivc44BeforeQuote').textContent='No hay un total disponible. Revisa tu carrito y vuelve a intentarlo.';$('ivc44BeforeQuote').classList.remove('is-loading');return;}
    busy=true;needsRequote=false;
    clearQuote('Estamos verificando los precios y la disponibilidad de tus productos…');
    try {
      const auth=await request('token');
      const saved=await request('crear',{
        method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({csrf:auth.csrf,productos:current.lines})
      });
      const fresh=payload();
      if(fresh.signature!==current.signature) throw new Error('El carrito cambió mientras cotizábamos. Genera una nueva cotización.');
      if(!saved.cotizacion || !positiveInt(saved.cotizacion.id_cotizacion)) throw new Error('No se recibió el identificador de la cotización.');
      const verified=await request('ver/'+saved.cotizacion.id_cotizacion);
      if (verified.cotizacion.items.length!==current.lines.length) throw new Error('La respuesta no incluye todos los artículos del carrito.');
      // Confirmar que el snapshot devuelto por PHP conserva exactamente
      // la composición y el orden solicitados: nunca mostrar un total ajeno.
      for (let i=0;i<current.lines.length;i++) {
        const expected=current.lines[i],actual=verified.cotizacion.items[i];
        if (Number(actual.id_producto)!==expected.id_producto ||
            Number(actual.cantidad)!==expected.cantidad ||
            (actual.id_mica==null?null:Number(actual.id_mica))!==expected.id_mica ||
            (actual.id_receta==null?null:Number(actual.id_receta))!==expected.id_receta ||
            Boolean(Number(actual.solicita_valoracion))!==expected.solicita_valoracion) {
          throw new Error('La cotización recibida no coincide con todos los productos. Reintenta.');
        }
      }
      signature=current.signature;
      display(verified);
    } catch(e) {
      clearQuote('');
      $('ivc44BeforeQuote').textContent='No pudimos preparar el total de tu compra. Verifica el carrito y vuelve a entrar a esta pantalla.';
      $('ivc44BeforeQuote').classList.remove('is-loading');
      setStatus(e.message,true);
    } finally {
      busy=false;
      if (needsRequote) {needsRequote=false;scheduleQuote();}
    }
  }
  function scheduleQuote() {
    if (refreshTimer!==null) clearTimeout(refreshTimer);
    refreshTimer=window.setTimeout(() => {refreshTimer=null;generate();}, 200);
  }
  function init() {
    let data;
    try {data=payload();}
    catch(e) {data={active:true,lines:[],descriptions:[]};setStatus(e.message,true);}
    root.hidden=false;
    document.body.classList.add('ivc44-with-rx');
    // TODAS las ventas usan la API PHP: se deshabilita el PayPal heredado con importe JS.
    const old=$('ivcCheckoutFlow');if(old) old.hidden=true;
    const oldPay=$('ivcPayReal');if(oldPay) {oldPay.hidden=true;oldPay.style.display='none';}
    const oldButtons=$('paypal-button-container');if(oldButtons) oldButtons.style.display='none';
    if(data.lines.length) showPreview(data);
    else {
      $('ivc44Preview').replaceChildren(make('tr','Tu carrito está vacío.'));
      $('ivc44BeforeQuote').textContent='Agrega un armazón para comenzar tu compra.';
      $('ivc44BeforeQuote').classList.remove('is-loading');
      setStatus('Tu carrito está vacío.');
    }
    document.addEventListener('ivc:carrito-actualizado',invalidate);
    window.addEventListener('storage',event=>{if([KEY_RX,KEY_PLAIN].includes(event.key)) invalidate();});
    // Calcular siempre en PHP sin añadir un paso manual de revisión de total.
    if(data.active && data.lines.length) scheduleQuote();
  }
  function invalidate() {
    let data;
    try {data=payload();}catch(e){clearQuote('');setStatus(e.message,true);return;}
    if(!data.active) {
      if (refreshTimer!==null) {clearTimeout(refreshTimer);refreshTimer=null;}
      needsRequote=false;
      clearQuote('El carrito está vacío. Agrega un armazón para continuar.');
      $('ivc44BeforeQuote').textContent='Agrega un armazón para comenzar tu compra.';
      $('ivc44BeforeQuote').classList.remove('is-loading');
      $('ivc44Preview').replaceChildren(make('tr','Tu carrito está vacío.'));
      return;
    }
    showPreview(data);
    // Revalidar en PHP si cambió el contenido, incluso mientras hay una solicitud en curso.
    if (data.signature !== signature) {
      clearQuote('Tu carrito cambió. Estamos preparando el nuevo total…');
      scheduleQuote();
    }
  }
  window.IVCCheckout44={generate,getQuoteId:()=>quotedId,hasQuote:()=>Boolean(quotedId),isPaymentStep:()=>currentStep===2};
  init();
})();
