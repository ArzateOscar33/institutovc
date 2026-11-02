(() => {
  'use strict';
  const root = document.getElementById('rxAdmin'); if (!root) return;
  const base = root.dataset.base; const el=id=>document.getElementById(id);
  let items = [], current = null;
  const modal = window.bootstrap?.Modal?.getOrCreateInstance(el('rxAdminModal'));
  const setMessage=(msg,good=false)=>{el('rxAdminAlert').textContent=msg;el('rxAdminAlert').style.color=good?'#136a4e':'#b23636';};
  async function api(path, init={}) {
    const response=await fetch(base+'recetasAdmin/'+path,{credentials:'same-origin',headers:{'Accept':'application/json'},...init});
    const data=await response.json();if(!response.ok||!data.ok) throw new Error(data.msg||'No pudimos completar la consulta. Inténtalo nuevamente.');return data;
  }
  const cell=(row,value)=>{const td=row.insertCell();td.textContent=String(value??'—');return td;};
  const label=st=>({PENDIENTE:'Pendiente',APROBADA:'Aprobada',REQUIERE_CORRECCION:'Requiere corrección',RECHAZADA:'Rechazada'})[st]||st;
  function render() {
    const target=el('rxAdminRows');target.replaceChildren();
    const q=el('rxSearch').value.trim().toLowerCase(), st=el('rxState').value;
    const showing=items.filter(x=>(!st||x.estado_revision===st)&&(!q||[x.id_receta,x.cliente,x.correo].join(' ').toLowerCase().includes(q)));
    for(const r of showing){
      const tr=target.insertRow();cell(tr,'#'+r.id_receta);cell(tr,`${r.cliente} · ${r.correo}`);cell(tr,r.modalidad);cell(tr,r.creado_en);cell(tr,label(r.estado_revision));
      const td=tr.insertCell();const btn=document.createElement('button');btn.type='button';btn.className='btn btn-sm rx-btn';btn.textContent='Revisar';btn.addEventListener('click',()=>openDetail(r.id_receta));td.append(btn);
    }
    if(!showing.length){const tr=target.insertRow();const td=tr.insertCell();td.colSpan=6;td.textContent='No se encontraron recetas.';}
    el('rxAdminCount').textContent=`${showing.length} de ${items.length} recetas`;
    el('rxTotal').textContent=items.length;el('rxPending').textContent=items.filter(x=>x.estado_revision==='PENDIENTE').length;el('rxApproved').textContent=items.filter(x=>x.estado_revision==='APROBADA').length;
  }
  async function load(){try{const d=await api('listar');items=d.datos;render();setMessage('');}catch(e){setMessage(e.message);}}
  function row(parent,labelText,value){const p=document.createElement('p');const b=document.createElement('b');b.textContent=labelText+': ';p.append(b,document.createTextNode(value===null||value===''?'—':String(value)));parent.append(p);}
  async function openDetail(id){try{
    const d=await api('detalle/'+encodeURIComponent(id));current=d.receta;
    el('rxModalTitle').textContent='Receta #'+id+' · '+label(current.estado_revision);
    const target=el('rxModalInfo');target.replaceChildren();row(target,'Cliente',current.cliente+' · '+current.correo);row(target,'Estado',label(current.estado_revision));row(target,'Fecha de receta',current.fecha_receta);row(target,'DP (mm)',current.distancia_pupilar_total_mm);row(target,'Observaciones del cliente',current.observaciones_cliente);
    for(const eye of current.ojos){const div=document.createElement('div');div.className='rx-eye';row(div,eye.ojo,'ESF '+(eye.esfera??'—')+' / CIL '+(eye.cilindro??'—')+' / EJE '+(eye.eje??'—')+' / ADD '+(eye.adicion??'—'));target.append(div);}
    const visor=document.createElement('div');visor.className='mt-3';target.append(visor);
    if(window.IVCRecetaPreview){window.IVCRecetaPreview.montar(visor,current.archivos||[],base);}
    else {row(visor,'Documento','No se pudo cargar el visor; actualiza el navegador.');}
    row(target,'Observación de revisión',current.observaciones_revision);
    el('rxReviewPane').hidden=current.estado_revision!=='PENDIENTE';el('rxReviewNotes').value='';modal?.show();
  }catch(e){setMessage(e.message);}}
  async function review(estado){if(!current)return;const nota=el('rxReviewNotes').value.trim();if(estado!=='APROBADA'&&!nota){setMessage('Escribe el motivo de la corrección o rechazo.');return;}
    const fd=new FormData();fd.append('csrf',el('rxCsrfAdmin').value);fd.append('id_receta',current.id_receta);fd.append('estado',estado);fd.append('observaciones',nota);
    try{const result=await api('revisar',{method:'POST',body:fd});modal?.hide();setMessage(result.msg,true);await load();}catch(e){setMessage(e.message);}}
  el('rxReload').addEventListener('click',load);el('rxSearch').addEventListener('input',render);el('rxState').addEventListener('change',render);
  root.querySelectorAll('[data-review]').forEach(b=>b.addEventListener('click',()=>review(b.dataset.review)));
  load();
})();
