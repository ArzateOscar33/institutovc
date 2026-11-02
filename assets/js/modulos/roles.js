(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  const tabla=$('ivcRolesFilas'), frm=$('ivcRolFormulario');
  if (!tabla||!frm) return;
  const modal=new bootstrap.Modal($('ivcModalRol'));
  const escapar=s=>String(s??'').replace(/[&<>"']/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
  let roles=[];
  const solicitud=async (endpoint,opciones={})=>{
    const r=await fetch(base_url+'roles/'+endpoint,{credentials:'same-origin',headers:{Accept:'application/json'},...opciones});
    const j=await r.json();
    if (!r.ok || !j.ok) throw new Error(j.msg || 'No se pudo completar la operación.');
    return j;
  };
  async function cargar(){
    try {
      const j=await solicitud('listar');roles=j.datos;
      $('ivcRolesCantidad').textContent=roles.length+' roles';
      tabla.innerHTML=roles.map(r=>{
        const protegido=r.codigo==='ROOT', sistema=['ROOT','ADMINISTRADOR','VENDEDOR'].includes(r.codigo);
        const editar=protegido?'':`<button class="btn btn-outline-primary btn-sm" data-editar="${Number(r.id_rol)}">Permisos</button>`;
        const toggle=sistema?'':`<button class="btn btn-outline-${Number(r.activo)?'danger':'success'} btn-sm" data-estado="${Number(r.id_rol)}" data-activo="${Number(r.activo)?'0':'1'}">${Number(r.activo)?'Desactivar':'Reactivar'}</button>`;
        return `<tr><td><strong>${escapar(r.nombre)}</strong><div class="small text-muted">${escapar(r.descripcion||'')}</div></td><td><code>${escapar(r.codigo)}</code></td><td>${Number(r.usuarios)}</td><td>${protegido?'Acceso total':Number(r.permisos)}</td><td><span class="badge ${Number(r.activo)?'bg-success':'bg-secondary'}">${Number(r.activo)?'Activo':'Inactivo'}</span></td><td class="text-end"><div class="d-inline-flex gap-2">${editar}${toggle||'<span class="small text-muted">Protegido</span>'}</div></td></tr>`;
      }).join('');
    }catch(e){tabla.innerHTML='<tr><td colspan="6" class="text-danger text-center py-4">'+escapar(e.message)+'</td></tr>';}
  }
  function nuevo(){
    frm.reset();$('ivcRolId').value='0';$('ivcRolCodigo').disabled=false;$('ivcRolNombre').readOnly=false;
    $('ivcRolTitulo').textContent='Crear rol';modal.show();
  }
  async function editar(id){
    try{
      const j=await solicitud('obtener/'+id),r=j.rol;
      if(r.codigo==='ROOT')return;
      nuevo();$('ivcRolId').value=String(r.id_rol);$('ivcRolNombre').value=r.nombre;
      $('ivcRolCodigo').value=r.codigo;$('ivcRolCodigo').disabled=true;
      $('ivcRolDescripcion').value=r.descripcion||'';
      if(['ADMINISTRADOR','VENDEDOR'].includes(r.codigo)) $('ivcRolNombre').readOnly=true;
      frm.querySelectorAll('[name="permisos[]"]').forEach(c=>c.checked=r.permisos.includes(c.value));
      $('ivcRolTitulo').textContent='Editar permisos: '+r.nombre;
    }catch(e){Swal.fire('Roles',e.message,'error');}
  }
  async function cambioEstado(id,activo){
    const r=roles.find(x=>Number(x.id_rol)===id);
    const pregunta=await Swal.fire({title:activo?'¿Reactivar rol?':'¿Desactivar rol?',text:activo?'Volverá a estar disponible para nuevas asignaciones.':'Los usuarios deben estar reasignados antes de desactivar este rol.',icon:'warning',showCancelButton:true,confirmButtonText:'Confirmar',cancelButtonText:'Cancelar'});
    if(!pregunta.isConfirmed)return;
    const fd=new FormData();fd.set('csrf',$('ivcRolCsrf').value);fd.set('id_rol',id);fd.set('activo',activo);
    try{const j=await solicitud('estado',{method:'POST',body:fd});await cargar();Swal.fire('Roles',j.msg,'success');}
    catch(e){Swal.fire('Roles',e.message,'error');}
  }
  tabla.addEventListener('click',e=>{
    const edit=e.target.closest('[data-editar]');if(edit){editar(Number(edit.dataset.editar));return;}
    const estado=e.target.closest('[data-estado]');if(estado)cambioEstado(Number(estado.dataset.estado),Number(estado.dataset.activo));
  });
  $('ivcNuevoRol').addEventListener('click',nuevo);
  $('ivcRolTodo').addEventListener('click',()=>frm.querySelectorAll('[name="permisos[]"]').forEach(c=>c.checked=true));
  $('ivcRolNinguno').addEventListener('click',()=>frm.querySelectorAll('[name="permisos[]"]').forEach(c=>c.checked=false));
  frm.addEventListener('submit',async e=>{
    e.preventDefault();const btn=$('ivcRolGuardar');btn.disabled=true;
    try{
      const j=await solicitud('guardar',{method:'POST',body:new FormData(frm)});
      modal.hide();await cargar();Swal.fire('Roles y permisos',j.msg,'success');
    }catch(err){Swal.fire('No se guardó',err.message,'error');}
    finally{btn.disabled=false;}
  });
  cargar();
})();
