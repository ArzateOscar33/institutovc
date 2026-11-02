<?php
/**
 * Instituto Visual California · Reservación de citas V6
 * La navegación y el pie de página se conservan de la versión V3.
 * No cambia el controlador, modelo, base de datos ni endpoints.
 */
include_once 'Views/template/header.php';
$agendaIcon = static function (string $name, int $size = 22): string {
    $paths = [
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'check' => '<path d="m5 12 4.4 4.4L19 7"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'back' => '<path d="m15 5-7 7 7 7"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.8"/>',
        'pin' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'shield' => '<path d="M12 2 4 6v6c0 5 3.5 8.1 8 10 4.5-1.9 8-5 8-10V6l-8-4Z"/><path d="m8.5 12 2.4 2.5 4.8-5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 7 9-7"/>',
        'phone' => '<path d="M7 3h3l1 4-2 2c1 2 3 4 5 5l2-2 4 1v3c0 2-2 4-4 4C9 20 4 14 4 7c0-2 1-4 3-4Z"/>',
        'spark' => '<path d="m12 2 2.4 7.6L22 12l-7.6 2.4L12 22l-2.4-7.6L2 12l7.6-2.4L12 2Z"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>',
    ];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['check']) . '</svg>';
};
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-agendar.css?v=6.0.0">
<main class="ivc-appointment" id="ivc-appointment">
    <!-- Portada breve y enfocada en una sola acción -->
    <section class="ivc-appt-hero" aria-labelledby="ivc-appt-title">
        <div class="container ivc-appt-hero-grid">
            <div class="ivc-appt-hero-copy ivc-appt-reveal">
                <span class="ivc-appt-eyebrow"><span class="ivc-appt-kicker-dot" aria-hidden="true"></span> TU VISITA COMIENZA AQUÍ</span>
                <h1 id="ivc-appt-title">Una mejor visión<br>comienza con <em>una cita.</em></h1>
                <p>Elige el día y horario que mejor se acomoden a ti. Queremos que agendar sea tan sencillo como cuidar tu mirada.</p>
                <a class="ivc-appt-hero-link" href="#ivc-appt-booking">Comenzar mi reserva <?php echo $agendaIcon('arrow', 18); ?></a>
            </div>
            <div class="ivc-appt-hero-art ivc-appt-reveal" aria-hidden="true">
                <div class="ivc-appt-orb ivc-appt-orb-one"></div>
                <div class="ivc-appt-orb ivc-appt-orb-two"></div>
                <div class="ivc-appt-illustration">
                    <span class="ivc-appt-art-label">INSTITUTO VISUAL CALIFORNIA</span>
                    <div class="ivc-appt-glasses-art">
                        <svg viewBox="0 0 480 210" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M48 58C101 40 160 44 207 59M271 59C318 44 379 40 430 58" stroke="#8FC9EA" stroke-width="7" stroke-linecap="round"/>
                          <path d="M193 95C217 84 260 84 284 95" stroke="#153B5B" stroke-width="10" stroke-linecap="round"/>
                          <path d="M48 59 17 66M430 59l33 7" stroke="#153B5B" stroke-width="11" stroke-linecap="round"/>
                          <path d="M51 63c34-12 106-11 141 1 12 5 15 14 14 26l-7 52c-3 30-24 44-66 44-38 0-67-19-76-52l-12-43c-4-14-2-23 6-28Z" fill="#ffffff" fill-opacity=".6" stroke="#153B5B" stroke-width="11"/>
                          <path d="M288 64c35-12 107-13 141-1 8 5 10 14 6 28l-12 43c-9 33-38 52-76 52-42 0-63-14-66-44l-7-52c-1-12 2-21 14-26Z" fill="#ffffff" fill-opacity=".6" stroke="#153B5B" stroke-width="11"/>
                          <path d="m78 88 65-18M306 88l65-18" stroke="#8FC9EA" stroke-width="7" stroke-linecap="round" opacity=".75"/>
                        </svg>
                    </div>
                    <div class="ivc-appt-art-caption"><span class="ivc-appt-art-star">✳</span> TU MIRADA, NUESTRA PRIORIDAD</div>
                </div>
                <div class="ivc-appt-floating-badge"><?php echo $agendaIcon('calendar', 19); ?><span><strong>Tu tiempo importa</strong><small>Reserva de manera sencilla</small></span></div>
            </div>
        </div>
    </section>

    <section class="ivc-appt-content" id="ivc-appt-booking" aria-labelledby="ivc-appt-booking-title">
        <div class="container">
            <div class="ivc-appt-section-label ivc-appt-reveal"><span>RESERVAS EN LÍNEA</span><span class="ivc-appt-section-line"></span><span>01 — 02</span></div>
            <div class="ivc-appt-section-heading ivc-appt-reveal">
                <div><h2 id="ivc-appt-booking-title">Agenda a tu <em>ritmo.</em></h2><p>Solo necesitamos unos datos para preparar tu visita.</p></div>
                <span class="ivc-appt-safe"><span><?php echo $agendaIcon('shield', 16); ?></span> Tus datos se utilizan para gestionar tu cita</span>
            </div>

            <div class="ivc-appt-layout">
                <div class="ivc-appt-panel">
                    <div class="ivc-appt-progress" aria-label="Progreso de la reserva">
                        <div class="ivc-appt-progress-item is-current" id="ivc-appt-progress-one" aria-current="step"><span class="ivc-appt-progress-number">01</span><span><strong>Fecha y horario</strong><small>Elige cuándo venir</small></span></div>
                        <span class="ivc-appt-progress-connector" aria-hidden="true"></span>
                        <div class="ivc-appt-progress-item" id="ivc-appt-progress-two"><span class="ivc-appt-progress-number">02</span><span><strong>Tus datos</strong><small>Confirma la reserva</small></span></div>
                    </div>

                    <?php if (!empty($data['pedidoValoracionError'])): ?>
                        <div class="alert alert-warning">No se pudo verificar el pedido para vincular la cita. Inicia sesión con la cuenta que compró los lentes o solicita ayuda a la óptica. Si continúas, esta cita no se asociará automáticamente.</div>
                        <?php endif; ?>
                    <?php if (!empty($data['pedidoValoracion'])): ?>
                        <div class="alert alert-info" role="status"><strong>Valoración del pedido #<?php echo (int)$data['pedidoValoracion']['id']; ?></strong><br>Reserva tu cita con el correo de tu cuenta. Se asociará automáticamente al pedido.</div>
                        <?php endif; ?>
                    <form id="frmAgendar" novalidate method="post" autocomplete="on" data-agendar-url="<?php echo BASE_URL; ?>principal/registrarCita" data-horarios-url="<?php echo BASE_URL; ?>principal/getHorariosDisponibles" data-correo-url="<?php echo BASE_URL; ?>principal/enviarCorreoAgenda" data-inicio-url="<?php echo BASE_URL; ?>">
                        <?php if (!empty($data['pedidoValoracion'])): ?>
                        <input type="hidden" name="id_pedido" value="<?php echo (int)$data['pedidoValoracion']['id']; ?>">
                        <input type="hidden" name="csrf_pedido" value="<?php echo htmlspecialchars((string)$data['pedidoValoracion']['csrf'],ENT_QUOTES,'UTF-8'); ?>">
                        <?php endif; ?>
                        <section class="ivc-appt-form-step" id="ivc-appt-step-one" aria-labelledby="ivc-appt-step-one-title">
                            <div class="ivc-appt-form-header"><span class="ivc-appt-form-icon"><?php echo $agendaIcon('calendar', 24); ?></span><div><span class="ivc-appt-step-caption">PASO 01</span><h3 id="ivc-appt-step-one-title">¿Cuándo te gustaría venir?</h3><p>Primero selecciona un día. Te mostraremos los horarios disponibles.</p></div></div>
                            <div class="ivc-appt-field"><label for="fechaCita">Selecciona una fecha <span aria-hidden="true">*</span></label>
                                <div class="ivc-appt-quick-dates" id="ivc-appt-quick-dates" role="group" aria-label="Fechas próximas disponibles"></div>
                                <div class="ivc-appt-date-line"><span>¿Necesitas otro día?</span><input class="ivc-appt-date-input" type="date" name="fechaCita" id="fechaCita" required aria-describedby="ivc-appt-date-help ivc-appt-date-error"></div>
                                <p class="ivc-appt-field-hint" id="ivc-appt-date-help">Puedes agendar a partir de mañana. Los domingos no están disponibles.</p>
                                <p class="ivc-appt-error" id="ivc-appt-date-error" role="alert" hidden></p>
                            </div>
                            <div class="ivc-appt-divider"></div>
                            <div class="ivc-appt-field"><div class="ivc-appt-label-row"><label id="ivc-appt-time-label" for="selectHorarios">Horarios disponibles <span aria-hidden="true">*</span></label><span id="ivc-appt-selected-date" class="ivc-appt-date-tag">Selecciona una fecha</span></div>
                                <select id="selectHorarios" name="selectHorarios" required class="ivc-appt-native-select" aria-describedby="ivc-appt-time-status ivc-appt-time-error"><option value="">Selecciona un horario</option></select>
                                <div class="ivc-appt-times" id="ivc-appt-times" role="group" aria-label="Selecciona un horario">
                                    <div class="ivc-appt-empty-hours"><?php echo $agendaIcon('clock', 26); ?><span>Selecciona un día para consultar sus horarios.</span></div>
                                </div>
                                <p id="ivc-appt-time-status" class="ivc-appt-field-hint" role="status" aria-live="polite">Los horarios se consultan directamente en nuestro sistema.</p>
                                <p class="ivc-appt-error" id="ivc-appt-time-error" role="alert" hidden></p>
                            </div>
                            <button class="ivc-appt-primary ivc-appt-next" id="ivc-appt-next" type="button">Continuar con mis datos <?php echo $agendaIcon('arrow', 20); ?></button>
                        </section>

                        <section class="ivc-appt-form-step" id="ivc-appt-step-two" aria-labelledby="ivc-appt-step-two-title" hidden>
                            <div class="ivc-appt-form-header"><span class="ivc-appt-form-icon"><?php echo $agendaIcon('check', 25); ?></span><div><span class="ivc-appt-step-caption">PASO 02</span><h3 id="ivc-appt-step-two-title">Ahora, cuéntanos quién eres.</h3><p>Con esta información podremos registrar tu visita y enviarte la confirmación.</p></div></div>
                            <div class="ivc-appt-person-grid">
                                <div class="ivc-appt-field ivc-appt-field-full"><label for="iNombre">Nombre(s) <span aria-hidden="true">*</span></label><input type="text" class="ivc-appt-input" name="iNombre" id="iNombre" maxlength="100" autocomplete="given-name" placeholder="Ej. Andrea" required aria-describedby="ivc-appt-error-iNombre"><p id="ivc-appt-error-iNombre" class="ivc-appt-error" role="alert" hidden></p></div>
                                <div class="ivc-appt-field"><label for="iapPaterno">Apellido paterno <span aria-hidden="true">*</span></label><input type="text" class="ivc-appt-input" name="iapPaterno" id="iapPaterno" maxlength="50" autocomplete="family-name" placeholder="Ej. Martínez" required aria-describedby="ivc-appt-error-iapPaterno"><p id="ivc-appt-error-iapPaterno" class="ivc-appt-error" role="alert" hidden></p></div>
                                <div class="ivc-appt-field"><label for="iapMaterno">Apellido materno <span aria-hidden="true">*</span></label><input type="text" class="ivc-appt-input" name="iapMaterno" id="iapMaterno" maxlength="50" placeholder="Ej. López" required aria-describedby="ivc-appt-error-iapMaterno"><p id="ivc-appt-error-iapMaterno" class="ivc-appt-error" role="alert" hidden></p></div>
                                <div class="ivc-appt-field"><label for="iCorreo">Correo electrónico <span aria-hidden="true">*</span></label><input type="email" class="ivc-appt-input" name="iCorreo" id="iCorreo" maxlength="100" autocomplete="email" placeholder="correo@ejemplo.com" required aria-describedby="ivc-appt-error-iCorreo"><p id="ivc-appt-error-iCorreo" class="ivc-appt-error" role="alert" hidden></p></div>
                                <div class="ivc-appt-field"><label for="iTelefono">Teléfono (10 dígitos) <span aria-hidden="true">*</span></label><input type="tel" class="ivc-appt-input" name="iTelefono" id="iTelefono" maxlength="10" inputmode="numeric" autocomplete="tel-national" placeholder="6641234567" required aria-describedby="ivc-appt-error-iTelefono"><p id="ivc-appt-error-iTelefono" class="ivc-appt-error" role="alert" hidden></p></div>
                            </div>
                            <div class="ivc-appt-notice"><?php echo $agendaIcon('mail', 20); ?><span>Después de registrar tu cita recibirás un correo con un enlace para <strong>confirmarla</strong>. Revisa también tu carpeta de correo no deseado.</span></div>
                            <p class="ivc-appt-error" id="ivc-appt-submit-error" role="alert" hidden></p>
                            <div class="ivc-appt-form-actions"><button type="button" class="ivc-appt-back" id="ivc-appt-back"><?php echo $agendaIcon('back', 18); ?> Volver al horario</button><button type="submit" class="ivc-appt-primary" id="btnAgendar">Registrar mi cita <?php echo $agendaIcon('arrow', 19); ?></button></div>
                        </section>
                    </form>
                    <section id="ivc-appt-result" class="ivc-appt-result" aria-live="polite" tabindex="-1" hidden>
                        <div class="ivc-appt-result-symbol"><?php echo $agendaIcon('check', 32); ?></div>
                        <span class="ivc-appt-step-caption">SOLICITUD REGISTRADA</span>
                        <h3 id="ivc-appt-result-title">Tu cita quedó registrada.</h3>
                        <p id="ivc-appt-result-message">Te enviamos los siguientes pasos por correo electrónico.</p>
                        <div id="ivc-appt-mail-warning" class="ivc-appt-mail-warning" hidden><span><?php echo $agendaIcon('alert', 20); ?></span><div><strong>El correo no pudo enviarse.</strong><p>Tu cita ya está registrada, no es necesario hacer otra reserva. Puedes intentar reenviar el correo o comunicarte con la óptica.</p><button type="button" id="ivc-appt-retry-mail">Reintentar envío</button></div></div>
                        <a class="ivc-appt-result-link" href="<?php echo BASE_URL; ?>">Regresar al inicio <?php echo $agendaIcon('arrow', 18); ?></a>
                    </section>
                </div>

                <aside class="ivc-appt-sidebar" aria-labelledby="ivc-appt-sidebar-title">
                    <div class="ivc-appt-summary-card">
                        <div class="ivc-appt-summary-icon"><?php echo $agendaIcon('eye', 24); ?></div>
                        <span class="ivc-appt-eyebrow">TU PRÓXIMA VISITA</span>
                        <h3 id="ivc-appt-sidebar-title">Tu cita, <em>de un vistazo.</em></h3>
                        <p>Revisa aquí la información de tu reserva.</p>
                        <dl class="ivc-appt-summary-list"><div><dt><?php echo $agendaIcon('calendar', 18); ?> Fecha</dt><dd id="ivc-appt-summary-date">Por seleccionar</dd></div><div><dt><?php echo $agendaIcon('clock', 18); ?> Horario</dt><dd id="ivc-appt-summary-time">Por seleccionar</dd></div><div><dt><?php echo $agendaIcon('pin', 18); ?> Ubicación</dt><dd>Instituto Visual California, Tijuana</dd></div></dl>
                        <div class="ivc-appt-summary-footnote"><span><?php echo $agendaIcon('shield', 16); ?></span> Tu reserva se confirma desde el enlace que recibirás por correo.</div>
                    </div>
                    <div class="ivc-appt-help-card"><span class="ivc-appt-help-icon"><?php echo $agendaIcon('phone', 21); ?></span><div><strong>¿Necesitas ayuda?</strong><p>También podemos atender tus dudas por teléfono.</p><a href="tel:+526644913156">(664) 491-3156 <?php echo $agendaIcon('arrow', 17); ?></a></div></div>
                </aside>
            </div>
        </div>
    </section>
    <section class="ivc-appt-closing" aria-label="Información de atención"><div class="container ivc-appt-closing-grid"><span><?php echo $agendaIcon('eye', 24); ?> ATENCIÓN VISUAL CERCANA</span><span><?php echo $agendaIcon('calendar', 24); ?> HORARIOS ACTUALIZADOS</span><span><?php echo $agendaIcon('mail', 24); ?> CONFIRMACIÓN POR CORREO</span></div></section>
</main>
<script defer src="<?php echo BASE_URL; ?>assets/js/modulos/ivc-agendar.js?v=6.1.0"></script>
<?php include_once 'Views/template/footer.php'; ?>
