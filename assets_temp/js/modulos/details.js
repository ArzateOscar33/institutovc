const btnAddCart = document.querySelector('#btnAddCart');
const cantidad = document.querySelector('#product-quanity');
const idProducto = document.querySelector('#idProducto');
const btnAddListaDeseo = document.querySelector('#btnAddDeseoSingle');

document.addEventListener("DOMContentLoaded", function() {
    if (btnAddCart) { // Verifica que btnAddCart no sea null
        btnAddCart.addEventListener("click", function() {
            agregarCarrito(idProducto.value, cantidad.value);
        });

       
    } else {
        console.error("El botón #btnAddCart no existe en el DOM.");
    }

 
});