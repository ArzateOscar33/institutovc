(()=>{
 'use strict';
 const $=id=>document.getElementById(id), boton=$('pvaAbrir');if(!boton)return;
 const main=$('ivcPosVenta'), modal=$('pvaModal');let seleccionado=null;
 window.addEventListener('ivc:pos-cliente',e=>{seleccionado=e.detail;});
 const cerrar=()=>{modal.close();$('pvaSalida').replaceChildren();$('pvaForm').reset();};
 boton.addEventListener('click',()=>{
   if(!seleccionado?.id)return window.Swal?.fire?.({icon:'info',title:'Selecciona un cliente',text:'Selecciona un cliente presencial antes de generar su código.'})||alert('Selecciona un cliente.');
   $('pvaForm').reset();$('pvaSalida').replaceChildren();$('pvaCorreo').value=seleccionado.correo||'';modal.showModal();
 });
 $('pvaCerrar').addEventListener('click',cerrar);$('pvaCancelar').addEventListener('click',cerrar);
 $('pvaForm').addEventListener('submit',async e=>{
   e.preventDefault();if(!seleccionado?.id)return;
   if(!$('pvaIdentidad').checked)return alert('Confirma la identidad presencial.');
   const btn=$('pvaEmitir');btn.disabled=true;
   const fd=new FormData();fd.set('csrf',main.dataset.csrf);fd.set('id_cliente',String(seleccionado.id));
   fd.set('correo',$('pvaCorreo').value.trim());fd.set('identidad_confirmada','1');
   try{
     const r=await fetch(main.dataset.base+'pos/emitiractivacion',{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'}});
     const res=await r.json();if(!r.ok||!res.ok)throw Error(res.msg||'No se pudo emitir el código.');
     const wrap=$('pvaSalida');wrap.replaceChildren();
     const info=document.createElement('p');info.textContent='Entregar este código una sola vez al cliente. Caduca en 7 días:';
     const codigo=document.createElement('div');codigo.className='ivc-activacion-codigo';codigo.textContent=res.datos.codigo;
     const instruccion=document.createElement('p');instruccion.textContent='El cliente debe abrir '+res.datos.url+' e introducir su correo y este código. Recibirá un enlace para establecer su contraseña.';
     wrap.append(info,codigo,instruccion);
   }catch(err){if(window.Swal)Swal.fire({icon:'error',title:'No se pudo emitir',text:err.message});else alert(err.message);}
   finally{btn.disabled=false;}
 });
})();
