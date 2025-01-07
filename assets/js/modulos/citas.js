//calendario
let calendarEl = document.getElementById("calendar");

let frmAgenda = document.getElementById("formularioAgenda");
let eliminarAgenda = document.getElementById("btnEliminarAgenda");

//mopdal y formulario para nueva cita
let modalAgenda = new bootstrap.Modal(document.getElementById("modalAgenda"));
let modalAgendarNuevaCita = new bootstrap.Modal(
	document.getElementById("modalAgendarNuevaCita")
);

//let modalAgenda = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAgenda'));
//let modalAgendarNuevaCita = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAgendarNuevaCita'));//

//let modalAgendarNuevaCita = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAgendarNuevaCita'));

let frmNuevaCita = document.getElementById("frmAgendarCita");
let btnAgendarNuevaCita = document.getElementById("btnAgendarNuevaCita");
let fechaCita = document.getElementById("fechaNuevaCita");

const iapPaterno = document.querySelector("#iapPaterno");
const iapMaterno = document.querySelector("#iapMaterno");
const iNombre = document.querySelector("#iNombre");
const iCorreo = document.querySelector("#iCorreo");
const iTelefono = document.querySelector("#iTelefono");
const selectHorarios = document.querySelector("#selectHorarios");

// Establecer fecha mínima (hoy)
const hoy = new Date();
const fechaMinima = hoy.toISOString().split("T")[0];
fechaCita.setAttribute("min", fechaMinima);

const btnAgendar = document.querySelector("#btnAgendar");

document.addEventListener("DOMContentLoaded", function () {
	var calendarEl = document.getElementById("calendar");
	var calendar = new FullCalendar.Calendar(calendarEl, {
		locale: "es",
		events: base_url + "/citas/listar",
		initialView: "dayGridMonth",
		headerToolbar: {
			left: "prev,next,today",
			center: "title",
			right: "dayGridMonth,timeGridWeek,listWeek",
		},
		validRange: {
			start: new Date(hoy.setDate(hoy.getDate() + 1))
				.toISOString()
				.split("T")[0], // Restringe a fechas posteriores a hoy
		},
		dateClick: function (info) {
			document.getElementById("fechaNuevaCita").value = info.dateStr;
			//
			modalAgendarNuevaCita.show();
		},
		eventClick: function (info) {
			modalAgenda.show();
			let id_cita = info.event.id;

			document.getElementById("tituloModalAgenda").textContent =
				"Cita Agendada";
			// Extraer solo la parte de la fecha (YYYY-MM-DD)
			const fechaEvento = info.event.startStr.split("T")[0];
			const horaEventoCompleta = info.event.startStr.split("T")[1]; // Hora completa (HH:mm:ss-08:00)
			const horaEvento = horaEventoCompleta.split(":").slice(0, 2).join(":"); // Solo HH:mm

			document.getElementById("fechaCitaAgenda").value = fechaEvento;
			document.getElementById("horaCitaAgenda").value = horaEvento; // Input de hora
			document.getElementById("id_Cita").value = info.event.id;

			document.getElementById("nombrePaciente").value = info.event.title;
		},
	});
	calendar.render();
	//formulario para modificar las citas
	frmAgenda.addEventListener("submit", function (e) {
		e.preventDefault();
	});

	// Deshabilitar domingos
	fechaCita.addEventListener("change", function () {
		const fechaSeleccionada = fechaCita.value;

		if (fechaSeleccionada) {
			const url = base_url + "citas/getHorariosDisponibles";
			const formData = new FormData();
			formData.append("fechaNuevaCita", fechaSeleccionada);

			const http = new XMLHttpRequest();
			http.open("POST", url, true);
			http.send(formData);

			http.onreadystatechange = function () {
				if (this.readyState === 4 && this.status === 200) {
					try {
						const horarios = JSON.parse(this.responseText);
						selectHorarios.innerHTML =
							'<option value="">Selecciona un horario</option>';
						if (Array.isArray(horarios) && horarios.length > 0) {
							horarios.forEach((horario) => {
								const option = document.createElement("option");
								option.value = horario.id_horario;
								option.textContent = horario.horario;
								selectHorarios.appendChild(option);
							});
						} else {
							Swal.fire(
								"Aviso",
								"No hay horarios disponibles para esta fecha",
								"warning"
							);
						}
					} catch (error) {
						console.error("Error al procesar la respuesta:", error);
					}
				}
			};
		} else {
			selectHorarios.innerHTML =
				'<option value="">Selecciona un horario</option>';
		}
	});

	frmNuevaCita.addEventListener("submit", function (e) {
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
			const url = base_url + "citas/registrarCita";
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
						Swal.fire(
							"Error",
							"Hubo un problema con la respuesta del servidor",
							"error"
						);
					}
				}
			};
		}
	});

	function alertas(msg, icono) {
		Swal.fire("Aviso?", msg.toUpperCase(), icono);
	}

	function enviarCorreoAgenda(correo, token) {
		let formData = new FormData();
		formData.append("token", token);
		formData.append("iCorreo", correo);
		const url = base_url + "principal/enviarCorreoAgenda";
		const http = new XMLHttpRequest();
		http.open("POST", url, true);
		http.send(formData);
		http.onreadystatechange = function () {
			if (this.readyState == 4 && this.status == 200) {
				const res = JSON.parse(this.responseText);
				console.log(res);
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

	eliminarAgenda.addEventListener("click", function () {
		modalAgenda.hide();
		Swal.fire({
			title: "Advertencia ",
			text: "Esta Seguro de Eliminar ",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Si, Eliminar!",
			cancelButtonText: "Cancelar ",
		}).then((result) => {
			if (result.isConfirmed) {
				const id_cita = document.getElementById("id_Cita").value;
				const url = base_url + "citas/eliminarCita/" + id_cita;
				const http = new XMLHttpRequest();
				http.open("GET", url, true);
				http.send();
				http.onreadystatechange = function () {
					if (this.readyState == 4 && this.status == 200) {
						const res = JSON.parse(this.responseText);
						console.log(res);
						if (res.icono == "success") {
							calendar.refetchEvents();
						}
						Swal.fire({
							title: "Aviso",
							text: res.msg,
							icon: res.icono,
						});
					}
				};
			}
		});
	});
});
