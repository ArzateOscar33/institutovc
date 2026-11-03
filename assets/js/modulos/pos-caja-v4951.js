/* IVC 4.9.5.1: caja presencial. NUNCA simula una venta ni acredita una tarjeta. */
(() => {
  'use strict';
  const root=document.getElementById('ivcPos');if(!root)return;
  const $=id=>document.getElementById(id);
  const permiso=id=>root.dataset[id]==='1';
  const moneda=n=>new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(Number(n||0));
  const fecha=s=>s?String(s):'—';
  const texto=(tag,content,cls)=>{const e=document.createElement(tag);e.textContent=String(content??'');if(cls)e.className=cls;return e;};
  async function solicitar(metodo,form){
    const opciones={method:form?'POST':'GET',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'};
    if(form){const body=new FormData(form);body.set('csrf',root.dataset.csrf);opciones.body=body;}
    let resp;
    try{resp=await fetch(root.dataset.url+'pos/'+metodo,opciones);}
    catch{throw Error('No se pudo conectar con el servidor.');}
    let j;try{j=await resp.json();}catch{throw Error('Respuesta no válida del servidor.');}
    if(!resp.ok||!j.ok)throw Error(j.msg||'No se pudo completar la operación.');
    return j.datos;
  }
  async function aviso(mensaje,tipo='success'){
    if(window.Swal?.fire) return Swal.fire({icon:tipo,title:tipo==='success'?'Operación realizada':'Aviso',text:mensaje,confirmButtonColor:'#1576b6'});
    alert(mensaje);
  }
  function habilitarFormulario(id,mostrar){const f=$(id);f.hidden=!mostrar;f.querySelectorAll('input,button,select').forEach(i=>i.disabled=!mostrar);}
  async function refrescar(){
    const datos=await solicitar('resumen');const turno=datos.turno;const abierto=Boolean(turno);
    $('posEstado').textContent=abierto?'Turno abierto':'Sin turno abierto';
    $('posTurnoEstado').textContent=abierto?'Abierto':'Cerrado';
    $('posCajaTitulo').textContent=abierto?turno.caja_nombre:'Seleccionar caja';
    habilitarFormulario('posAbrirForm',!abierto&&permiso('abrir'));
    $('posAbrirSeccion').hidden=abierto||!permiso('abrir');
    $('posTurnoSeccion').hidden=!abierto;
    habilitarFormulario('posMovimientoForm',abierto&&permiso('movimientos'));
    $('posCerrarAyuda').hidden=abierto&&permiso('cerrar');
    habilitarFormulario('posCerrarForm',abierto&&permiso('cerrar'));
    if(abierto){
      const a=datos.arqueo;
      $('posFondoActual').textContent=moneda(a.fondo_inicial);
      $('posEntradas').textContent=moneda(a.entradas);
      $('posSalidas').textContent=moneda(a.salidas);
      $('posEsperado').textContent=moneda(a.esperado);
      $('posContado').value=a.esperado;
    }
    const sel=$('posCajaSelect'),anterior=sel.value;
    sel.replaceChildren();
    for(const caja of datos.cajas){
      const option=document.createElement('option');option.value=caja.id_caja;
      option.textContent=caja.nombre+(caja.turno_abierto?' · Ocupada por '+caja.responsable:'');
      option.disabled=Boolean(caja.turno_abierto);sel.append(option);
    }
    if([...sel.options].some(opt=>opt.value===anterior&&!opt.disabled))sel.value=anterior;
    else if([...sel.options].some(opt=>!opt.disabled))sel.value=[...sel.options].find(opt=>!opt.disabled).value;
    const disponibles=[...sel.options].some(opt=>!opt.disabled);
    if(!abierto&&permiso('abrir'))$('posAbrirBtn').disabled=!disponibles;
    const cont=$('posMovimientos');cont.replaceChildren();
    const movs=datos.movimientos||[];
    $('posCantidad').textContent=String(movs.length);
    if(!movs.length)cont.append(texto('p','Sin movimientos registrados en este turno.'));
    for(const m of movs){
      const r=texto('div','', 'ivc-pos-movimiento');
      const izquierdo=texto('div','');izquierdo.append(texto('strong',m.tipo==='RETIRO'?'Retiro':m.tipo==='INGRESO'?'Ingreso':m.tipo),texto('small',m.motivo),texto('span',fecha(m.creado_en)));
      r.append(izquierdo,texto('strong',(m.tipo==='RETIRO'||m.tipo==='DEVOLUCION'?'-':'+')+moneda(m.importe),m.tipo==='RETIRO'?'ivc-pos-negativo':'ivc-pos-positivo'));cont.append(r);
    }
  }
  async function cargarTurnos(){
    if(!permiso('supervisar'))return;
    $('posSupervision').hidden=false;
    const turnos=await solicitar('turnos');const tb=$('posTurnos');tb.replaceChildren();
    for(const t of turnos){const tr=document.createElement('tr');
      for(const col of [t.id_turno,t.caja,t.responsable,fecha(t.apertura_en),t.estado,t.efectivo_esperado===null?'—':moneda(t.efectivo_esperado),t.efectivo_contado===null?'—':moneda(t.efectivo_contado),t.diferencia===null?'—':moneda(t.diferencia)])tr.append(texto('td',col));
      tb.append(tr);
    }
    if(!turnos.length){const tr=document.createElement('tr');const td=texto('td','No hay turnos registrados.');td.colSpan=8;tr.append(td);tb.append(tr);}
  }
  async function enviar(id,endpoint,confirmacion){
    const form=$(id);if(!form.reportValidity())return;
    if(confirmacion){const ok=window.Swal?.fire?
      (await Swal.fire({icon:'question',title:'Confirmar cierre',text:'El arqueo quedará registrado y el turno no podrá reabrirse.',showCancelButton:true,confirmButtonText:'Cerrar turno',cancelButtonText:'Cancelar'})).isConfirmed:
      window.confirm('¿Cerrar este turno definitivamente?');if(!ok)return;}
    const boton=form.querySelector('button[type=submit]');boton.disabled=true;
    try{const respuesta=await solicitar(endpoint,form);form.reset();
      await aviso(respuesta.mensaje+(respuesta.diferencia!==undefined?' Diferencia: '+moneda(respuesta.diferencia):''));
      await refrescar();await cargarTurnos();
    }catch(e){await aviso(e.message,'error');}
    finally{boton.disabled=false;}
  }
  $('posAbrirForm').addEventListener('submit',e=>{e.preventDefault();enviar('posAbrirForm','abrir',false);});
  $('posCerrarForm').addEventListener('submit',e=>{e.preventDefault();enviar('posCerrarForm','cerrar',true);});
  $('posMovimientoForm').addEventListener('submit',e=>{e.preventDefault();enviar('posMovimientoForm','movimiento',false);});
  Promise.all([refrescar(),cargarTurnos()]).catch(e=>aviso(e.message,'error'));
})();
