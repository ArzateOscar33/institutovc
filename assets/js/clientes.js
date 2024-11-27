const tableLista = document.querySelector("#tableListaProductos tbody");
let productosjson = [];
const tblPendientes = document.querySelector("#tblPendientes");

document.addEventListener("DOMContentLoaded", function () {
	if (tableLista) {
		getListaProductos();
	}
	//cargar datos pendientes con DataTables
	$("#tblPendientes").DataTable({
		ajax: {
			url: base_url + "clientes/listarPendientes",
			dataSrc: "",
		},
		columns: [
			{ data: "id_transaccion" },
			{ data: "monto" },
			{ data: "fecha" },
			{ data: "accion" },
		],
		language: {
			//
			url: "//cdn.datatables.net/plug-ins/2.1.8/i18n/es-MX.json",
		},
		dom,
		buttons,
	});
});

//ver carrito
function getListaProductos() {
	let html = "";
	const url = base_url + "principal/listaProductos";
	const http = new XMLHttpRequest();
	http.open("POST", url, true);
	http.send(JSON.stringify(listaCarrito));
	http.onreadystatechange = function () {
		if (this.readyState == 4 && this.status == 200) {
			const res = JSON.parse(this.responseText);
			if (res.totalPayPal > 0) {
				res.productos.forEach((producto) => {
					html += `<tr>
                            <td>
                           <img class="img-thumbnail rounded-circle" src="${
															producto.imagen
														}" alt="" width="100">
                           </td>
                            <td>${producto.nombre}</td>
                            <td> <span class="badge bg-warning">${
															res.moneda + " " + producto.precio
														}</span></td>
                            <td><span class="badge bg-primary">${
															producto.cantidad
														}</td>
                            <td> ${producto.subTotal}</td> 
                             </tr>`;
					//agregar producto para PayPal
					let json = {
						name: producto.nombre,
						unit_amount: {
							currency_code: res.moneda,
							value: producto.precio,
						},
						quantity: producto.cantidad,
					};
					productosjson.push(json);
				});
				tableLista.innerHTML = html;
				document.querySelector("#totalProducto").textContent =
					"TOTAL A PAGAR " + res.moneda + " " + res.total;
				botonPaypal(res.totalPayPal, res.moneda);
			}
		} else {
			tableLista.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">CARRITO VACIO</td>
            </tr>
            `;
		}
	};
}

function botonPaypal(total, moneda) {
	paypal
		.Buttons({
			style: {
				color: "blue",
				shape: "pill",
				label: "pay",
				height: 40,
			},
			// Sets up the transaction when a payment button is clicked
			createOrder: (data, actions) => {
				return actions.order.create({
					purchase_units: [
						{
							amount: {
								currency_code: moneda,
								value: total,
								breakdown: {
									item_total: {
										/* Required when including the `items` array */
										currency_code: moneda,
										value: total,
									},
								},
							},
							items: productosjson,
						},
					],
				});
			},
			// Finalize the transaction after payer approval
			onApprove: (data, actions) => {
				return actions.order.capture().then(function (orderData) {
					registrarPedido(orderData);
				});
			},
		})
		.render("#paypal-button-container");
}

function registrarPedido(datos) {
	const url = base_url + "clientes/registrarPedido";
	const http = new XMLHttpRequest();
	http.open("POST", url, true);
	http.send(
		JSON.stringify({
			pedidos: datos,
			productos: listaCarrito,
		})
	);
	http.onreadystatechange = function () {
		if (this.readyState == 4 && this.status == 200) {
			try {
				// console.log(this.responseText); // Útil para verificar qué está devolviendo el servidor
				const res = JSON.parse(this.responseText); 
				console.log(res);// Analiza la respuesta JSON
				Swal.fire("Aviso", res.msg, res.icono);
				if (res.icono == "success") {
					localStorage.removeItem("listaCarrito");
					setTimeout(() => {
						window.location.reload();
					}, 2000);
				}
			} catch (error) {
				console.error("Error al analizar JSON:", error, this.responseText);
				Swal.fire("Error", "Ocurrió un problema con el servidor", "error");
			}
		}
	};
}

function verPedido(idPedido) {
	const mPedido = new bootstrap.Modal(document.getElementById("modalPedido"));
	const url = base_url + "clientes/verPedido/"+idPedido;
	const http = new XMLHttpRequest();
	http.open("GET", url, true);
	http.send();
	http.onreadystatechange = function () {
		if (this.readyState == 4 && this.status == 200) {
			const res = JSON.parse(this.responseText);

            let html='';
            res.productos.forEach(row => {
                let subTotal=parseFloat(row.precio)*parseInt(row.cantidad);
                html +=`<tr> 
                            <td>${row.producto}</td>
                            <td> <span class="badge bg-warning">${res.moneda + ' '+row.precio}</span></td>
                            <td><span class="badge bg-primary">${ row.cantidad}</td>
                            <td> ${subTotal.toFixed(2)}</td>
                              </tr>`;
            });
            
            document.querySelector('#tablePedidos tbody').innerHTML=html;
			mPedido.show();
		}
	};
}
//credenciales de prueba
//sb-u6blu34135729@personal.example.com
//&-:fI0#I
