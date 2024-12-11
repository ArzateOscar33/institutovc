const frmAgendar = document.querySelector("#frmAgendar");
const iapPaterno = document.querySelector("#iapPaterno");
const iapMaterno = document.querySelector("#iapMaterno");
const iNombre = document.querySelector("#iNombre");
const iCorreo = document.querySelector("#iCorreo");
const iTelefono = document.querySelector("#iTelefono"); 
const fechaCita = document.querySelector("#fechaCita"); 
const selectHorarios = document.querySelector("#selectHorarios"); 



const btnAgendar = document.querySelector("#btnAgendar");
document.addEventListener("DOMContentLoaded", function() { 
 
  //MODIFICAR PARA APARECER Y DESAPARECER HORARIOS
    /* btnRegister.addEventListener("click", function () {
		formLogin.classList.add("d-none");
		formRegister.classList.remove("d-none");
	});
	btnLogin.addEventListener("click", function () {
		formLogin.classList.remove("d-none");
		formRegister.classList.add("d-none");
	});*/
    frmAgendar.addEventListener("submit", function (e) {
        e.preventDefault();
    
        if (
            iapPaterno.value === "" ||
            iapMaterno.value === "" ||
            iNombre.value === "" ||
            iCorreo.value === "" ||
            iTelefono.value === "" ||
            fechaCita.value === "" ||
            selectHorarios.value === ""
        ) {
            Swal.fire("Aviso", "Todos los campos son requeridos", "warning");
        } else {
            let formData = new FormData(this);
            const url = base_url + "principal/registrarCita";
            const http = new XMLHttpRequest();
            http.open("POST", url, true);
            http.send(formData);
    
            http.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) {
                    try {
                        const res = JSON.parse(this.responseText);
                        Swal.fire({
                            title: "Aviso",
                            text: res.msg,
                            icon: res.icono,
                        });
                        if (res.icono === "success") {
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        }
                    } catch (error) {
                        console.error("Error al procesar JSON:", error, this.responseText);
                        Swal.fire("Error", "Hubo un problema con la respuesta del servidor", "error");
                    }
                }
            };
        }
    });
    
    });





function alertas(msg, icono) {
    Swal.fire("Aviso?", msg.toUpperCase(), icono);
}

function enviarCorreo(correo, token) {
	let formData = new FormData();
	formData.append("token", token);
	formData.append("correo", correo);
	const url = base_url + "principal/enviarCorreo";
	const http = new XMLHttpRequest();
	http.open("POST", url, true);
	http.send(formData);
	http.onreadystatechange = function () {
		if (this.readyState == 4 && this.status == 200) {
			const res = JSON.parse(this.responseText);
			Swal.fire({
				title: "Aviso",
				text: res.msg,
				icon: res.icono,
			});
			if (res.icono == "success") {
				setTimeout(() => {
					window.location.reload();
				}, 2000);
			}
		}
	};
}