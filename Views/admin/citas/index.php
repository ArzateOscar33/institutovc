<?php include_once 'Views/template/header-admin.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-agenda-admin-v492b.css?v=1.1.0">

<main class="ivc-agenda-admin" id="ivc-agenda-admin">
    <header class="ivc-agenda-head">
        <div><div class="ivc-agenda-kicker"><i class="bx bx-calendar-check"></i> GESTIÓN DE CITAS</div>
            <h1>Agenda de atención</h1><p>Organiza las citas y consulta los horarios disponibles de forma sencilla.</p>
        </div>
        <div class="ivc-agenda-head-actions">
            <span class="ivc-agenda-today"><i class="bx bx-time-five"></i> Horario de Tijuana</span>
            <button type="button" class="ivc-agenda-add" id="ivcAbrirNuevaCita"><i class="bx bx-plus"></i> Agendar cita</button>
        </div>
    </header>
    <section class="ivc-agenda-insights" aria-label="Resumen de citas">
        <article><div class="ivc-agenda-insight-icon blue"><i class="bx bx-calendar-event"></i></div><div><small>Citas visibles</small><strong id="ivcCitasVisibles">—</strong></div></article>
        <article><div class="ivc-agenda-insight-icon green"><i class="bx bx-badge-check"></i></div><div><small>Confirmadas</small><strong id="ivcCitasConfirmadas">—</strong></div></article>
        <article><div class="ivc-agenda-insight-icon amber"><i class="bx bx-envelope"></i></div><div><small>Por confirmar</small><strong id="ivcCitasPendientes">—</strong></div></article>
    </section>
    <section class="ivc-agenda-card">
        <div class="ivc-agenda-card-head"><div><h2>Calendario de citas</h2><p>Selecciona un día para agregar una cita o elige una existente para ver sus detalles.</p></div>
            <div class="ivc-agenda-legend"><span><i class="dot confirmed"></i> Confirmadas</span><span><i class="dot pending"></i> Pendientes</span></div>
        </div>
        <div id="calendar" aria-label="Calendario administrativo de citas"></div>
    </section>
    <!-- Detalle de cita: mismo formulario e IDs, presentación renovada -->
    <div class="modal fade ivc-agenda-modal" id="modalAgenda" tabindex="-1"
         aria-labelledby="tituloModalAgenda" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content ivc-cita-modal-shell">
                <div class="modal-header ivc-cita-modal-head">
                    <div class="ivc-cita-modal-icon" aria-hidden="true"><i class="bx bx-calendar-check"></i></div>
                    <div class="ivc-cita-modal-heading">
                        <span class="ivc-agenda-modal-kicker">AGENDA DE ATENCIÓN</span>
                        <h2 class="modal-title" id="tituloModalAgenda">Detalle de la cita</h2>
                        <p class="ivc-cita-modal-desc">Consulta la información de esta reserva.</p>
                    </div>
                    <button type="button" class="btn-close ivc-cita-modal-close" data-bs-dismiss="modal" aria-label="Cerrar ventana"></button>
                </div>
                <form id="formularioAgenda" autocomplete="off">
                    <div class="modal-body ivc-cita-modal-body">
                        <div class="ivc-cita-status-row">
                            <div class="ivc-cita-status-heading">
                                <span class="ivc-cita-section-icon" aria-hidden="true"><i class="bx bx-shield-quarter"></i></span>
                                <div><strong>Estado de la cita</strong><small>Confirmación de asistencia</small></div>
                            </div>
                            <span class="ivc-agenda-modal-status is-pending" id="ivcAgendaStatus" role="status" aria-live="polite">Pendiente de confirmar</span>
                        </div>
                        <div class="ivc-cita-section-title"><i class="bx bx-user-circle" aria-hidden="true"></i> Datos del paciente</div>
                        <div class="ivc-cita-detail-grid ivc-cita-detail-grid-person">
                            <div class="ivc-agenda-field ivc-cita-field ivc-cita-field-wide">
                                <label for="nombrePaciente">Nombre del paciente</label>
                                <div class="ivc-cita-input-icon"><i class="bx bx-user" aria-hidden="true"></i>
                                    <input type="text" class="form-control" id="nombrePaciente" readonly aria-readonly="true">
                                </div>
                            </div>
                            <div class="ivc-agenda-field ivc-cita-field">
                                <label for="id_Cita">Folio de cita</label>
                                <div class="ivc-cita-input-icon"><i class="bx bx-hash" aria-hidden="true"></i>
                                    <input type="text" class="form-control" id="id_Cita" readonly aria-readonly="true">
                                </div>
                            </div>
                        </div>
                        <div class="ivc-cita-section-title"><i class="bx bx-time-five" aria-hidden="true"></i> Fecha y horario</div>
                        <div class="ivc-cita-detail-grid">
                            <div class="ivc-agenda-field ivc-cita-field">
                                <label for="fechaCitaAgenda">Fecha de la cita</label>
                                <div class="ivc-cita-input-icon"><i class="bx bx-calendar" aria-hidden="true"></i>
                                    <input type="date" class="form-control" id="fechaCitaAgenda" readonly aria-readonly="true">
                                </div>
                            </div>
                            <div class="ivc-agenda-field ivc-cita-field">
                                <label for="horaCitaAgenda">Horario de atención</label>
                                <div class="ivc-cita-input-icon"><i class="bx bx-time" aria-hidden="true"></i>
                                    <input type="text" class="form-control" id="horaCitaAgenda" readonly aria-readonly="true">
                                </div>
                            </div>
                        </div>
                        <p class="ivc-cita-zone-note"><i class="bx bx-info-circle" aria-hidden="true"></i> Todos los horarios corresponden a Tijuana, Baja California.</p>
                    </div>
                    <div class="modal-footer ivc-cita-modal-actions">
                        <button type="button" class="btn ivc-cita-btn-secondary" data-bs-dismiss="modal"><i class="bx bx-arrow-back" aria-hidden="true"></i> Volver a la agenda</button>
                        <button type="button" class="btn ivc-cita-btn-danger" id="btnEliminarAgenda"><i class="bx bx-x-circle" aria-hidden="true"></i> Cancelar cita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/main.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/modulos/es.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/moment.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/modulos/citas.js?v=492b1"></script>
</body></html>
