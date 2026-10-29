/* Instituto Visual California · Citas V6
 * Integra los endpoints existentes sin alterar controllers ni models.
 * Registra la cita primero y envía después el mensaje de verificación.
 */
(() => {
  'use strict';
  const root = document.getElementById('ivc-appointment');
  if (!root) return;

  const $ = (id) => document.getElementById(id);
  const form = $('frmAgendar');
  const dateInput = $('fechaCita');
  const timeSelect = $('selectHorarios');
  const quickDates = $('ivc-appt-quick-dates');
  const timesGrid = $('ivc-appt-times');
  const timeStatus = $('ivc-appt-time-status');
  const selectedDateTag = $('ivc-appt-selected-date');
  const stepOne = $('ivc-appt-step-one');
  const stepTwo = $('ivc-appt-step-two');
  const progressOne = $('ivc-appt-progress-one');
  const progressTwo = $('ivc-appt-progress-two');
  const dateError = $('ivc-appt-date-error');
  const timeError = $('ivc-appt-time-error');
  const nextButton = $('ivc-appt-next');
  const backButton = $('ivc-appt-back');
  const submitButton = $('btnAgendar');
  const submitError = $('ivc-appt-submit-error');
  const result = $('ivc-appt-result');
  const resultMessage = $('ivc-appt-result-message');
  const mailWarning = $('ivc-appt-mail-warning');
  const retryMail = $('ivc-appt-retry-mail');
  const summaryDate = $('ivc-appt-summary-date');
  const summaryTime = $('ivc-appt-summary-time');
  const ids = ['iNombre', 'iapPaterno', 'iapMaterno', 'iCorreo', 'iTelefono'];

  if (!form || !dateInput || !timeSelect) return;

  let chosenTime = '';
  let chosenTimeLabel = '';
  let requestSerial = 0;
  let slotsAbort = null;
  let isSubmitting = false;
  let booked = false;
  let tokenForEmail = '';
  let emailBusy = false;
  const MX_DATE = new Intl.DateTimeFormat('es-MX', {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'});
  const MX_SHORT_DATE = new Intl.DateTimeFormat('es-MX', {weekday: 'short', day: 'numeric', month: 'short'});

  // La fecha se formatea en hora LOCAL, no UTC (evita el desfase de toISOString).
  const ymd = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  const tomorrow = new Date();
  tomorrow.setHours(12, 0, 0, 0);
  tomorrow.setDate(tomorrow.getDate() + 1);
  const minDate = ymd(tomorrow);
  dateInput.min = minDate;

  function parseLocal(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day, 12, 0, 0, 0);
    if (ymd(date) !== value) return null;
    return date;
  }
  function validDate(value) {
    const date = parseLocal(value);
    return !!(date && value >= minDate && date.getDay() !== 0);
  }
  function showError(element, text, input) {
    if (element) { element.textContent = text; element.hidden = !text; }
    if (input) {
      if (text) input.setAttribute('aria-invalid', 'true');
      else input.removeAttribute('aria-invalid');
    }
  }
  function showTimeMessage(message, state = 'idle') {
    timesGrid.replaceChildren();
    const item = document.createElement('div');
    item.className = 'ivc-appt-empty-hours';
    if (state === 'loading') {
      const spin = document.createElement('span'); spin.className = 'ivc-appt-hour-loader'; spin.setAttribute('aria-hidden', 'true'); item.append(spin);
    } else {
      const ico = document.createElement('span'); ico.textContent = state === 'error' ? '!' : '◷'; ico.setAttribute('aria-hidden', 'true'); item.append(ico);
    }
    const text = document.createElement('span'); text.textContent = message; item.append(text);
    timesGrid.append(item);
  }
  function formatSlot(value) {
    const match = /^(\d\d?):(\d\d)/.exec(String(value));
    if (!match) return String(value);
    const hour = Number(match[1]);
    return `${hour % 12 || 12}:${match[2]} ${hour < 12 ? 'a. m.' : 'p. m.'}`;
  }
  function updateSummary() {
    const date = parseLocal(dateInput.value);
    summaryDate.textContent = date && validDate(dateInput.value) ? MX_SHORT_DATE.format(date) : 'Por seleccionar';
    summaryTime.textContent = chosenTimeLabel ? formatSlot(chosenTimeLabel) : 'Por seleccionar';
    selectedDateTag.textContent = date && validDate(dateInput.value) ? MX_SHORT_DATE.format(date) : 'Selecciona una fecha';
  }
  function updateQuickDates() {
    quickDates.querySelectorAll('button').forEach((button) => {
      button.setAttribute('aria-pressed', String(button.dataset.date === dateInput.value));
    });
  }
  function resetSlots() {
    chosenTime = '';
    chosenTimeLabel = '';
    timeSelect.replaceChildren(new Option('Selecciona un horario', ''));
    showError(timeError, '', timeSelect);
    showTimeMessage('Selecciona un día para consultar sus horarios.');
    updateSummary();
  }
  function renderQuickDates() {
    quickDates.replaceChildren();
    const date = new Date(tomorrow);
    let added = 0;
    while (added < 5) {
      if (date.getDay() !== 0) {
        const value = ymd(date);
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'ivc-appt-date-chip'; button.dataset.date = value;
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-label', MX_DATE.format(date));
        const weekday = document.createElement('span'); weekday.textContent = new Intl.DateTimeFormat('es-MX', {weekday: 'short'}).format(date).replace('.', '').toUpperCase();
        const day = document.createElement('strong'); day.textContent = String(date.getDate());
        const month = document.createElement('small'); month.textContent = new Intl.DateTimeFormat('es-MX', {month: 'short'}).format(date).replace('.', '').toUpperCase();
        button.append(weekday, day, month);
        button.addEventListener('click', () => { dateInput.value = value; dateInput.dispatchEvent(new Event('change', {bubbles: true})); });
        quickDates.append(button);
        added++;
      }
      date.setDate(date.getDate() + 1);
    }
  }

  async function loadSlots() {
    const selected = dateInput.value;
    if (slotsAbort) slotsAbort.abort();
    const ownRequest = ++requestSerial;
    resetSlots();
    updateQuickDates();
    showError(dateError, '', dateInput);
    if (!selected) return;
    if (!validDate(selected)) {
      showError(dateError, selected < minDate ? 'Elige una fecha a partir de mañana.' : 'Selecciona una fecha válida. Los domingos no se atienden citas.', dateInput);
      timeStatus.textContent = 'Selecciona otra fecha para ver los horarios.';
      return;
    }
    showTimeMessage('Consultando horarios disponibles…', 'loading');
    timeStatus.textContent = 'Consultando disponibilidad en el sistema…';
    slotsAbort = new AbortController();
    try {
      const params = new FormData(); params.append('fechaCita', selected);
      const response = await fetch(form.dataset.horariosUrl, {method: 'POST', body: params, signal: slotsAbort.signal, headers: {'Accept': 'application/json'}});
      if (!response.ok) throw new Error('HTTP ' + response.status);
      const hours = await response.json();
      if (requestSerial !== ownRequest) return;
      if (!Array.isArray(hours)) throw new Error('Formato inesperado');
      timesGrid.replaceChildren();
      const slots = hours.filter((entry) => entry && String(entry.id_horario || '').trim() !== '' && typeof entry.horario === 'string');
      if (!slots.length) {
        showTimeMessage('No hay horarios disponibles este día. Prueba otra fecha.');
        timeStatus.textContent = 'No se encontraron horarios disponibles para la fecha seleccionada.';
        return;
      }
      slots.forEach((entry) => {
        const id = String(entry.id_horario);
        const originalText = String(entry.horario);
        timeSelect.append(new Option(originalText, id));
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'ivc-appt-time-button';
        button.dataset.id = id;
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-label', `Seleccionar horario ${formatSlot(originalText)}`);
        const label = document.createElement('span'); label.textContent = formatSlot(originalText);
        button.append(label);
        button.addEventListener('click', () => {
          chosenTime = id;
          chosenTimeLabel = originalText;
          timeSelect.value = id;
          timesGrid.querySelectorAll('button').forEach((el) => el.setAttribute('aria-pressed', String(el === button)));
          showError(timeError, '', timeSelect);
          updateSummary();
          timeStatus.textContent = `Seleccionaste las ${formatSlot(originalText)}.`;
        });
        timesGrid.append(button);
      });
      timeStatus.textContent = `${slots.length} horario${slots.length === 1 ? '' : 's'} disponible${slots.length === 1 ? '' : 's'}. Elige uno para continuar.`;
    } catch (error) {
      if (error.name === 'AbortError' || requestSerial !== ownRequest) return;
      console.error('No se pudieron consultar los horarios:', error);
      showTimeMessage('No pudimos cargar los horarios. Cambia la fecha o inténtalo de nuevo.', 'error');
      timeStatus.textContent = 'Error al consultar los horarios; vuelve a elegir la fecha.';
    }
  }

  function showStep(number) {
    stepOne.hidden = number !== 1;
    stepTwo.hidden = number !== 2;
    progressOne.classList.toggle('is-current', number === 1);
    progressOne.classList.toggle('is-complete', number > 1);
    progressTwo.classList.toggle('is-current', number === 2);
    if (number === 2) progressTwo.setAttribute('aria-current', 'step');
    else progressTwo.removeAttribute('aria-current');
    if (number === 1) progressOne.setAttribute('aria-current', 'step');
    else progressOne.removeAttribute('aria-current');
    const focusTitle = $(number === 1 ? 'ivc-appt-step-one-title' : 'ivc-appt-step-two-title');
    focusTitle.tabIndex = -1; focusTitle.focus({preventScroll: true});
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      root.querySelector('.ivc-appt-panel').scrollIntoView({behavior: 'smooth', block: 'start'});
    }
  }

  nextButton.addEventListener('click', () => {
    if (!validDate(dateInput.value)) {
      showError(dateError, 'Selecciona una fecha válida a partir de mañana (excepto domingos).', dateInput);
      dateInput.focus(); return;
    }
    if (!chosenTime || timeSelect.value !== chosenTime) {
      showError(timeError, 'Selecciona uno de los horarios disponibles para continuar.', timeSelect);
      timesGrid.querySelector('button')?.focus(); return;
    }
    showStep(2);
  });
  backButton.addEventListener('click', () => { if (!booked && !isSubmitting) showStep(1); });
  dateInput.addEventListener('change', loadSlots);
  dateInput.addEventListener('input', () => { showError(dateError, '', dateInput); });

  function validateDetails() {
    const messages = {
      iNombre: 'Escribe tu nombre.', iapPaterno: 'Escribe tu apellido paterno.',
      iapMaterno: 'Escribe tu apellido materno.', iCorreo: 'Escribe un correo electrónico válido.',
      iTelefono: 'Introduce un teléfono de 10 dígitos.'
    };
    let firstInvalid = null;
    ids.forEach((id) => {
      const input = $(id);
      const value = input.value.trim();
      let valid = value.length > 0;
      if (id === 'iCorreo') valid = valid && input.validity.valid;
      if (id === 'iTelefono') valid = /^\d{10}$/.test(value);
      showError($('ivc-appt-error-' + id), valid ? '' : messages[id], input);
      if (!valid && !firstInvalid) firstInvalid = input;
    });
    if (firstInvalid) firstInvalid.focus();
    return !firstInvalid;
  }
  ids.forEach((id) => {
    const input = $(id);
    input.addEventListener('input', () => showError($('ivc-appt-error-' + id), '', input));
  });
  $('iTelefono').addEventListener('input', (event) => {
    event.target.value = event.target.value.replace(/\D/g, '').slice(0, 10);
  });

  async function postJson(url, payload) {
    const response = await fetch(url, {method: 'POST', body: payload, headers: {'Accept': 'application/json'}});
    if (!response.ok) throw new Error('HTTP ' + response.status);
    return await response.json();
  }

  async function sendConfirmation() {
    if (!tokenForEmail || emailBusy) return;
    emailBusy = true;
    retryMail.disabled = true;
    retryMail.textContent = 'Enviando correo…';
    resultMessage.textContent = 'Tu cita está registrada. Estamos enviando el enlace para confirmarla.';
    try {
      const payload = new FormData();
      payload.append('token', tokenForEmail);
      payload.append('iNombre', $('iNombre').value.trim());
      payload.append('iapPaterno', $('iapPaterno').value.trim());
      payload.append('iapMaterno', $('iapMaterno').value.trim());
      payload.append('fechaCita', dateInput.value);
      payload.append('selectHorarios', chosenTimeLabel); // El email espera el TEXTO (08:00), no el ID.
      payload.append('iCorreo', $('iCorreo').value.trim());
      const res = await postJson(form.dataset.correoUrl, payload);
      if (res.icono !== 'success') throw new Error(res.msg || 'No se envió el correo');
      resultMessage.textContent = `Te enviamos un correo a ${$('iCorreo').value.trim()} con un enlace para confirmar la cita. Revisa también la carpeta de spam.`;
      mailWarning.hidden = true;
    } catch (error) {
      console.error('La cita ya está registrada, pero falló el correo:', error);
      resultMessage.textContent = 'Tu cita ya está registrada en el sistema. No vuelvas a reservar este mismo horario.';
      mailWarning.hidden = false;
    } finally {
      retryMail.disabled = false;
      retryMail.textContent = 'Reintentar envío';
      emailBusy = false;
    }
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (isSubmitting || booked) return;
    if (!validDate(dateInput.value) || !chosenTime || timeSelect.value !== chosenTime) {
      showStep(1);
      showError(timeError, 'Revisa la fecha y selecciona un horario disponible.', timeSelect);
      return;
    }
    if (!validateDetails()) return;
    showError(submitError, '');
    isSubmitting = true;
    submitButton.disabled = true;
    backButton.disabled = true;
    submitButton.textContent = 'Registrando tu cita…';
    try {
      const data = new FormData(form);
      const res = await postJson(form.dataset.agendarUrl, data);
      if (res.icono !== 'success' || !res.token) {
        showError(submitError, res.msg || 'No se pudo registrar la cita. Revisa los datos e inténtalo nuevamente.');
        return;
      }
      booked = true;
      tokenForEmail = String(res.token);
      form.hidden = true;
      $('ivc-appt-progress-one').classList.add('is-complete');
      progressTwo.classList.remove('is-current');
      progressTwo.classList.add('is-complete');
      progressTwo.removeAttribute('aria-current');
      result.hidden = false;
      result.focus({preventScroll: true});
      await sendConfirmation();
    } catch (error) {
      console.error('Error al registrar la cita:', error);
      showError(submitError, 'No pudimos verificar si se registró tu solicitud. Antes de volver a intentar, comunícate con la óptica para evitar duplicados.');
    } finally {
      isSubmitting = false;
      if (!booked) {
        submitButton.disabled = false;
        backButton.disabled = false;
        submitButton.replaceChildren(document.createTextNode('Registrar mi cita'));
      }
    }
  });
  retryMail.addEventListener('click', sendConfirmation);

  renderQuickDates();
  resetSlots();

  // Animaciones progresivas, con contenido visible si JS o GSAP no están disponibles.
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    const reveals = root.querySelectorAll('.ivc-appt-reveal');
    root.classList.add('ivc-appt-animate');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('ivc-appt-visible');
          observer.unobserve(entry.target);
        }
      });
    }, {threshold: 0.07, rootMargin: '0px 0px -25px 0px'});
    reveals.forEach((node) => observer.observe(node));
  }
})();
