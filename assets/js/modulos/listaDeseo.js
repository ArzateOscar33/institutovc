const tableLista=document.querySelector('#tableListaDeseo tbody');
document.addEventListener("DOMContentLoaded",function(){
 getListaDeseo();
});

function getListaDeseo(){
    const url=base_url+'principal/listaDeseo';
    const http = new XMLHttpRequest();
    http.open('POST',url,true);
    http.send(JSON.stringify(listaDeseo));
    http.onreadystatechange=function(){
    if(this.readyState==4 && this.status==200){
        const res=JSON.parse(this.responseText);
        let html='';
        res.productos.forEach(producto => {
            html +=`<tr>
                        <td>
                       <img class="img-thumbnail rounded-circle" src="${producto.imagen}" alt="" width="100">
                       </td>
                        <td>${producto.nombre}</td>
                        <td> <span class="badge bg-warning">${res.moneda + ' '+producto.precio}</span></td>
                        <td><span class="badge bg-primary">${producto.cantidad}</td>
                        <td><button class="btn btn-danger btnEliminarDeseo" type="button" prod="${producto.id}"> <i class="fas fa-trash"></i> </button>
                        <button class="btn btn-primary btnAddCart" type="button"  prod="${producto.id}"><i class="fas fa-cart-plus"></i></button></td>
                         </tr>`;
        });
        
    tableLista.innerHTML=html;
    btnAgregarProducto();
    btnEliminarDeseo();
    }


    //agregar productos desde la lista de deseos

}
}
function btnEliminarDeseo(){

    let listaEliminar=document.querySelectorAll('.btnEliminarDeseo');
    console.log(listaEliminar);
    for (let i = 0; i < listaEliminar.length; i++) {
        listaEliminar[i].addEventListener('click',function(){
            let idProducto=listaEliminar[i].getAttribute('prod');
            eliminarListaDeseo(idProducto);
        });
        
    }
}

function eliminarListaDeseo(idProducto) {
 
    for (let index = 0; index < listaDeseo.length; index++) {
         if(listaDeseo[index]['idProducto']==idProducto){
            listaDeseo.splice(index,1);

         }
    }
localStorage.setItem('listaDeseo',JSON.stringify(listaDeseo));
getListaDeseo();
cantidadDeseo();
Swal.fire({
    title: "Aviso",
    text: "PRODUCTO ELIMINADO DE TU LISTA DE DESEOS ",
    icon: "success"
  });
}
function btnAgregarProducto(){

    let listaAgregar=document.querySelectorAll('.btnAddCart');
    
    for (let i = 0; i < listaAgregar.length; i++) {
        listaAgregar[i].addEventListener('click',function(){
            let idProducto=listaAgregar[i].getAttribute('prod');
            agregarCarrito(idProducto,1,true);
        })
    }
} 