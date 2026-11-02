/* IVC — Agenda administrativa V4.9.2-B. Conserva los endpoints y formularios existentes. */
(() => {
  'use strict';
  document.addEventListener('DOMContentLoaded', () => {
    const $ = id => document.getElementById(id);
    const calendarEl = $('calendar');
    if (!calendarEl || typeof FullCalendar === 'undefined') return;
    const modalNuevoEl = $('modalAgendarNuevaCita'); // Formulario reutilizado del footer-admin.
    const modalDetalleEl = $('modalAgenda');
    if (!modalNuevoEl || !modalDetalleEl) return;
    const modalNuevo = bootstrap.Modal.getOrCreateInstance(modalNuevoEl);
    const modalDetalle = bootstrap.Modal.getOrCreateInstance(modalDetalleEl);
    const form = $('frmAgendarCita');
    const dateInput = $('fechaNuevaCita');
    const slot = $('selectHorarios');
    const saveButton = $('btnAgendar');
    const deleteButton = $('btnEliminarAgenda');
    const home = String(typeof base_url === 'string' ? base_url : '/institutovc/').replace(/\/?$/, '/');
    const ymd = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
    dateInput.min = ymd(new Date());
    const alert = options => (typeof Swal === 'undefined' ? Promise.resolve({isConfirmed:window.confirm(options.title+'\n'+(options.text||''))}) : Swal.fire({confirmButtonColor:'#176d9d',...options}));
    const request = async (url, body) => {
      const response=await fetch(url,{method:'POST',body,headers:{'Accept':'application/json'}});
      if(!response.ok) throw new Error(`HTTP ${response.status}`);
      return response.json();
    };
    const updateStats = events => {
      const confirmed=events.filter(e => Number(e.extendedProps?.verify) === 1).length;
      if ($('ivcCitasVisibles')) $('ivcCitasVisibles').textContent=events.length;
      if ($('ivcCitasConfirmadas')) $('ivcCitasConfirmadas').textContent=confirmed;
      if ($('ivcCitasPendientes')) $('ivcCitasPendientes').textContent=events.length-confirmed;
    };
    const calendar = new FullCalendar.Calendar(calendarEl, {
      locale:'es', initialView:'dayGridMonth', height:'auto', nowIndicator:true,
      headerToolbar:{left:'prev,next today',center:'title',right:'dayGridMonth,timeGridWeek,listWeek'},
      buttonText:{today:'Hoy',month:'Mes',week:'Semana',list:'Lista'},
      events:home+'citas/listar',
      datesSet: () => setTimeout(() => updateStats(calendar.getEvents()),0),
      eventsSet: events => updateStats(events),
      eventDidMount: info => {info.el.title=`Cita: ${info.event.title}`;},
      dateClick: info => {
        const selected=info.dateStr.slice(0,10);
        if (selected < ymd(new Date())) {
          alert({icon:'info',title:'Fecha anterior',text:'Selecciona una fecha de hoy en adelante.'});return;
        }
        dateInput.value=selected;
        dateInput.dispatchEvent(new Event('change'));
        modalNuevo.show();
      },
      eventClick: info => {
        const ev=info.event;
        $('id_Cita').value=ev.id;
        $('nombrePaciente').value=ev.title;
        $('fechaCitaAgenda').value=(ev.startStr||'').slice(0,10);
        $('horaCitaAgenda').value=ev.start ? ev.start.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit',hour12:false}) : '—';
        const isConfirmed=Number(ev.extendedProps?.verify)===1;
        const status = $('ivcAgendaStatus');
        status.textContent=isConfirmed?'Confirmada por el paciente':'Pendiente de confirmar';
        status.classList.toggle('is-confirmed', isConfirmed);
        status.classList.toggle('is-pending', !isConfirmed);
        modalDetalle.show();
      },
    });
    calendar.render();
    $('ivcAbrirNuevaCita')?.addEventListener('click',()=>{form?.reset();dateInput.min=ymd(new Date());modalNuevo.show();});
    async function loadSlots() {
      slot.innerHTML='<option value="">Consultando horarios…</option>';
      if (!dateInput.value) {slot.innerHTML='<option value="">Selecciona una fecha</option>';return;}
      const payload=new FormData();payload.append('fechaNuevaCita',dateInput.value);
      try {
        const data=await request(home+'citas/getHorariosDisponibles',payload);
        slot.innerHTML='<option value="">Selecciona un horario</option>';
        if(!Array.isArray(data) || !data.length) {
          await alert({icon:'info',title:'Sin horarios disponibles',text:'Selecciona otra fecha.'});return;
        }
        data.forEach(row => slot.append(new Option(row.horario,row.id_horario)));
      } catch(e) {
        slot.innerHTML='<option value="">No fue posible consultar horarios</option>';
        await alert({icon:'error',title:'Sin conexión',text:'No pudimos consultar la disponibilidad. Vuelve a intentar.'});
      }
    }
    dateInput.addEventListener('change',loadSlots);
    form?.addEventListener('submit',async event=>{
      event.preventDefault();
      if(!form.checkValidity() || !dateInput.value || !slot.value){
        await alert({icon:'warning',title:'Revisa los datos',text:'Completa la fecha, el horario y los datos del paciente.'});return;
      }
      saveButton.disabled=true;
      try {
        const reg=await request(home+'citas/registrarCita',new FormData(form));
        if(reg.icono!=='success' || !reg.token) throw new Error(reg.msg||'No se pudo registrar.');
        modalNuevo.hide();calendar.refetchEvents();
        const payload=new FormData();payload.append('token',String(reg.token));
        const mail=await request(home+'citas/enviarCorreoAgenda',payload);
        await alert(mail.correo_enviado===true
          ? {icon:'success',title:'Cita registrada',text:'Enviamos al paciente un correo con el enlace para confirmar la cita.'}
          : {icon:'warning',title:'Cita registrada',text:'La cita quedó guardada, pero el correo sigue pendiente. Revisa la cola de notificaciones; no registres la misma cita nuevamente.'});
      } catch(e) {
        await alert({icon:'warning',title:'Revisa la operación',text:'No pudimos confirmar el resultado. Revisa la agenda antes de intentar registrar otra cita.'});
        calendar.refetchEvents();
      } finally {saveButton.disabled=false;}
    });
    deleteButton?.addEventListener('click',async () => {
      const id=Number($('id_Cita').value);
      const ok=await alert({icon:'warning',title:'¿Cancelar esta cita?',text:'La cita dejará de ocupar el horario. Su historial de correo se conservará.',showCancelButton:true,confirmButtonText:'Sí, cancelar cita',cancelButtonText:'Volver'});
      if(!ok.isConfirmed || id<1)return;
      try {
        const resp=await fetch(home+'citas/eliminarCita/'+id,{method:'GET',headers:{Accept:'application/json'}});
        const result=await resp.json();
        if(result.icono!=='success') throw new Error(result.msg||'Operación fallida');
        modalDetalle.hide();calendar.refetchEvents();
        await alert({icon:'success',title:'Cita cancelada',text:'El horario vuelve a estar disponible.'});
      } catch(e) {await alert({icon:'error',title:'No se canceló',text:'Inténtalo nuevamente o consulta con soporte.'});}
    });
  });
})();
