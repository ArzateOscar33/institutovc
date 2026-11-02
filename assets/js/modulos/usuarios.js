const nuevo = document.getElementById('nuevo_registro');
const frm = document.getElementById('frmRegistro');
const titleModal = document.getElementById('titleModal');
const btnAccion = document.getElementById('btnAccion');
const myModal = new bootstrap.Modal(document.getElementById('nuevoModal'));
let tblUsuario;
const csrf = document.getElementById('ivcRolesCsrf').value;

document.addEventListener('DOMContentLoaded', () => {
    tblUsuario = $('#tblUsuarios').DataTable({
        ajax: {url: base_url + 'usuarios/listar', dataSrc: ''},
        columns: [{data:'id'},{data:'nombres'},{data:'apellidos'},{data:'correo'},{data:'perfil'},{data:'accion'}],
        language, dom, buttons
    });
    nuevo.addEventListener('click', () => {
        frm.reset(); document.getElementById('id').value='';
        titleModal.textContent='Nuevo usuario'; btnAccion.textContent='Registrar';
        document.getElementById('clave').disabled=false;
        document.getElementById('clave').required=true;
        document.getElementById('id_rol').disabled=false;
        myModal.show();
    });
    frm.addEventListener('submit', async (e) => {
        e.preventDefault(); btnAccion.disabled=true;
        try {
            const r=await fetch(base_url+'usuarios/registrar', {method:'POST',body:new FormData(frm),headers:{Accept:'application/json'},credentials:'same-origin'});
            const j=await r.json();
            if(r.ok && j.icono==='success') {myModal.hide();tblUsuario.ajax.reload(null,false);}
            Swal.fire('Usuarios',j.msg,j.icono||'error');
        } catch(e) {Swal.fire('Error','No se pudo guardar el usuario.','error');}
        finally {btnAccion.disabled=false;}
    });
});

async function editUser(id) {
    try {
        const r=await fetch(base_url+'usuarios/edit/'+encodeURIComponent(id),{headers:{Accept:'application/json'},credentials:'same-origin'});
        const u=await r.json();
        if(!r.ok || !u.id) throw Error(u.msg||'No autorizado');
        frm.reset();
        document.getElementById('id').value=u.id;
        document.getElementById('nombre').value=u.nombres;
        document.getElementById('apellido').value=u.apellidos;
        document.getElementById('correo').value=u.correo;
        document.getElementById('id_rol').value=String(u.id_rol);
        // No es necesario volver a solicitar la contraseña para editar.
        document.getElementById('clave').value='';
        document.getElementById('clave').required=false;
        document.getElementById('clave').disabled=true;
        titleModal.textContent='Editar usuario';btnAccion.textContent='Guardar cambios';
        myModal.show();
    } catch(e) {Swal.fire('Usuarios',e.message,'error');}
}

async function eliminarUser(id) {
    const confirmacion=await Swal.fire({title:'¿Desactivar usuario?',text:'Perderá acceso al panel administrativo.',icon:'warning',showCancelButton:true,confirmButtonText:'Sí, desactivar',cancelButtonText:'Cancelar'});
    if(!confirmacion.isConfirmed)return;
    try {
        const fd=new FormData();fd.set('csrf',csrf);
        const r=await fetch(base_url+'usuarios/delete/'+encodeURIComponent(id),{method:'POST',headers:{Accept:'application/json'},credentials:'same-origin',body:fd});
        const j=await r.json();
        if(r.ok && j.icono==='success')tblUsuario.ajax.reload(null,false);
        Swal.fire('Usuarios',j.msg,j.icono||'error');
    } catch(e) {Swal.fire('Error','No se pudo procesar la solicitud.','error');}
}
