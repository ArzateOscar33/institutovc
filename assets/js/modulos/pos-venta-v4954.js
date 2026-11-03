/* IVC 4.9.5.4 · Cobro definitivo; validación y reserva transaccional en PHP/MySQL. */
(() => {
  'use strict';
  const root=document.getElementById('ivcPosVenta');if(!root)return;
  const $=id=>document.getElementById(id);
  const turno=root.dataset.turno==='1',descuentoPermitido=root.dataset.descuentos==='1',puedeCobrar=root.dataset.cobrar==='1',base=root.dataset.base;
  const moneda=c=>new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(c/100);
  const centavos=v=>{const m=String(v??'0').trim().match(/^(\d+)(?:\.(\d{1,2}))?$/);return m?Number(m[1])*100+Number((m[2]||'').padEnd(2,'0')):0;};
  const elemento=(tag,text,css)=>{const e=document.createElement(tag);if(text!==undefined&&text!==null)e.textContent=String(text);if(css)e.className=css;return e;};
  const carrito=new Map();let secuencia=0;
  let solicitudCobro=null, guardandoCobro=false;
  let cliente=null,categoria=0,pagina=1,hayMas=false,buscando=0,clienteToken=0,validacionToken=0;
  let micas=[],recetas=[],opticaLinea=null,ultimaValidacion=null;
  const avisar=async(mensaje,icono='warning')=>{
    if(window.Swal?.fire)await Swal.fire({icon:icono,title:icono==='error'?'No se pudo completar':'Aviso',text:String(mensaje),confirmButtonColor:'#137fad'});
    else window.alert(String(mensaje));
  };
  async function api(endpoint,args=null){
    const post=args instanceof FormData;
    const opts={method:post?'POST':'GET',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',cache:'no-store'};
    if(post){args.set('csrf',root.dataset.csrf);opts.body=args;}
    const url=base+'pos/'+endpoint+(!post&&args?'?'+new URLSearchParams(args):'');
    let resp;try{resp=await fetch(url,opts);}catch{throw Error('Sin conexión con el servidor.');}
    let json;try{json=await resp.json();}catch{throw Error('El servidor devolvió una respuesta inesperada.');}
    if(!resp.ok||!json.ok)throw Error(json.msg||'La operación no pudo completarse.');
    return json.datos;
  }
  const cambiarValidacion=()=>{
    validacionToken++;ultimaValidacion=null;solicitudCobro=null;
    if($('pvCobrar'))$('pvCobrar').disabled=true;
    $('pvValidacion').className='ivc-pv-validacion';
    $('pvValidacion').textContent='Importes preliminares. Usa «Validar carrito y total» para comprobar existencias, micas y recetas en MySQL.';
  };
  function rutaImagen(url){
    const v=String(url||'').trim();
    if(/^https?:\/\//i.test(v))return v;
    if(/^assets\/images\/productos\/[a-zA-Z0-9_./-]+$/.test(v)&&!v.includes('..'))return base+v;
    return '';
  }
  const cantidadProducto=id=>[...carrito.values()].filter(i=>i.id===id).reduce((n,i)=>n+i.cantidad,0);
  const disponiblesProducto=id=>[...carrito.values()].find(i=>i.id===id)?.disponibles??0;
  const totalCarrito=()=>{
    let armazones=0,lentes=0,unidades=0;
    for(const i of carrito.values()){armazones+=i.precio*i.cantidad;lentes+=(i.optica?.precio||0)*i.cantidad;unidades+=i.cantidad;}
    return {armazones,lentes,unidades,subtotal:armazones+lentes};
  };
  function productoCard(p){
    const card=elemento('article',null,'ivc-pv-producto'),foto=elemento('div',null,'ivc-pv-foto');
    const src=rutaImagen(p.imagen);
    if(src){const im=document.createElement('img');im.src=src;im.alt=p.nombre;im.loading='lazy';im.referrerPolicy='no-referrer';im.addEventListener('error',()=>im.replaceWith(elemento('i',null,'bx bx-glasses')),{once:true});foto.append(im);}
    else foto.append(elemento('i',null,'bx bx-glasses'));
    foto.append(elemento('span',p.cantidad>0?'Disp. '+p.cantidad:'Sin existencias','ivc-pv-stock'+(p.cantidad>0?'':' agotado')));
    const datos=elemento('div',null,'ivc-pv-datos');
    datos.append(elemento('span',p.categoria||'Sin categoría','ivc-pv-categoria'),elemento('h3',p.nombre,'ivc-pv-nombre'),elemento('span','ID producto: '+p.id,'ivc-pv-codigo'),elemento('strong',moneda(centavos(p.precio)),'ivc-pv-precio'));
    const btn=elemento('button',p.cantidad>0?'＋ Agregar al carrito':'Agotado','ivc-pv-agregar');btn.type='button';btn.disabled=p.cantidad<1;btn.addEventListener('click',()=>agregar(p));
    datos.append(btn);card.append(foto,datos);return card;
  }
  async function categoriasCargar(){
    if(!turno)return;const lista=await api('categoriasventa');
    for(const cat of lista){const btn=elemento('button',cat.categoria,'ivc-pv-cat');btn.type='button';btn.dataset.categoria=String(cat.id);$('pvCategorias').append(btn);}
    $('pvCategorias').addEventListener('click',e=>{const btn=e.target.closest('button[data-categoria]');if(!btn)return;categoria=Number(btn.dataset.categoria);for(const b of $('pvCategorias').querySelectorAll('button'))b.classList.toggle('activo',b===btn);cargarCatalogo();});
  }
  async function cargarCatalogo(nuevo=true){
    if(!turno)return;if(nuevo){pagina=1;$('pvProductos').replaceChildren();}
    const token=++buscando;
    $('pvMensaje').textContent='Consultando productos…';$('pvCargarMas').hidden=true;
    try{const d=await api('buscarproductos',{q:$('pvBuscar').value.trim(),categoria:String(categoria),pagina:String(pagina),disponibles:$('pvDisponibles').checked?'1':'0'});
      if(token!==buscando)return;hayMas=d.hay_mas;
      for(const p of d.productos)$('pvProductos').append(productoCard(p));
      $('pvMensaje').textContent=$('pvProductos').childElementCount?'Selecciona los armazones. Puedes configurar micas en cada renglón del carrito.':'No se encontraron productos.';
      $('pvCargarMas').hidden=!hayMas;
    }catch(e){if(token!==buscando)return;$('pvMensaje').textContent='No fue posible cargar el catálogo.';await avisar(e.message,'error');}
  }
  function agregar(p){
    const id=Number(p.id),disp=Number(p.cantidad),usadas=cantidadProducto(id);
    if(usadas>=disp||usadas>=99){avisar('No puedes agregar más unidades de las disponibles.');return;}
    let sinMicas=[...carrito.values()].find(i=>i.id===id&&!i.optica);
    if(sinMicas){sinMicas.cantidad++;sinMicas.disponibles=disp;sinMicas.precio=centavos(p.precio);}
    else if(carrito.size<40){const clave=++secuencia;carrito.set(clave,{clave,id,nombre:String(p.nombre),precio:centavos(p.precio),cantidad:1,disponibles:disp,optica:null});}
    else {avisar('El carrito admite como máximo 40 renglones.');return;}
    pintarCarrito();
  }
  function variar(clave,delta){
    const i=carrito.get(clave);if(!i)return;
    const nuevo=i.cantidad+delta;
    if(nuevo<1)carrito.delete(clave);
    else if(i.optica&&delta>0){avisar('Cada configuración de micas corresponde a un armazón. Añade otra línea para configurar otro par.');return;}
    else if(nuevo<=99 && cantidadProducto(i.id)+delta<=i.disponibles)i.cantidad=nuevo;
    else {avisar('No hay suficientes existencias del armazón.');return;}
    pintarCarrito();
  }
  function duplicarLinea(clave){
    const i=carrito.get(clave);if(!i)return;
    if(carrito.size>=40||cantidadProducto(i.id)>=i.disponibles||cantidadProducto(i.id)>=99){avisar('No es posible crear otra línea para este producto.');return;}
    const nueva=++secuencia;carrito.set(nueva,{...i,clave:nueva,cantidad:1,optica:null});pintarCarrito();
  }
  function pintarCarrito(){
    cambiarValidacion();const cont=$('pvCarrito');cont.replaceChildren();const total=totalCarrito();
    for(const i of carrito.values()){
      const linea=elemento('div',null,'ivc-pv-linea'),info=elemento('div',null,'ivc-pv-linea-info');
      info.append(elemento('div',i.nombre,'ivc-pv-linea-nombre'),elemento('small','ID '+i.id+' · '+moneda(i.precio)+' c/u'));
      const controles=elemento('div',null,'ivc-pv-linea-controles');
      const menos=elemento('button','−');menos.type='button';menos.title='Quitar una unidad';menos.addEventListener('click',()=>variar(i.clave,-1));
      const unidades=elemento('span',i.cantidad);
      const mas=elemento('button','+');mas.type='button';mas.title='Agregar una unidad';mas.disabled=!!i.optica||cantidadProducto(i.id)>=i.disponibles||i.cantidad>=99;mas.addEventListener('click',()=>variar(i.clave,1));
      const borrar=elemento('button','×','ivc-pv-quitar');borrar.type='button';borrar.title='Eliminar renglón';borrar.addEventListener('click',()=>{carrito.delete(i.clave);pintarCarrito();});
      controles.append(menos,unidades,mas,borrar);info.append(controles);
      if(i.optica)info.append(elemento('div','Micas: '+i.optica.nombre+' · '+moneda(i.optica.precio)+' / par','ivc-pv-optica-tag'));
      if(i.optica)info.append(elemento('small',i.optica.modo==='RECETA'?'Receta #'+i.optica.id_receta:'Receta pendiente'));
      const acciones=elemento('div',null,'ivc-pv-optica-actions');
      const configurar=elemento('button',i.optica?'Editar micas':'＋ Configurar micas','ivc-pv-optica-btn');configurar.type='button';configurar.addEventListener('click',()=>abrirOptica(i.clave));acciones.append(configurar);
      const otra=elemento('button','Otra configuración','ivc-pv-optica-btn secundario');otra.type='button';otra.addEventListener('click',()=>duplicarLinea(i.clave));acciones.append(otra);
      info.append(acciones);linea.append(info,elemento('strong',moneda((i.precio+(i.optica?.precio||0))*i.cantidad),'ivc-pv-linea-subtotal'));cont.append(linea);
    }
    if(!carrito.size){const vacio=elemento('div',null,'ivc-pv-empty');vacio.append(elemento('i',null,'bx bx-shopping-bag'),elemento('strong','Tu carrito está vacío'),elemento('span','Agrega productos del catálogo para comenzar.'));cont.append(vacio);}
    $('pvCantidad').textContent=String(total.unidades);$('pvLimpiar').disabled=!carrito.size;$('pvPrevisualizar').disabled=!carrito.size;
    const porcentaje=descuentoPermitido?Math.min(100,Math.max(0,Number($('pvDescuento').value||0))):0;
    const rebaja=Math.floor((total.subtotal*porcentaje+50)/100);
    $('pvArmazones').textContent=moneda(total.armazones);$('pvMicas').textContent=moneda(total.lentes);
    $('pvSubtotal').textContent=moneda(total.subtotal);$('pvDescuentoImporte').textContent='−'+moneda(rebaja);$('pvTotal').textContent=moneda(total.subtotal-rebaja);
  }
  function resetOpticaCliente(){
    let quitadas=0;for(const i of carrito.values())if(i.optica){i.optica=null;quitadas++;}
    recetas=[];pintarCarrito();return quitadas;
  }
  function seleccionarCliente(c){
    // Invalidar búsquedas asíncronas anteriores para evitar resultados de otro cliente.
    clienteToken++;
    const anterior=cliente?.id||0,nombre=[c.nombre,c.apellidoP,c.apellidoM].filter(Boolean).join(' ');
    cliente={id:Number(c.id),nombre};
    $('pvClienteSeleccionado').firstChild.textContent=nombre+' · #'+c.id;
    $('pvClienteSeleccionado').dataset.clienteId=String(c.id);
    window.dispatchEvent(new CustomEvent('ivc:pos-cliente',{detail:{id:c.id,nombre,correo:c.correo||''}}));
    $('pvSinCliente').hidden=false;$('pvClienteBusqueda').value='';$('pvClientes').hidden=true;
    if(anterior!==cliente.id&&resetOpticaCliente()>0)avisar('Al cambiar de cliente se retiraron las configuraciones ópticas anteriores.');
    else cambiarValidacion();
  }
  function dialogoCliente(){return $('pvModalCliente');}
  let guardandoCliente=false,coincidenciasToken=0,solicitudCliente=null;
  function nuevaSolicitud(){
    const bytes=new Uint8Array(16);window.crypto.getRandomValues(bytes);
    return [...bytes].map(b=>b.toString(16).padStart(2,'0')).join('');
  }
  function abrirNuevoCliente(){
    if(!turno)return;
    const d=dialogoCliente(),form=$('pvFormularioNuevoCliente');form.reset();
    $('pvcCoincidencias').replaceChildren();coincidenciasToken++;
    solicitudCliente=nuevaSolicitud();d.showModal();$('pvcNombre').focus();
  }
  async function buscarCoincidencias(){
    const token=++coincidenciasToken,nombre=$('pvcNombre').value.trim(),apellido=$('pvcApellidoP').value.trim(),telefono=$('pvcTelefono').value.trim(),correo=$('pvcCorreo').value.trim();
    const q=correo.length>=3?correo:(telefono.replace(/[^0-9]/g,'').length>=7?telefono.replace(/[^0-9]/g,''):(nombre.length>=2&&apellido.length>=2?nombre:':'));
    const destino=$('pvcCoincidencias');destino.replaceChildren();if(q===':')return;
    try{const lista=await api('buscarclientes',{q});if(token!==coincidenciasToken||!dialogoCliente().open)return;
      if(!lista.length)return;
      destino.append(elemento('strong','Posibles clientes existentes:'));
      for(const c of lista.slice(0,6)){
        const nombreCompleto=[c.nombre,c.apellidoP,c.apellidoM].filter(Boolean).join(' ');
        const b=elemento('button',nombreCompleto+' · '+(c.telefono||c.correo||'#'+c.id),'ivc-pvc-coincidencia');b.type='button';
        b.addEventListener('click',()=>{dialogoCliente().close();seleccionarCliente(c);});destino.append(b);
      }
      destino.append(elemento('small','Revisa las coincidencias antes de crear una ficha nueva.'));
    }catch(e){if(token!==coincidenciasToken)return;destino.textContent='No fue posible comprobar coincidencias. Verifica antes de guardar.';}
  }
  async function guardarNuevoCliente(e){
    e.preventDefault();if(guardandoCliente)return;
    const form=$('pvFormularioNuevoCliente');if(!form.reportValidity())return;
    const f=new FormData(form);f.set('solicitud',solicitudCliente||nuevaSolicitud());
    guardandoCliente=true;$('pvcGuardar').disabled=true;$('pvcGuardar').textContent='Registrando…';
    try{
      const c=await api('crearcliente',f);
      dialogoCliente().close();seleccionarCliente(c);
      await avisar('Cliente #'+c.id+' registrado correctamente y seleccionado para esta venta.','success');
    }catch(error){await avisar(error.message,'error');}
    finally{guardandoCliente=false;$('pvcGuardar').disabled=false;$('pvcGuardar').textContent='Registrar y seleccionar';}
  }
  async function clientesBuscar(q){
    const token=++clienteToken,cont=$('pvClientes');cont.replaceChildren();if(q.trim().length<2){cont.hidden=true;return;}
    try{const lista=await api('buscarclientes',{q:q.trim()});if(token!==clienteToken)return;
      for(const c of lista){const nombre=[c.nombre,c.apellidoP,c.apellidoM].filter(Boolean).join(' '),btn=elemento('button',null,'ivc-pv-cliente-opcion');btn.type='button';btn.setAttribute('role','option');btn.append(elemento('span',nombre),elemento('small',c.correo||c.telefono||'Cliente presencial'));
        btn.addEventListener('click',()=>seleccionarCliente(c));cont.append(btn);
      }
      if(!lista.length)cont.append(elemento('div','Sin clientes coincidentes.','ivc-pv-cliente-opcion'));
      cont.hidden=false;
    }catch(e){if(token!==clienteToken)return;cont.hidden=true;await avisar(e.message,'error');}
  }
  function seleccionarOpcion(select,valor){select.value=String(valor);if(select.selectedIndex<0)select.selectedIndex=0;}
  function llenarTipos(){
    const s=$('pvTipoMica');s.replaceChildren();const tipos=new Map();for(const m of micas)tipos.set(m.id_tipo_lente,m.tipo_lente);
    for(const [id,nombre] of tipos)s.append(new Option(nombre,String(id)));
    llenarMicas();
  }
  function llenarMicas(preferida=null){
    const s=$('pvMica');s.replaceChildren();for(const m of micas.filter(x=>String(x.id_tipo_lente)===$('pvTipoMica').value))s.append(new Option(m.nombre_comercial+' · '+moneda(centavos(m.precio_venta)),String(m.id_mica)));
    if(preferida!==null)seleccionarOpcion(s,preferida);mostrarDetalle();
  }
  function mostrarDetalle(){
    const m=micas.find(x=>String(x.id_mica)===$('pvMica').value);
    const detalle=$('pvMicaDetalle');detalle.replaceChildren();if(!m){detalle.textContent='Sin opciones disponibles.';return;}
    detalle.append(elemento('strong',moneda(centavos(m.precio_venta))+' por par · '+m.sku));
    if(m.materiales)detalle.append(elemento('span','Materiales: '+m.materiales));
    if(m.tratamientos)detalle.append(elemento('span','Tratamientos incluidos: '+m.tratamientos));
    if(m.requiere_revision)detalle.append(elemento('span','Requiere revisión óptica profesional.'));
  }
  function mostrarRecetas(){
    const s=$('pvModalReceta');s.replaceChildren();
    for(const r of recetas)s.append(new Option('Receta #'+r.id_receta+' · '+r.estado_revision+' · '+(r.fecha_receta||r.creado_en.slice(0,10)),String(r.id_receta)));
    $('pvCampoReceta').hidden=$('pvModalModo').value!=='RECETA';
  }
  async function abrirOptica(clave){
    const i=carrito.get(clave);if(!i)return;
    if(!cliente){await avisar('Selecciona un cliente registrado antes de configurar lentes graduados.');$('pvClienteBusqueda').focus();return;}
    if(i.cantidad!==1){await avisar('Para configurar micas, deja una unidad en este renglón. Cada armazón tendrá su propia mica y receta.');return;}
    opticaLinea=clave;const clienteConsultado=cliente.id;const dialog=$('pvModalOptica');$('pvModalProducto').textContent=i.nombre+' · ID '+i.id;
    try{
      const [cat,rx]=await Promise.all([micas.length?Promise.resolve(micas):api('catalogomicas'),api('recetascliente',{cliente:String(cliente.id)})]);
      if(!cliente || cliente.id!==clienteConsultado)return;
      micas=cat;recetas=rx;
      if(!micas.length){await avisar('No hay micas activas disponibles por par en MXN. Revisa el catálogo.');return;}
      llenarTipos();if(i.optica){const m=micas.find(x=>x.id_mica===i.optica.id_mica);if(m){seleccionarOpcion($('pvTipoMica'),m.id_tipo_lente);llenarMicas(m.id_mica);}}
      $('pvModalModo').value=i.optica?.modo==='RECETA'?'RECETA':'PENDIENTE';mostrarRecetas();
      if(i.optica?.id_receta)seleccionarOpcion($('pvModalReceta'),i.optica.id_receta);
      $('pvModalQuitar').hidden=!i.optica;
      if(typeof dialog.showModal==='function')dialog.showModal();else await avisar('Tu navegador no admite el diálogo de configuración óptica.');
    }catch(e){await avisar(e.message,'error');}
  }
  async function guardarOptica(){
    const i=carrito.get(opticaLinea);if(!i)return;
    if(!cliente){await avisar('Debes seleccionar un cliente.');return;}
    const mica=micas.find(m=>String(m.id_mica)===$('pvMica').value);
    if(!mica){await avisar('Selecciona una mica disponible.');return;}
    const modo=$('pvModalModo').value,recetaId=modo==='RECETA'?Number($('pvModalReceta').value):null;
    if(modo==='RECETA'&&!recetas.some(r=>r.id_receta===recetaId)){await avisar('No hay una receta válida seleccionada para este cliente.');return;}
    i.optica={id_mica:mica.id_mica,nombre:mica.nombre_comercial,precio:centavos(mica.precio_venta),modo,id_receta:recetaId};
    $('pvModalOptica').close();pintarCarrito();
  }
  async function validarVenta(){
    if(!carrito.size)return;
    const descuento=descuentoPermitido?$('pvDescuento').value:'0';
    if(!/^\d+$/.test(descuento)||Number(descuento)>100){await avisar('El descuento debe ser un entero de 0 a 100.');return;}
    const items=[...carrito.values()].map(i=>({id:i.id,cantidad:i.cantidad,optica:i.optica?{id_mica:i.optica.id_mica,modo:i.optica.modo,id_receta:i.optica.id_receta}:null}));
    const f=new FormData();f.set('items',JSON.stringify(items));f.set('cliente',String(cliente?.id||0));f.set('descuento',descuento);
    const token=++validacionToken,btn=$('pvPrevisualizar');btn.disabled=true;$('pvValidacion').textContent='Validando existencias, micas, recetas y precios vigentes en MySQL…';
    try{const d=await api('previsualizar',f);if(token!==validacionToken)return;ultimaValidacion=d;
      $('pvCobrar').disabled=!puedeCobrar||centavos(d.total)<=0;
      $('pvArmazones').textContent=moneda(centavos(d.subtotal_armazones));$('pvMicas').textContent=moneda(centavos(d.subtotal_micas));
      $('pvSubtotal').textContent=moneda(centavos(d.subtotal));$('pvDescuentoImporte').textContent='−'+moneda(centavos(d.descuento_importe));$('pvTotal').textContent=moneda(centavos(d.total));
      $('pvValidacion').className='ivc-pv-validacion ok';$('pvValidacion').textContent='✓ '+d.unidades+' unidad(es) validadas. Armazones: '+moneda(centavos(d.subtotal_armazones))+', micas: '+moneda(centavos(d.subtotal_micas))+'. Total: '+moneda(centavos(d.total))+'. No se creó pedido, no hubo cobro ni reserva de existencias.';
    }catch(e){if(token!==validacionToken)return;ultimaValidacion=null;$('pvCobrar').disabled=true;$('pvValidacion').className='ivc-pv-validacion error';$('pvValidacion').textContent='No validado: '+e.message;await avisar(e.message,'error');}
    finally{btn.disabled=!carrito.size;}
  }
  const modalCobro=()=>$('pvModalCobro');
  function nuevaClaveCobro(){const b=new Uint8Array(16);crypto.getRandomValues(b);return [...b].map(x=>x.toString(16).padStart(2,'0')).join('');}
  function valoresCobro(){
    const total=centavos(ultimaValidacion?.total),forma=$('pvCobroForma').value;
    let tc=forma==='TARJETA'?total:forma==='MIXTO'?centavos($('pvCobroTarjeta').value):0;
    const parte=total-tc,rec=forma==='TARJETA'?0:centavos($('pvCobroRecibido').value);
    return {total,forma,tc,parte,rec,cambio:rec-parte};
  }
  function pintarCobro(){
    if(!ultimaValidacion)return;
    const c=valoresCobro();
    $('pvCobroTarjetaGrupo').hidden=c.forma==='EFECTIVO';
    $('pvCobroEfectivoGrupo').hidden=c.forma==='TARJETA';
    if(c.forma==='TARJETA')$('pvCobroTarjeta').value=(c.total/100).toFixed(2);
    $('pvCobroParteTarjeta').textContent=moneda(c.tc);
    $('pvCobroParteEfectivo').textContent=moneda(Math.max(0,c.parte));
    $('pvCobroCambio').textContent=moneda(Math.max(0,c.cambio));
    $('pvCobroEstado').textContent='';
  }
  async function abrirCobro(){
    if(!puedeCobrar||!ultimaValidacion||centavos(ultimaValidacion.total)<1){await avisar('Valida el carrito y su total antes de cobrar.');return;}
    if(!solicitudCobro)solicitudCobro=nuevaClaveCobro();
    $('pvCobroTotal').textContent=moneda(centavos(ultimaValidacion.total));
    $('pvCobroForma').value='EFECTIVO';$('pvCobroTarjeta').value='0.00';
    $('pvCobroRecibido').value=ultimaValidacion.total;
    $('pvCobroProveedor').value='';$('pvCobroReferencia').value='';$('pvCobroConfirmacion').checked=false;
    pintarCobro();modalCobro().showModal();
  }
  async function confirmarCobro(event){
    event.preventDefault();if(guardandoCobro||!ultimaValidacion)return;
    const c=valoresCobro();
    if(c.parte<0||(c.forma==='MIXTO'&&(c.tc<1||c.parte<1))||c.rec<c.parte){await avisar('Revisa los importes de efectivo y tarjeta; deben cubrir el total.');return;}
    if(c.tc>0&&(!$('pvCobroConfirmacion').checked||!$('pvCobroReferencia').value.trim()||!$('pvCobroProveedor').value.trim())){await avisar('Debes confirmar aprobación y capturar proveedor y referencia de la terminal.');return;}
    if(!solicitudCobro)solicitudCobro=nuevaClaveCobro();
    const f=new FormData();
    const items=[...carrito.values()].map(i=>({id:i.id,cantidad:i.cantidad,optica:i.optica?{id_mica:i.optica.id_mica,modo:i.optica.modo,id_receta:i.optica.id_receta}:null}));
    f.set('items',JSON.stringify(items));f.set('cliente',String(cliente?.id||0));f.set('descuento',descuentoPermitido?$('pvDescuento').value:'0');
    f.set('total_esperado',ultimaValidacion.total);f.set('solicitud',solicitudCobro);
    f.set('forma',c.forma);f.set('tarjeta_importe',(c.tc/100).toFixed(2));
    f.set('efectivo_recibido',(c.rec/100).toFixed(2));
    f.set('proveedor',c.tc>0?$('pvCobroProveedor').value.trim():'');
    f.set('referencia_terminal',c.tc>0?$('pvCobroReferencia').value.trim():'');
    f.set('confirmacion_terminal',c.tc>0&&$('pvCobroConfirmacion').checked?'1':'0');
    guardandoCobro=true;$('pvCobroGuardar').disabled=true;$('pvCobroGuardar').textContent='Registrando…';
    $('pvCobroEstado').textContent='Registrando el pedido y descontando inventario mediante transacción MySQL…';
    try{
      const r=await api('registrarventa',f);
      modalCobro().close();
      solicitudCobro=null;carrito.clear();cliente=null;recetas=[];
      $('pvSinCliente').hidden=true;$('pvClienteSeleccionado').firstChild.textContent='Venta de mostrador ';
      delete $('pvClienteSeleccionado').dataset.clienteId;
      window.dispatchEvent(new CustomEvent('ivc:pos-cliente',{detail:null}));
      $('pvDescuento').value='0';pintarCarrito();
      await avisar('Venta #'+r.id_venta+' · Pedido #'+r.id_pedido+' · Total '+moneda(centavos(r.total))+
        (r.cambio?' · Cambio '+moneda(centavos(r.cambio)):'')+
        (r.ya_existia?' (ya registrada, sin duplicación)':'')+
        (r.seguimiento_optico?' · Lentes pendientes de revisión/receta.':''),'success');
      // Conservar acceso directo al ticket de la venta recién cobrada.
      const zona=$('pvValidacion');
      if(zona){
        zona.textContent='Venta #'+Number(r.id_venta)+' registrada correctamente. ';
        zona.className='ivc-pv-validacion ok';
        const link=document.createElement('a');link.href=base+'pos/ticket?id='+encodeURIComponent(Number(r.id_venta));
        link.target='_blank';link.rel='noopener';link.textContent='Imprimir ticket de venta';zona.append(link);
      }
      cargarCatalogo(true);
    }catch(e){
      $('pvCobroEstado').textContent='ATENCIÓN: '+e.message+' Si la terminal ya aprobó el cargo, NO repitas el cobro; conserva el folio para conciliación.';
      await avisar(e.message+' Si cobraste con terminal, NO repitas el cargo; verifica con administración.','error');
      // Mantener solicitud y valores para permitir REINTENTAR EL REGISTRO, no el cobro físico.
    }finally{guardandoCobro=false;$('pvCobroGuardar').disabled=false;$('pvCobroGuardar').textContent='Registrar venta pagada';}
  }
  $('pvCobrar').addEventListener('click',abrirCobro);
  $('pvCobroCerrar').addEventListener('click',()=>{if(!guardandoCobro)modalCobro().close();});
  $('pvCobroCancelar').addEventListener('click',()=>{if(!guardandoCobro)modalCobro().close();});
  $('pvCobroForma').addEventListener('change',()=>{if($('pvCobroForma').value==='TARJETA')$('pvCobroTarjeta').value=ultimaValidacion?.total||'0.00';pintarCobro();});
  $('pvCobroTarjeta').addEventListener('input',pintarCobro);
  $('pvCobroRecibido').addEventListener('input',pintarCobro);
  $('pvCobroForm').addEventListener('submit',confirmarCobro);
  modalCobro().addEventListener('cancel',e=>{if(guardandoCobro)e.preventDefault();});

  let temporizador;$('pvBuscar').addEventListener('input' ,()=>{clearTimeout(temporizador);temporizador=setTimeout(()=>cargarCatalogo(true),320);});
  $('pvDisponibles').addEventListener('change',()=>cargarCatalogo(true));
  $('pvCargarMas').addEventListener('click',()=>{if(hayMas){pagina++;cargarCatalogo(false);}});
  $('pvLimpiar').addEventListener('click',async()=>{const ok=window.Swal?.fire?(await Swal.fire({icon:'question',title:'¿Vaciar carrito?',showCancelButton:true,confirmButtonText:'Sí, vaciar',cancelButtonText:'Conservar'})).isConfirmed:window.confirm('¿Vaciar el carrito?');if(ok){carrito.clear();pintarCarrito();}});
  let clienteTimer;$('pvClienteBusqueda').addEventListener('input',()=>{clearTimeout(clienteTimer);clienteTimer=setTimeout(()=>clientesBuscar($('pvClienteBusqueda').value),320);});
  $('pvSinCliente').addEventListener('click',()=>{cliente=null;$('pvClienteSeleccionado').firstChild.textContent='Venta de mostrador ';delete $('pvClienteSeleccionado').dataset.clienteId;window.dispatchEvent(new CustomEvent('ivc:pos-cliente',{detail:null}));$('pvSinCliente').hidden=true;const quitadas=resetOpticaCliente();if(quitadas)avisar('Se retiraron las configuraciones ópticas porque requieren un cliente identificado.');});
  $('pvNuevoCliente').addEventListener('click',abrirNuevoCliente);
  $('pvCerrarNuevoCliente').addEventListener('click',()=>dialogoCliente().close());
  $('pvcCancelar').addEventListener('click',()=>dialogoCliente().close());
  $('pvFormularioNuevoCliente').addEventListener('submit',guardarNuevoCliente);
  let timerDuplicados;
  for(const id of ['pvcNombre','pvcApellidoP','pvcTelefono','pvcCorreo'])$(id).addEventListener('input',()=>{
    clearTimeout(timerDuplicados);timerDuplicados=setTimeout(buscarCoincidencias,450);
  });
  dialogoCliente().addEventListener('close',()=>{coincidenciasToken++;});
  $('pvDescuento').addEventListener('input',pintarCarrito);
  $('pvPrevisualizar').addEventListener('click',validarVenta);
  $('pvTipoMica').addEventListener('change',()=>llenarMicas());$('pvMica').addEventListener('change',mostrarDetalle);
  $('pvModalModo').addEventListener('change',mostrarRecetas);
  $('pvModalCerrar').addEventListener('click',()=>$('pvModalOptica').close());
  $('pvModalGuardar').addEventListener('click',guardarOptica);
  $('pvModalQuitar').addEventListener('click',()=>{const i=carrito.get(opticaLinea);if(i)i.optica=null;$('pvModalOptica').close();pintarCarrito();});
  $('pvFormularioOptica').addEventListener('submit',e=>e.preventDefault());
  document.addEventListener('click',e=>{if(!e.target.closest('#pvClientes')&&!e.target.closest('#pvClienteBusqueda'))$('pvClientes').hidden=true;});
  if(turno){Promise.allSettled([categoriasCargar(),cargarCatalogo(),api('catalogomicas').then(v=>{micas=v;})]).then(r=>{for(const x of r)if(x.status==='rejected')avisar(x.reason?.message||'No se pudo consultar un catálogo.','error');});}
  else $('pvMensaje').textContent='Abre una caja para consultar el catálogo y preparar una venta.';
})();
