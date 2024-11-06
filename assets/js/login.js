const btnRegister = document.querySelector('#btnRegister');
const btnLogin = document.querySelector('#btnLogin');
const formLogin = document.querySelector('#frmLogin');
const formRegister = document.querySelector('#frmRegister');
const registrarse = document.querySelector('#registrarse');
//inputs de registro
const apellidoP = document.querySelector('#apellidoPaternoRegistro');
const apellidoM = document.querySelector('#apellidoMaternoRegistro');
const nombreRegistro = document.querySelector('#nombreRegistro');
const correoRegistro = document.querySelector('#correoRegistro');
const claveRegistro = document.querySelector('#claveRegistro');




const loguearse = document.querySelector('#loguearse');
document.addEventListener("DOMContentLoaded",function(){
btnRegister.addEventListener('click',function(){
    formLogin.classList.add('d-none');
    formRegister.classList.remove('d-none');
})
btnLogin.addEventListener('click',function(){
    formLogin.classList.remove('d-none');
    formRegister.classList.add('d-none'); 
})

//registro
registrarse.addEventListener("click",function(){
    let formData=new FormData();
    formData.append("apellidoP",apellidoP.value);
    formData.append("apellidoM",apellidoM.value);
    formData.append("nombre",nombreRegistro.value);
    formData.append("clave",claveRegistro.value);
    formData.append("correo",correoRegistro.value);
    const url=base_url+'clientes/registroDirecto';
    const http = new XMLHttpRequest();
    http.open('POST',url,true);
    http.send(formData);
    http.onreadystatechange=function(){
    if(this.readyState==4 && this.status==200){
        
     const res=JSON.parse(this.responseText);
     Swal.fire({
        title: "Aviso",
        text: res.msg,
        icon:res.icono
      })
     if (res.icono=='success') {
        setTimeout(()=>{
            window.location.reload();
        },2000);
     }

    }
    };

});

});