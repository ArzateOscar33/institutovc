/** Registro de recetas desde la cuenta del cliente. */
(() => {
  'use strict';
  const form=document.getElementById('ivcRxSaveCard');if(!form)return;
  const el=id=>document.getElementById(id);const base=form.dataset.base;
  const status=el('ivcRxSaveStatus'), save=el('ivcRxSaveButton');
  let csrf=null, loading=false;
  const getMode=()=>el('ivcRxManualBtn').classList.contains('is-active')?'MANUAL':'ARCHIVO';
  function feedback(message,ok=false){status.textContent=message;status.style.color=ok?'#136a4e':'#b23737';}
  async function request(path,init={}){
    const response=await fetch(base+'recetas/'+path,{credentials:'same-origin',headers:{Accept:'application/json'},...init});
    const data=await response.json();if(!response.ok||!data.ok)throw new Error(data.msg||'No se pudo completar la solicitud.');return data;
  }
  async function ensureToken(){if(csrf)return csrf;csrf=(await request('token')).csrf;return csrf;}
  async function list(){try{
    const data=await request('misRecetas');const box=el('ivcRxSavedList');box.replaceChildren();
    if(!data.datos?.length){box.hidden=true;return;}
    box.hidden=false;const heading=document.createElement('strong');heading.textContent='Mis recetas registradas';box.append(heading);
    const ul=document.createElement('ul');for(const item of data.datos){const li=document.createElement('li');
      li.textContent='#'+item.id_receta+' · '+item.modalidad+' · '+item.estado_revision.replaceAll('_',' ');
      const small=document.createElement('small');small.textContent=item.creado_en+(item.observaciones_revision?' · '+item.observaciones_revision:'');li.append(small);ul.append(li);}
    box.append(ul);
  }catch(e){feedback('No fue posible consultar tus recetas: '+e.message);}}
  // La versión visual V10 mostraba una previsualización al arrastrar pero no
  // asignaba el archivo al input real. Corregimos esto antes de su listener.
  const dropzone=el('ivcRxDropzone');const fileInput=el('ivcRxFile');
  if(dropzone&&fileInput){dropzone.addEventListener('drop',ev=>{
    if(ev.dataTransfer?.files?.length){try{fileInput.files=ev.dataTransfer.files;}catch(_){}
    }
  },{capture:true});}
  save.addEventListener('click',async()=>{
    if(loading)return;
    if(!el('ivcRxConsent').checked){feedback('Confirma que deseas enviar tu información para revisión de la óptica.');return;}
    const mode=getMode();
    if(mode==='ARCHIVO'&&!fileInput.files?.length){feedback('Selecciona una fotografía o un PDF válido.');return;}
    const body=new FormData();body.append('modalidad',mode);body.append('fecha_receta',el('ivcRxDate').value);
    body.append('observaciones',el('ivcRxNotes').value);body.append('dp',el('ivcRxDp').value);
    if(mode==='MANUAL')for(const eye of ['od','oi'])for(const [short,long] of [['esf','esfera'],['cil','cilindro'],['eje','eje'],['add','adicion']])body.append(`${eye}_${long}`,el(`ivcRx-${eye}-${short}`).value);
    if(mode==='ARCHIVO')body.append('receta_archivo',fileInput.files[0]);
    loading=true;save.disabled=true;save.textContent='Guardando…';feedback('');
    try{body.append('csrf',await ensureToken());const result=await request('guardar',{method:'POST',body});
      window.ivcRecetaGuardada=result.id_receta;
      feedback('Tu receta se guardó correctamente y está pendiente de revisión. Puedes seleccionarla al configurar tus lentes.',true);
      await list();
    }catch(e){feedback(e.message);}finally{loading=false;save.disabled=false;save.textContent='Guardar receta para revisión';}
  });
  list();
})();
