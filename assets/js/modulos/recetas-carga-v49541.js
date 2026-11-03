(() => {
  'use strict';
  const root=document.getElementById('rxAdmin');
  const form=document.getElementById('rxCargaForm');
  if (!root || !form) return;
  const base=root.dataset.base;
  const el=id=>document.getElementById(id);
  const dialog=window.bootstrap?.Modal?.getOrCreateInstance(el('rxCargaModal'));
  let selected=null;
  let busy=false;
  const msg=(message,ok=false)=>{
    el('rxCargaMensaje').textContent=message;
    el('rxCargaMensaje').style.color=ok?'#116648':'#aa3434';
  };
  async function request(endpoint, init={}) {
    const response=await fetch(base+'recetasAdmin/'+endpoint,{credentials:'same-origin',headers:{'Accept':'application/json'},...init});
    let data;
    try {data=await response.json();}catch(e){throw new Error('Respuesta inesperada del servidor.');}
    if(!response.ok || !data.ok) throw new Error(data.msg||'No fue posible completar la operación.');
    return data;
  }
  function reset(){
    selected=null;form.reset();
    el('rxCargaClienteId').value='';el('rxCargaResultados').replaceChildren();
    el('rxCargaSeleccion').hidden=true;el('rxCargaDetalle').disabled=true;
    el('rxCargaDetalle').replaceChildren(new Option('Solo registrar en historial del cliente',''));
    msg('');
  }
  el('rxAbrirCarga').addEventListener('click',()=>{reset();dialog?.show();});
  async function buscar(){
    const q=el('rxCargaBuscar').value.trim();
    if(q.length<2){msg('Introduce al menos dos caracteres.');return;}
    try{
      const data=await request('buscarClientes?q='+encodeURIComponent(q));
      const parent=el('rxCargaResultados');parent.replaceChildren();
      if(!data.clientes.length){msg('No se encontraron clientes. Regístralo primero en el POS.');return;}
      msg('');
      for(const cliente of data.clientes){
        const btn=document.createElement('button');btn.type='button';btn.className='rx-carga-cliente';
        const label=document.createElement('strong');label.textContent=`#${cliente.id} · ${cliente.nombre_completo}`;
        const sub=document.createElement('small');sub.textContent=[cliente.telefono,cliente.correo,cliente.origen].filter(Boolean).join(' · ')||'Cliente registrado';
        btn.append(label,sub);
        btn.addEventListener('click',()=>selectClient(cliente));parent.append(btn);
      }
    }catch(error){msg(error.message);}
  }
  async function selectClient(cliente){
    selected=cliente;
    el('rxCargaClienteId').value=cliente.id;
    const badge=el('rxCargaSeleccion');badge.textContent=`Cliente seleccionado: #${cliente.id} · ${cliente.nombre_completo}`;
    badge.hidden=false;el('rxCargaResultados').replaceChildren();
    const select=el('rxCargaDetalle');select.disabled=true;
    select.replaceChildren(new Option('Solo registrar en historial del cliente',''));
    try{
      const data=await request('pendientesCliente/'+encodeURIComponent(cliente.id));
      // Descartar resultados atrasados si cambió la selección.
      if(!selected || String(selected.id)!==String(cliente.id))return;
      for(const fila of data.detalles){
        const texto=`Pedido #${fila.id_pedido} · ${fila.producto} · ${fila.nombre_mica_snapshot} (detalle #${fila.id_detalle})`;
        select.add(new Option(texto,fila.id_detalle));
      }
      select.disabled=false;msg(data.detalles.length?`Hay ${data.detalles.length} artículos con receta pendiente. Puedes asociar uno.`:'Puedes guardar la receta en el historial del cliente.');
    }catch(error){msg(error.message);}
  }
  el('rxCargaBuscarBtn').addEventListener('click',buscar);
  el('rxCargaBuscar').addEventListener('keydown',ev=>{if(ev.key==='Enter'){ev.preventDefault();buscar();}});
  el('rxCargaBuscar').addEventListener('input',()=>{
    if(selected){selected=null;el('rxCargaClienteId').value='';el('rxCargaSeleccion').hidden=true;el('rxCargaDetalle').disabled=true;}
  });
  form.addEventListener('submit',async ev=>{
    ev.preventDefault();
    if(busy)return;
    if(!selected){msg('Selecciona un cliente de los resultados.');return;}
    const file=el('rxCargaArchivo').files?.[0];
    if(!file || file.size<1 || file.size>10*1024*1024){msg('Selecciona un archivo JPG, PNG o PDF de máximo 10 MB.');return;}
    const fd=new FormData(form);
    busy=true;el('rxCargaGuardar').disabled=true;msg('Guardando receta...');
    try {
      const result=await request('cargar',{method:'POST',body:fd});
      msg(`Receta #${result.id_receta} guardada. Pendiente de revisión.`,true);
      el('rxReload')?.click();
      if(window.Swal?.fire){await Swal.fire({icon:'success',title:'Receta registrada',text:`Receta #${result.id_receta}. Pendiente de revisión óptica.`,confirmButtonText:'Entendido'});}
      dialog?.hide();reset();
    }catch(error){msg(error.message);}
    finally{busy=false;el('rxCargaGuardar').disabled=false;}
  });
})();
