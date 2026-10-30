/** IVC 4.6 — PayPal SOLO SANDBOX, pedidos transaccionales y conciliación sin recobro. */
(() => {
  'use strict';
  const box = document.getElementById('ivc45Sandbox');
  if (!box || window.IVCPaypal45) return;
  const root = document.getElementById('ivcCheckoutQuote44');
  const base = root?.dataset.base || '/institutovc/';
  const status = document.getElementById('ivc45Status');
  const buttonContainer = document.getElementById('ivc45Buttons');
  const recovery = document.getElementById('ivc46Recovery');
  const CACHE = 'ivc46OrdenEnConciliacion';
  let quoteId = null, csrf = null, sdkPromise = null, rendering = false;
  let completed = false;
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
    if (!quoteId || String(current) !== String(quoteId) || !window.IVCCheckout44?.hasQuote?.()) {
      throw new Error('La cotización cambió. Vuelve a validar los precios antes de pagar.');
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
    const saved=button?.textContent || '';
    if (button) {button.disabled=true;button.textContent='Verificando sin volver a cobrar…';}
    try {
      await getToken();
      const result=await api('recuperar',{id_cotizacion:Number(id)});
      const text=finish(result,false);
      if (result.pedido_creado) await listPending();
      if (recovery) {
        const note=document.createElement('p');
        note.textContent=text;
        note.className=result.pedido_creado ? 'ivc46-recover-ok' : 'ivc46-recover-alert';
        recovery.prepend(note);
        botonValoracion(note,result);
      }
    } catch (error) {
      if (recovery) {
        const note=document.createElement('p'); note.className='ivc46-recover-alert';
        note.textContent='No pudimos confirmar esta compra. No se realizó un nuevo cargo. Vuelve a consultar su estado o solicita ayuda.';
        recovery.prepend(note);
      }
    } finally {if(button){button.disabled=false;button.textContent=saved;}}
  }
  async function listPending() {
    if (!recovery) return;
    try {
      const result=await api('pendientes');
      const candidates=new Map();
      for (const item of result.capturas || []) candidates.set(Number(item.id_cotizacion),item);
      const stored=readCache();
      if (stored && !candidates.has(Number(stored.id_cotizacion))) {
        candidates.set(Number(stored.id_cotizacion),{id_cotizacion:stored.id_cotizacion,temporal:true});
      }
      recovery.replaceChildren();
      if (!candidates.size) return;
      const title=document.createElement('strong');
      title.textContent='Compras pendientes de confirmación';
      recovery.append(title);
      const text=document.createElement('p');
      text.textContent='Puedes consultar el estado de estas compras sin realizar otro pago:';
      recovery.append(text);
      for (const [index, item] of [...candidates.values()].entries()) {
        const button=document.createElement('button');
        button.type='button';button.className='ivc46-recover-btn';
        button.textContent='Consultar estado de compra '+String(index+1);
        button.addEventListener('click',()=>recoverQuote(item.id_cotizacion,button));
        recovery.append(button);
      }
    } catch(error) {console.warn('IVC conciliación',error.message);}
  }
  async function render(quote) {
    if (rendering || completed) return;
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
            if (!result.pedido_creado) listPending();
            else if(recovery) recovery.replaceChildren();
          } catch(error) {
            msg('No pudimos confirmar el resultado del pago. No vuelvas a pagar; utiliza «Consultar estado de compra» para verificar tu pedido.',true);
            listPending();
          }
        },
        onCancel: () => msg('El pago se canceló. Los productos apartados volverán a estar disponibles cuando venza la reserva.'),
        onError: error => {console.error('IVC PayPal Sandbox',error);msg('Ocurrió un problema al procesar el pago. Revisa su estado antes de intentarlo otra vez.',true);listPending();},
      });
      if (!instance.isEligible()) throw new Error('PayPal no está disponible con esta cuenta en este momento.');
      await instance.render('#ivc45Buttons');
      msg('Selecciona cómo deseas continuar con el pago.');
    } catch(error) {msg(error.message,true);}
    finally {rendering=false;}
  }
  document.addEventListener('ivc:cotizacion-lista', event => {
    const id=Number(event.detail?.id);
    if (!Number.isSafeInteger(id)||id<=0) return;
    if (quoteId===id && !box.hidden) return;
    render(id);
  });
  document.addEventListener('ivc:cotizacion-invalidada', () => {
    quoteId=null;csrf=null;
    if(!completed){box.hidden=true;buttonContainer.replaceChildren();msg('');}
  });
  window.IVCPaypal45={getQuoteId:()=>quoteId, recuperar:recoverQuote};
  listPending();
})();
