/* IVC 4.9.5.2. Carrito transitorio, sin cobros, reservas o escrituras en MySQL. */
(() => {
  'use strict';
  const root = document.getElementById('ivcPosVenta'); if (!root) return;
  const $ = id => document.getElementById(id);
  const turno = root.dataset.turno === '1';
  const descuentoPermitido = root.dataset.descuentos === '1';
  const base = root.dataset.base;
  const moneda = c => new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(c / 100);
  // Usar centavos para no acumular errores por coma flotante.
  const centavos = valor => {const t=String(valor??'0').trim();const m=t.match(/^(\d+)(?:\.(\d{1,2}))?$/);return m?Number(m[1])*100+Number((m[2]||'').padEnd(2,'0')):0;};
  const elemento = (tipo, texto, clase) => {const e=document.createElement(tipo);if(texto!==undefined&&texto!==null)e.textContent=String(texto);if(clase)e.className=clase;return e;};
  const carrito = new Map();
  let cliente = null, categoria = 0, pagina = 1, hayMas = false, buscando = 0, clienteToken = 0;
  let ultimaValidacion = null;
  const avisar = async (texto, icono='warning') => {
    if (window.Swal && typeof Swal.fire==='function') await Swal.fire({icon:icono,title:icono==='error'?'No se pudo completar':'Aviso',text:String(texto),confirmButtonColor:'#137fad'});
    else window.alert(texto);
  };
  async function api(endpoint, args=null) {
    const opts = {method: args instanceof FormData?'POST':'GET', headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin',cache:'no-store'};
    if (args instanceof FormData){args.set('csrf',root.dataset.csrf);opts.body=args;}
    const url = base + 'pos/' + endpoint + ((args && !(args instanceof FormData))?'?'+new URLSearchParams(args):'');
    let resp; try{resp=await fetch(url,opts);}catch{throw Error('Sin conexión con el servidor.');}
    let json;try{json=await resp.json();}catch{throw Error('El servidor devolvió una respuesta inesperada.');}
    if (!resp.ok || !json.ok) throw Error(json.msg||'La operación no pudo completarse.');
    return json.datos;
  }
  function rutaImagen(url) {
    const v=String(url||'').trim();
    if (/^https?:\/\//i.test(v)) return v;
    if (/^assets\/images\/productos\/[a-zA-Z0-9_./-]+$/.test(v) && !v.includes('..')) return base+v;
    return '';
  }
  function limpiarProductos(){ $('pvProductos').replaceChildren(); }
  function mostrarProducto(p) {
    const card=elemento('article',null,'ivc-pv-producto');
    const foto=elemento('div',null,'ivc-pv-foto');const src=rutaImagen(p.imagen);
    if (src) {const img=document.createElement('img');img.src=src;img.alt=String(p.nombre||'Armazón');img.loading='lazy';img.referrerPolicy='no-referrer';img.addEventListener('error',()=>img.replaceWith(elemento('i',null,'bx bx-glasses')), {once:true});foto.append(img);}
    else foto.append(elemento('i',null,'bx bx-glasses'));
    const stock=elemento('span',p.cantidad>0?'Disp. '+p.cantidad:'Sin existencias','ivc-pv-stock'+(p.cantidad>0?'':' agotado'));foto.append(stock);
    const datos=elemento('div',null,'ivc-pv-datos');
    datos.append(elemento('span',p.categoria||'Sin categoría','ivc-pv-categoria'));
    datos.append(elemento('h3',p.nombre,'ivc-pv-nombre'));
    datos.append(elemento('span','ID producto: '+p.id,'ivc-pv-codigo'));
    datos.append(elemento('strong',moneda(centavos(p.precio)),'ivc-pv-precio'));
    const btn=elemento('button',p.cantidad>0?'＋ Agregar al carrito':'Agotado','ivc-pv-agregar');btn.type='button';btn.disabled=p.cantidad<1;
    btn.addEventListener('click',()=>agregar(p));datos.append(btn);card.append(foto,datos);return card;
  }
  async function categoriasCargar() {
    const cats=await api('categoriasventa');const nav=$('pvCategorias');nav.replaceChildren();
    const crear=(id,nombre)=>{const btn=elemento('button',nombre,'ivc-pv-cat'+(id===categoria?' activo':''));btn.type='button';btn.dataset.categoria=id;btn.addEventListener('click',()=>{categoria=id;nav.querySelectorAll('button').forEach(b=>b.classList.toggle('activo',Number(b.dataset.categoria)===id));catalogoCargar(true);});nav.append(btn);};
    crear(0,'Todos');for(const c of cats) crear(Number(c.id),c.categoria);
  }
  async function catalogoCargar(nuevo=true) {
    if(!turno)return;
    if(nuevo){pagina=1;limpiarProductos();}
    const token=++buscando;const texto=$('pvBuscar').value.trim();
    $('pvMensaje').textContent='Consultando productos…';$('pvCargarMas').hidden=true;
    try{
      const d=await api('buscarproductos',{q:texto,categoria:String(categoria),pagina:String(pagina),disponibles:$('pvDisponibles').checked?'1':'0'});
      if(token!==buscando)return;
      hayMas=d.hay_mas;
      for(const p of d.productos)$('pvProductos').append(mostrarProducto(p));
      $('pvMensaje').textContent=$('pvProductos').childElementCount ? 'Productos del catálogo · pulsa Agregar para construir tu carrito.' : 'No se encontraron productos para esos filtros.';
      $('pvCargarMas').hidden=!hayMas;
    }catch(e){if(token!==buscando)return;$('pvMensaje').textContent='No fue posible cargar los productos.';await avisar(e.message,'error');}
  }
  function invalidar(){ultimaValidacion=null;$('pvValidacion').className='ivc-pv-validacion';$('pvValidacion').textContent='Importes preliminares. Usa «Validar carrito y total» para comprobar precios y existencias actuales.';}
  function agregar(p) {
    const id=Number(p.id), existente=carrito.get(id), anterior=existente?existente.cantidad:0;
    if(anterior>=Number(p.cantidad) || anterior>=99) {avisar('No puedes agregar más unidades de las disponibles.');return;}
    carrito.set(id,{id,nombre:String(p.nombre),precio:centavos(p.precio),cantidad:anterior+1,disponibles:Number(p.cantidad)});
    renderCarrito();
  }
  function variar(id,incremento) {
    const item=carrito.get(id);if(!item)return;
    const nuevo=item.cantidad+incremento;
    if(nuevo<1)carrito.delete(id);
    else if(nuevo<=Math.min(item.disponibles,99))item.cantidad=nuevo;
    else {avisar('Se alcanzó el máximo disponible para este producto.');return;}
    renderCarrito();
  }
  function renderCarrito(){
    invalidar();const cont=$('pvCarrito');cont.replaceChildren();
    let piezas=0,subtotal=0;
    for(const item of carrito.values()){
      piezas+=item.cantidad;subtotal+=item.precio*item.cantidad;
      const renglon=elemento('div',null,'ivc-pv-linea');const info=elemento('div',null,'ivc-pv-linea-info');
      info.append(elemento('div',item.nombre,'ivc-pv-linea-nombre'),elemento('small','ID '+item.id+' · '+moneda(item.precio)+' c/u'));
      const acciones=elemento('div',null,'ivc-pv-linea-controles');
      const menos=elemento('button','−');menos.type='button';menos.title='Quitar una unidad';menos.addEventListener('click',()=>variar(item.id,-1));
      const cantidad=elemento('span',item.cantidad);
      const mas=elemento('button','+');mas.type='button';mas.title='Agregar una unidad';mas.disabled=item.cantidad>=Math.min(item.disponibles,99);mas.addEventListener('click',()=>variar(item.id,+1));
      const quitar=elemento('button','×','ivc-pv-quitar');quitar.type='button';quitar.title='Quitar producto';quitar.addEventListener('click',()=>{carrito.delete(item.id);renderCarrito();});
      acciones.append(menos,cantidad,mas,quitar);info.append(acciones);
      renglon.append(info,elemento('strong',moneda(item.precio*item.cantidad),'ivc-pv-linea-subtotal'));cont.append(renglon);
    }
    if(!carrito.size){const vacio=elemento('div',null,'ivc-pv-empty');vacio.append(elemento('i',null,'bx bx-shopping-bag'),elemento('strong','Tu carrito está vacío'),elemento('span','Agrega un armazón o un producto para comenzar.'));cont.append(vacio);}
    $('pvCantidad').textContent=String(piezas);$('pvLimpiar').disabled=!carrito.size;$('pvPrevisualizar').disabled=!carrito.size;
    const porcentaje=descuentoPermitido?Math.min(100,Math.max(0,Number($('pvDescuento').value||0))):0;
    const rebaja=Math.floor((subtotal*porcentaje+50)/100);$('pvSubtotal').textContent=moneda(subtotal);
    $('pvDescuentoImporte').textContent='−'+moneda(rebaja);$('pvTotal').textContent=moneda(subtotal-rebaja);
  }
  async function clientesBuscar(texto){
    const token=++clienteToken,contenedor=$('pvClientes');contenedor.replaceChildren();
    if(texto.trim().length<2){contenedor.hidden=true;return;}
    try {
      const lista=await api('buscarclientes',{q:texto.trim()});if(token!==clienteToken)return;
      for(const c of lista){const nombre=[c.nombre,c.apellidoP,c.apellidoM].filter(Boolean).join(' ');
        const opcion=elemento('button',null,'ivc-pv-cliente-opcion');opcion.type='button';opcion.setAttribute('role','option');
        opcion.append(elemento('span',nombre),elemento('small',c.correo));
        opcion.addEventListener('click',()=>{cliente={id:c.id,nombre};$('pvClienteSeleccionado').firstChild.textContent=nombre+' · #'+c.id;$('pvSinCliente').hidden=false;$('pvClienteBusqueda').value='';contenedor.hidden=true;invalidar();});contenedor.append(opcion);
      }
      if(!lista.length)contenedor.append(elemento('div','Sin clientes coincidentes.','ivc-pv-cliente-opcion'));
      contenedor.hidden=false;
    }catch(e){if(token!==clienteToken)return;contenedor.hidden=true;await avisar(e.message,'error');}
  }
  async function previsualizar(){
    if(!carrito.size)return;
    const descuento=descuentoPermitido?$('pvDescuento').value:'0';
    if(!/^\d+$/.test(descuento)||Number(descuento)>100){avisar('El descuento debe ser un entero de 0 a 100.');return;}
    const f=new FormData();f.set('items',JSON.stringify([...carrito.values()].map(p=>({id:p.id,cantidad:p.cantidad}))));f.set('cliente',String(cliente?.id||0));f.set('descuento',descuento);
    const button=$('pvPrevisualizar');button.disabled=true;$('pvValidacion').textContent='Validando precios y existencias contra el servidor…';
    try{
      const d=await api('previsualizar',f);ultimaValidacion=d;
      $('pvSubtotal').textContent=moneda(centavos(d.subtotal));$('pvDescuentoImporte').textContent='−'+moneda(centavos(d.descuento_importe));$('pvTotal').textContent=moneda(centavos(d.total));
      $('pvValidacion').className='ivc-pv-validacion ok';$('pvValidacion').textContent='✓ Carrito validado: '+d.unidades+' unidad(es). Total: '+moneda(centavos(d.total))+'. No se creó un pedido, no se reservó inventario ni se hizo ningún cobro.';
    }catch(e){ultimaValidacion=null;$('pvValidacion').className='ivc-pv-validacion error';$('pvValidacion').textContent='No validado: '+e.message;await avisar(e.message,'error');}
    finally{button.disabled=!carrito.size;}
  }
  let timer;
  $('pvBuscar').addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(()=>catalogoCargar(true),320);});
  $('pvDisponibles').addEventListener('change',()=>catalogoCargar(true));
  $('pvCargarMas').addEventListener('click',()=>{if(hayMas){pagina++;catalogoCargar(false);}});
  $('pvLimpiar').addEventListener('click',async()=>{const ok=window.Swal?.fire?(await Swal.fire({icon:'question',title:'¿Vaciar carrito?',showCancelButton:true,confirmButtonText:'Sí, vaciar',cancelButtonText:'Conservar'})).isConfirmed:window.confirm('¿Vaciar el carrito?');if(ok){carrito.clear();renderCarrito();}});
  let clienteTimer;
  $('pvClienteBusqueda').addEventListener('input',()=>{clearTimeout(clienteTimer);clienteTimer=setTimeout(()=>clientesBuscar($('pvClienteBusqueda').value),320);});
  $('pvSinCliente').addEventListener('click',()=>{cliente=null;$('pvClienteSeleccionado').firstChild.textContent='Venta de mostrador ';$('pvSinCliente').hidden=true;invalidar();});
  $('pvDescuento').addEventListener('input',renderCarrito);
  $('pvPrevisualizar').addEventListener('click',previsualizar);
  document.addEventListener('click',e=>{if(!e.target.closest('#pvClientes')&&!e.target.closest('#pvClienteBusqueda'))$('pvClientes').hidden=true;});
  if(turno){Promise.allSettled([categoriasCargar(),catalogoCargar(true)]).then(resultados=>{for(const r of resultados)if(r.status==='rejected')avisar(r.reason?.message||'Error de catálogo','error');});}
  else $('pvMensaje').textContent='Abre una caja para consultar el catálogo y preparar una venta.';
})();
