const tableLista = document.querySelector('#tableListaProductos tbody');
let productosjson = [];

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
                //agregar producto para PayPal
                let json = {
                    "name": producto.nombre,
                    "unit_amount": {
                        "currency_code": res.moneda,
                        "value": producto.precio
                    },
                    "quantity": producto.cantidad

                }
                productosjson.push(json);
            });
            console.log("la lista es " + tableLista);
            tableLista.innerHTML = html;
            document.querySelector('#totalProducto').textContent = 'TOTAL A PAGAR ' + res.moneda + ' ' + res.total;
            botonPaypal(res.totalPayPal, res.moneda);

        }
    }
}

function botonPaypal(total,moneda) {
    // Render the PayPal button into #paypal-button-container
    paypal.Buttons({
        style: {
            color: 'blue',
            shape: 'pill',
            label: 'pay',
            height: 40
        },
        //sets up the transaction when a payment button is clicked
        createOrder: function (data, actions) {
            return actions.order.create({
                application_context: {
                    shipping_preference: 'NO_SHIPPING',
                },
                purchase_units: [{
                    amount: {
                        currency_code: moneda,
                        value: total,
                        "breakdown": {
                            "item_total": {

                                currency_code: moneda,
                                value: total,
                            }
                        }
                    },
                    "items": productosjson
                }]
            })
        },
        //call your server to finalize the transaction
        onApprove:(data, actions)=> {
         return actions.order.capture().then(function(orderData){
            Swal.fire("Aviso?", "Pago Realizado con Exito", "success");
            registrarPedido(orderData);
         });
        }
    }).render('#paypal-button-container');


}

function registrarPedido(datos){
    const url = base_url + 'clientes/registrarPedido';
    const http = new XMLHttpRequest();
    http.open('POST', url, true);
    http.send(JSON.stringify(datos));
    http.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            console.log(this.responseText);
           // const res = JSON.parse(this.responseText);
           
        }
    } 
}

//credenciales de prueba
//sb-u6blu34135729@personal.example.com
//&-:fI0#I