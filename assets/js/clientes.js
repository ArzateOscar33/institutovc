const tableLista = document.querySelector('#tableListaProductos tbody');

document.addEventListener("DOMContentLoaded", function () {
    if (tableLista) {

        getListaProductos();
    }
});

//ver carrito
function getListaProductos() {
    const url = base_url + 'principal/listaProductos';
    const http = new XMLHttpRequest();
    http.open('POST', url, true);
    http.send(JSON.stringify(listaCarrito));
    http.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            let html = '';
            res.productos.forEach(producto => {
                html += `<tr>
                        <td>
                       <img class="img-thumbnail rounded-circle" src="${producto.imagen}" alt="" width="100">
                       </td>
                        <td>${producto.nombre}</td>
                        <td> <span class="badge bg-warning">${res.moneda + ' ' + producto.precio}</span></td>
                        <td><span class="badge bg-primary">${producto.cantidad}</td>
                        <td> ${producto.subTotal}</td> 
                         </tr>`;
            });
            console.log("la lista es " + tableLista);
            tableLista.innerHTML = html;
            document.querySelector('#totalProducto').textContent = 'TOTAL A PAGAR ' + res.moneda + ' ' + res.total;
            botonPaypal(res.totalPayPal);

        }
    }
}

function botonPaypal(total) {
    console.log("entro a la funcion");
    // Render the PayPal button into #paypal-button-container
    paypal.Buttons({ 
        style: {
            color:  'blue',
            shape:  'pill',
            label:  'pay',
            height: 40
        },
        //sets up the transaction when a payment button is clicked
        createOrder: function(data, actions) {
            return actions.order.create({
                application_context:{
                    shipping_preference:'NO_SHIPPING',
                },
                purchase_units:[{
                    amount:{
                        currency_code: 'USD',
                        value: total,
                    }
                }]
            })
        },
        //call your serve4r to finalize the transaction
        onApprove: function(data, actions){
            console.log(data);
            //return fetch('/demo/checkout/api/paypal/order/' + data.orderID + '/capture/', {
            //  method: 'post'
            //}).then(function(res) {
            //   return res.json();
            // }).then(function(orderData) {
            // });
        }

    }).render('#paypal-button-container');


}