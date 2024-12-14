//calendario
let calendarEl = document.getElementById('calendar');
//modal y formulario para modificar
let modalAgenda = new bootstrap.Modal(document.getElementById('modalAgenda'));
let frmAgenda = document.getElementById('formularioAgenda');
let eliminarAgenda = document.getElementById('btnEliminar');

//mopdal y formulario para nueva cita 

let modalAgendarNuevaCita = new bootstrap.Modal(document.getElementById('modalAgendarNuevaCita'));
let frmNuevaCita = document.getElementById('frmAgendarCita');
let btnAgendarNuevaCita = document.getElementById('btnAgendarNuevaCita');
let fechaCita = document.getElementById('fechaNuevaCita')

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

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next,today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        dateClick: function (info) {
            document.getElementById('fechaCitaAgenda').value = info.dateStr;
            document.getElementById('fechaNuevaCita').value = info.dateStr;
            modalAgenda.show();

            document.getElementById('tituloModalAgenda').textContent = "Agendar Nueva Cita";

        }
    })
    calendar.render();
    //formulario para modificar las citas
    frmAgenda.addEventListener('submit', function (e) {
        e.preventDefault();
        // const title = document.getElementById('tiuloModalAgenda').value;
        //const fecha = document.getElementById('fechaCitaAgenda').value;


    });



    //formulario para crear una nueva cita 
    btnAgendarNuevaCita.addEventListener('click', function () {
        modalAgendarNuevaCita.show();

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
                    selectHorarios.innerHTML = '<option value="">Selecciona un horario</option>';
                    if (Array.isArray(horarios) && horarios.length > 0) {
                        horarios.forEach(horario => {
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


});