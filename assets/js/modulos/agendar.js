const frmAgendar = document.querySelector("#frmAgendar");
const iapPaterno = document.querySelector("#iapPaterno");
const iapMaterno = document.querySelector("#iapMaterno");
const iNombre = document.querySelector("#iNombre");
const iCorreo = document.querySelector("#iCorreo");
const iTelefono = document.querySelector("#iTelefono");
const fechaCita = document.querySelector("#fechaCita");
const selectHorarios = document.querySelector("#selectHorarios");

// Establecer fecha mínima (día siguiente a hoy)
const hoy = new Date();
const fechaMinima = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + 1)
    .toISOString()
    .split("T")[0];
fechaCita.setAttribute("min", fechaMinima);

const btnAgendar = document.querySelector("#btnAgendar");
document.addEventListener("DOMContentLoaded", function () {
    // Deshabilitar domingos
    fechaCita.addEventListener("change", function () {
        const fechaSeleccionada = new Date(fechaCita.value);
    
        if (fechaSeleccionada.getDay() === 0) { // 0 es domingo
            Swal.fire("Aviso", "No se permiten citas los domingos", "warning");
            fechaCita.value = ""; // Limpia la selección
            return;
        }
    
        if (fechaSeleccionada) {
            const url = base_url + "principal/getHorariosDisponibles";
            const formData = new FormData();
            formData.append("fechaCita", fechaSeleccionada.toISOString().split("T")[0]);
    
            const http = new XMLHttpRequest();
            http.open("POST", url, true);
            http.send(formData);
    
            http.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) {
                    try {
                        const horarios = JSON.parse(this.responseText);
                        selectHorarios.innerHTML = '<option value="">Selecciona un horario</option>';
                        if (Array.isArray(horarios) && horarios.length > 0) {
                            horarios.forEach((horario) => {
                                const option = document.createElement("option");
                                option.value = horario.id_horario;
                                option.textContent = horario.horario;
                                selectHorarios.appendChild(option);
                            });
                        } else {
                            Swal.fire("Aviso", "No hay horarios disponibles para esta fecha", "warning");
                        }
                    } catch (error) {
                        console.error("Error al procesar la respuesta:", error);
                    }
                }
            };
        } else {
            selectHorarios.innerHTML = '<option value="">Selecciona un horario</option>';
        }
    });
    




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
                                // window.location.reload();
                                enviarCorreoAgenda(iCorreo.value, res.token);
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

    function alertas(msg, icono) {
        Swal.fire("Aviso?", msg.toUpperCase(), icono);
    }
    
    function enviarCorreoAgenda(correo, token) {

    	  // Obtén los valores de los campos 
		  const iNombreEC = document.getElementById("iNombre").value;
		 const fechaCitaEC = document.getElementById("fechaCita").value;
         const iapMaternoEC = document.getElementById("iapMaterno").value;
         const iapPaternoEC=document.getElementById("iapPaterno").value
		     // Obtén el elemento select y su opción seleccionada
			 const selectHorariosEC = document.getElementById("selectHorarios");
			 const horarioId = selectHorarios.value; // ID del horario seleccionado (value del option)
			 const horarioTexto = selectHorarios.options[selectHorarios.selectedIndex].text; // Texto del horario seleccionado
		 

		let formData = new FormData();
		formData.append("token", token);
		formData.append("iNombre", iNombreEC);
		formData.append("fechaCita", fechaCitaEC);
		formData.append("selectHorarios", horarioTexto);
		formData.append("iapMaterno", iapMaternoEC);
		formData.append("iapPaterno", iapPaternoEC);
		formData.append("iCorreo", correo);
		//console.log( iNombreEC, fechaCitaEC, horarioTexto);
        const url = base_url + "principal/enviarCorreoAgenda";
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(formData);
        http.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                //console.log(res);
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

});





