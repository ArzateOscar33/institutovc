/** IVC 4.6 — PayPal SOLO SANDBOX, pedidos transaccionales y conciliación sin recobro. */
(() => {
  'use strict';
  const box = document.getElementById('ivc45Sandbox');
  if (!box || window.IVCPaypal45) return;
  const root = document.getElementById('ivcCheckoutQuote44');
  const base = root?.dataset.base || '/institutovc/';
  const status = document.getElementById('ivc45Status');
  const buttonContainer = document.getElementById('ivc45Buttons');
  // Sin listado permanente de cotizaciones antiguas en la pantalla de pago.
  const CACHE = 'ivc46OrdenEnConciliacion';
  let quoteId = null, csrf = null, sdkPromise = null, rendering = false;
  let completed = false;
  let selectedPaymentStep = false;
  let availableQuoteId = null;
  let queuedPaymentRender = null;
  const msg = (text, error = false) => {
    status.textContent = text;
    status.classList.toggle('is-error', error);
  };
  async function api(path, body) {
    const reply = await fetch(base + 'pagos/' + path, {
      credentials: 'same-origin', cache: 'no-store',
      ...(body ? {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...body,csrf})}
                 : {method:'GET'}),
    });
    const result = await reply.json().catch(() => {throw new Error('No pudimos obtener la respuesta del servicio. Inténtalo nuevamente.');});
    if (!reply.ok || result.ok !== true) throw new Error(result.msg || 'No se pudo completar la operación.');
    return result;
  }
  async function getToken() {
    const response = await fetch(base+'cotizaciones/token',{credentials:'same-origin',cache:'no-store'});
    const data = await response.json();
    if (!response.ok || !data.ok || !data.csrf) throw new Error(data.msg || 'Sesión de cliente no válida.');
    csrf=data.csrf;
  }
  const loadSdk = clientId => {
    if (sdkPromise) return sdkPromise;
    sdkPromise = new Promise((resolve,reject) => {
      const script = document.createElement('script');
      const params=new URLSearchParams({
        'client-id':clientId, currency:'MXN', intent:'capture', components:'buttons'
      });
      script.src='https://www.paypal.com/sdk/js?'+params.toString();
      script.setAttribute('data-namespace', 'paypalIVCSandbox');
      script.async=true;
      script.onload=()=>window.paypalIVCSandbox?.Buttons ? resolve(window.paypalIVCSandbox) : reject(new Error('No pudimos preparar el pago con PayPal.'));
      script.onerror=()=>reject(new Error('No fue posible conectar con PayPal. Revisa tu conexión.'));
      document.head.append(script);
    });
    return sdkPromise;
  };
  function assertQuote() {
    const current=window.IVCCheckout44?.getQuoteId?.();
    if (!selectedPaymentStep || !window.IVCCheckout44?.isPaymentStep?.() || !quoteId || String(current) !== String(quoteId) || !window.IVCCheckout44?.hasQuote?.()) {
      throw new Error('Tu compra cambió. Regresa al carrito para revisar los productos antes de pagar.');
    }
  }
  function readCache() {
    try {
      const obj=JSON.parse(sessionStorage.getItem(CACHE) || 'null');
      return obj && Number.isSafeInteger(obj.id_cotizacion) && obj.id_cotizacion>0 ? obj : null;
    } catch (_) {return null;}
  }
  function remember(id, orderId) {
    try {sessionStorage.setItem(CACHE,JSON.stringify({id_cotizacion:Number(id),order_id:orderId}));}
    catch (_) { /* La conciliación también queda accesible en MySQL. */ }
  }
  let successModalDisplayed = false;
  const money = value => new Intl.NumberFormat('es-MX', {style:'currency',currency:'MXN'}).format(Number(value) || 0);
  function confirmacionExitosa(result) {
    if (successModalDisplayed || !result?.pedido_creado || !Number.isSafeInteger(Number(result.id_pedido))) return;
    successModalDisplayed = true;
    const target = base; // La ruta principal; no seguir el estado de la pantalla de checkout.
    const id = Number(result.id_pedido);
    const correo = result.correo_estado === 'ENVIADO'
      ? 'Te enviamos el comprobante y el estado de tu pedido a tu correo electrónico.'
      : 'Puedes consultar tu pedido en «Mis pedidos». Si el correo tarda en llegar, puedes consultar tu compra desde «Mis pedidos».';
    const detalle = result.requiere_valoracion
      ? 'Tu pedido necesita una valoración visual antes de preparar tus lentes.'
      : 'Revisaremos tu receta antes de preparar tus lentes, cuando corresponda.';
    const contenido = `<div class="ivc-confirmacion-detalle">` +
      `<div class="ivc-confirmacion-numero">Pedido <strong>#${id}</strong></div>` +
      `<div class="ivc-confirmacion-total">${money(result.total)}</div>` +
      `<p>Tu pago fue confirmado correctamente.</p>` +
      `<p>${detalle}</p><p class="ivc-confirmacion-correo">${correo}</p>` +
      `</div>`;
    let redirected = false;
    const go = () => { if (redirected) return; redirected = true; window.location.assign(target); };
    // Seguridad: el temporizador corre aunque la librería tarde en abrir el modal.
    const fallback = window.setTimeout(go, 7000);
    if (window.Swal?.fire) {
      window.Swal.fire({
        icon:'success', title:'¡Compra confirmada!', html:contenido,
        confirmButtonText:'Aceptar', showConfirmButton:true,
        buttonsStyling:false, customClass:{popup:'ivc-confirmacion-modal',confirmButton:'ivc-confirmacion-aceptar'},
        timer:7000, timerProgressBar:true, allowOutsideClick:false,
        allowEscapeKey:false, returnFocus:false,
      }).then(go).catch(go);
    } else {
      // Accesibilidad sin dependencia externa: botón y redirección automática.
      const overlay = document.createElement('div'); overlay.className='ivc-confirmacion-fallback';
      overlay.setAttribute('role','dialog'); overlay.setAttribute('aria-modal','true');
      overlay.setAttribute('aria-label','Compra confirmada');
      const card=document.createElement('div');card.className='ivc-confirmacion-modal';
      const title=document.createElement('h2');title.textContent='¡Compra confirmada!';
      const details=document.createElement('div');details.className='ivc-confirmacion-detalle';
      const order=document.createElement('p');order.textContent=`Pedido #${id} · ${money(result.total)}`;
      const note=document.createElement('p');note.textContent=detalle+' '+correo;
      const accept=document.createElement('button'); accept.type='button';accept.textContent='Aceptar';
      accept.className='ivc-confirmacion-aceptar';accept.addEventListener('click',go);
      details.append(order,note);card.append(title,details,accept);overlay.append(card);
      document.body.append(overlay);accept.focus();
    }
    // Mantener el temporizador aunque el cuadro desaparezca por una recarga accidental.
    void fallback;
  }
  function botonValoracion(container, result) {
    if (!container || !result?.pedido_creado || !result.requiere_valoracion || !Number.isSafeInteger(Number(result.id_pedido))) return;
    const a=document.createElement('a');a.href=base+'principal/agendar?pedido='+encodeURIComponent(result.id_pedido);
    a.textContent='Agendar valoración de tu pedido #'+result.id_pedido;
    a.className='btn btn-outline-primary btn-sm mt-2';a.style.display='inline-block';
    container.append(document.createElement('br'),a);
  }
  function finish(result, fromPayment) {
    if (result.pedido_creado) {
      if (fromPayment) {
        // El checkout ya fue verificado contra el carrito actual; no cambiar citas ni precios.
        localStorage.removeItem('listaCarrito');
        localStorage.removeItem('ivcCarritoConfiguradoV43');
      }
      try {
        const old=readCache();
        if (old && (fromPayment || Number(old.id_cotizacion)===Number(result.id_cotizacion))) sessionStorage.removeItem(CACHE);
      } catch (_) { /* Opcional */ }
      const citas=result.requiere_valoracion ? ' Requiere agendar valoración antes de fabricar.' : '';
      return `Tu pedido #${result.id_pedido} está confirmado. Total: ${result.total} MXN.${citas}`;
    }
    return `Recibimos la confirmación del pago por ${result.total} MXN, pero tu pedido aún está pendiente de confirmación. No intentes pagar otra vez. Utiliza «Consultar estado de compra» o contacta a la óptica.`;
  }
  async function recoverQuote(id, button) {
    if (!Number.isSafeInteger(Number(id)) || Number(id) < 1) return;
    if (button) {button.disabled=true;button.textContent='Consultando el pago…';}
    try {
      await getToken();
      const result=await api('recuperar',{id_cotizacion:Number(id)});
      msg(finish(result,false),!result.pedido_creado);
      botonValoracion(status,result);
      if (!result.pedido_creado) offerRecovery(id);
      else confirmacionExitosa(result);
    } catch (error) {
      msg('No pudimos confirmar este pago. No vuelvas a pagar; contacta a la óptica para revisar tu compra.',true);
      offerRecovery(id);
    } finally {
      if (button) {button.disabled=false;button.textContent='Consultar estado del pago';}
    }
  }
  function offerRecovery(id) {
    if (!Number.isSafeInteger(Number(id)) || Number(id)<1) return;
    if (status.querySelector('.ivc45-recheck')) return;
    const button=document.createElement('button');
    button.type='button';button.className='ivc45-recheck';
    button.textContent='Consultar estado del pago';
    button.addEventListener('click',()=>recoverQuote(id,button));
    status.append(document.createElement('br'),button);
  }
  function offerCurrentRecovery() {
    const saved=readCache();
    if (saved) offerRecovery(saved.id_cotizacion);
  }
  async function render(quote) {
    if (!selectedPaymentStep || !window.IVCCheckout44?.isPaymentStep?.() || completed) return;
    if (rendering) {queuedPaymentRender=quote;return;}
    if (quoteId===quote && !box.hidden && buttonContainer.hasChildNodes()) return;
    rendering=true;quoteId=quote;
    box.hidden=false;
    buttonContainer.replaceChildren();
    msg('Preparando las opciones de pago…');
    try {
      await getToken();
      const config=await api('configuracion');
      if (!config.sandbox || config.currency!=='MXN' || !config.client_id) throw new Error('Este medio de pago no está disponible por el momento. Contacta a la óptica.');
      const sdk=await loadSdk(config.client_id);
      assertQuote();
      const instance=sdk.Buttons({
        style:{color:'blue',shape:'pill',label:'paypal',height:42},
        createOrder: async () => {
          assertQuote();
          const result=await api('crear',{id_cotizacion:Number(quoteId)});
          assertQuote();
          remember(quoteId,result.order_id);
          if (result.reserva_expira_en) msg('Tus productos están apartados temporalmente. Completa el pago dentro del tiempo indicado para mantener la disponibilidad.');
          return result.order_id;
        },
        onApprove: async data => {
          try {
            assertQuote();
            const result=await api('capturar',{id_cotizacion:Number(quoteId),order_id:data.orderID});
            completed=true;
            msg(finish(result,true),!result.pedido_creado);
            botonValoracion(status,result);
            buttonContainer.replaceChildren();
            box.classList.add('ivc45-complete');
            if (!result.pedido_creado) offerRecovery(quoteId);
            else confirmacionExitosa(result);
          } catch(error) {
            msg('No pudimos confirmar el resultado del pago. No vuelvas a pagar; utiliza «Consultar estado de compra» para verificar tu pedido.',true);
            offerCurrentRecovery();
          }
        },
        onCancel: () => msg('El pago se canceló. Los productos apartados volverán a estar disponibles cuando venza la reserva.'),
        onError: error => {console.error('IVC PayPal',error);msg('No pudimos confirmar el resultado del pago. Revisa su estado antes de intentarlo otra vez.',true);offerCurrentRecovery();},
      });
      if (!instance.isEligible()) throw new Error('PayPal no está disponible con esta cuenta en este momento.');
      await instance.render('#ivc45Buttons');
      msg('Selecciona cómo deseas continuar con el pago.');
    } catch(error) {msg(error.message,true);}
    finally {
      rendering=false;
      if (queuedPaymentRender && selectedPaymentStep && window.IVCCheckout44?.isPaymentStep?.()) {
        const next=queuedPaymentRender;
        queuedPaymentRender=null;
        if (Number(next)===Number(availableQuoteId)) render(next);
      } else queuedPaymentRender=null;
    }
  }
  document.addEventListener('ivc:cotizacion-lista', event => {
    const id=Number(event.detail?.id);
    if (!Number.isSafeInteger(id)||id<=0) return;
    availableQuoteId=id;
    // Mostrar opciones de pago solo cuando el cliente entra al segundo paso.
  });
  document.addEventListener('ivc:mostrar-pago', event => {
    const id=Number(event.detail?.id);
    if (!Number.isSafeInteger(id)||id<=0 || id!==availableQuoteId) return;
    selectedPaymentStep=true;
    render(id);
  });
  document.addEventListener('ivc:volver-resumen',()=>{selectedPaymentStep=false;});
  document.addEventListener('ivc:cotizacion-invalidada', () => {
    quoteId=null;csrf=null;availableQuoteId=null;selectedPaymentStep=false;queuedPaymentRender=null;
    if(!completed){box.hidden=true;buttonContainer.replaceChildren();msg('');}
  });
  window.IVCPaypal45={getQuoteId:()=>quoteId, recuperar:recoverQuote};
})();
