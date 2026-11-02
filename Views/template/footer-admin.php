</div>
        </div>
        <!-- Formulario de nueva cita (mantiene IDs y nombres de las peticiones AJAX) -->
        <div class="modal fade ivc-agenda-create-modal" id="modalAgendarNuevaCita" tabindex="-1"
             aria-labelledby="tituloModalAgendarNuevaCita" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content ivc-cita-modal-shell">
                    <div class="modal-header ivc-cita-modal-head">
                        <div class="ivc-cita-modal-icon" aria-hidden="true"><i class="bx bx-calendar-plus"></i></div>
                        <div class="ivc-cita-modal-heading">
                            <span class="ivc-agenda-modal-kicker">AGENDA DE ATENCIÓN</span>
                            <h2 class="modal-title" id="tituloModalAgendarNuevaCita">Agendar nueva cita</h2>
                            <p class="ivc-cita-modal-desc">Programa una visita y registra los datos del paciente.</p>
                        </div>
                        <button type="button" class="btn-close ivc-cita-modal-close" data-bs-dismiss="modal" aria-label="Cerrar ventana"></button>
                    </div>
                    <form method="POST" id="frmAgendarCita" autocomplete="off">
                        <div class="modal-body ivc-cita-modal-body">
                            <div class="ivc-cita-section-title"><i class="bx bx-calendar-event" aria-hidden="true"></i> 1. Fecha y horario</div>
                            <div class="ivc-cita-form-grid">
                                <div class="ivc-agenda-field ivc-cita-field">
                                    <label for="fechaNuevaCita">Fecha de la cita</label>
                                    <input type="date" class="form-control" id="fechaNuevaCita" name="fechaNuevaCita">
                                </div>
                                <div class="ivc-agenda-field ivc-cita-field">
                                    <label for="selectHorarios">Horario disponible</label>
                                    <select class="form-select" name="selectHorarios" id="selectHorarios">
                                        <option value="">Selecciona un horario</option>
                                    </select>
                                </div>
                            </div>
                            <p class="ivc-cita-zone-note"><i class="bx bx-info-circle" aria-hidden="true"></i> Los horarios corresponden a Tijuana, Baja California.</p>
                            <div class="ivc-cita-section-title"><i class="bx bx-user" aria-hidden="true"></i> 2. Datos del paciente</div>
                            <div class="ivc-cita-form-grid">
                                <div class="ivc-agenda-field ivc-cita-field">
                                    <label for="iNombre">Nombre</label>
                                    <input class="form-control" type="text" name="iNombre" id="iNombre" placeholder="Nombre del paciente" autocomplete="given-name">
                                </div>
                                <div class="ivc-agenda-field ivc-cita-field">
                                    <label for="iapPaterno">Apellido paterno</label>
                                    <input class="form-control" type="text" name="iapPaterno" id="iapPaterno" placeholder="Apellido paterno" autocomplete="family-name">
                                </div>
                                <div class="ivc-agenda-field ivc-cita-field">
                                    <label for="iapMaterno">Apellido materno</label>
                                    <input class="form-control" type="text" name="iapMaterno" id="iapMaterno" placeholder="Apellido materno">
                                </div>
                                <div class="ivc-agenda-field ivc-cita-field">
                                    <label for="iTelefono">Teléfono</label>
                                    <input class="form-control" type="tel" name="iTelefono" id="iTelefono" placeholder="Número de contacto" autocomplete="tel">
                                </div>
                                <div class="ivc-agenda-field ivc-cita-field ivc-cita-field-full">
                                    <label for="iCorreo">Correo electrónico</label>
                                    <input class="form-control" type="email" name="iCorreo" id="iCorreo" placeholder="paciente@ejemplo.com" autocomplete="email">
                                </div>
                            </div>
                            <div class="ivc-cita-section-title"><i class="bx bx-notepad" aria-hidden="true"></i> 3. Información adicional <span class="ivc-cita-optional">Opcional</span></div>
                            <div class="ivc-agenda-field ivc-cita-field ivc-cita-field-full">
                                <label for="comentariosAgendarCitaNueva">Comentarios u observaciones</label>
                                <textarea class="form-control" name="comentarios" id="comentariosAgendarCitaNueva" rows="3" placeholder="Información que el personal deba conocer"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer ivc-cita-modal-actions">
                            <button type="button" class="btn ivc-cita-btn-secondary" data-bs-dismiss="modal">Volver</button>
                            <button type="submit" id="btnAgendar" name="btnAgendar" class="btn ivc-cita-btn-primary"><i class="bx bx-check-circle" aria-hidden="true"></i> Guardar cita</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--end page wrapper -->
        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        <!--Start Back To Top Button-->
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->
        <footer class="page-footer">
            <p class="mb-0">Instituto Visual California.</p>
        </footer>
    </div>
    <!--end wrapper-->
    <!--start switcher-->
    <div class="switcher-wrapper">
        <div class="switcher-btn"> <i class='bx bx-cog bx-spin'></i>
        </div>
        <div class="switcher-body">
            <div class="d-flex align-items-center">
                <h5 class="mb-0 text-uppercase">Theme Customizer</h5>
                <button type="button" class="btn-close ms-auto close-switcher" aria-label="Close"></button>
            </div>
            <hr/>
            <h6 class="mb-0">Theme Styles</h6>
            <hr/>
            <div class="d-flex align-items-center justify-content-between">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="flexRadioDefault" id="lightmode" checked>
                    <label class="form-check-label" for="lightmode">Light</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="flexRadioDefault" id="darkmode">
                    <label class="form-check-label" for="darkmode">Dark</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="flexRadioDefault" id="semidark">
                    <label class="form-check-label" for="semidark">Semi Dark</label>
                </div>
            </div>
            <hr/>
            <div class="form-check">
                <input class="form-check-input" type="radio" id="minimaltheme" name="flexRadioDefault">
                <label class="form-check-label" for="minimaltheme">Minimal Theme</label>
            </div>
            <hr/>
            <h6 class="mb-0">Header Colors</h6>
            <hr/>
            <div class="header-colors-indigators">
                <div class="row row-cols-auto g-3">
                    <div class="col">
                        <div class="indigator headercolor1" id="headercolor1"></div>
                    </div>
                    <div class="col">
                        <div class="indigator headercolor2" id="headercolor2"></div>
                    </div>
                    <div class="col">
                        <div class="indigator headercolor3" id="headercolor3"></div>
                    </div>
                    <div class="col">
                        <div class="indigator headercolor4" id="headercolor4"></div>
                    </div>
                    <div class="col">
                        <div class="indigator headercolor5" id="headercolor5"></div>
                    </div>
                    <div class="col">
                        <div class="indigator headercolor6" id="headercolor6"></div>
                    </div>
                    <div class="col">
                        <div class="indigator headercolor7" id="headercolor7"></div>
                    </div>
                    <div class="col">
                        <div class="indigator headercolor8" id="headercolor8"></div>
                    </div>
                </div>
            </div>
            <hr/>
            <h6 class="mb-0">Sidebar Colors</h6>
            <hr/>
            <div class="header-colors-indigators">
                <div class="row row-cols-auto g-3">
                    <div class="col">
                        <div class="indigator sidebarcolor1" id="sidebarcolor1"></div>
                    </div>
                    <div class="col">
                        <div class="indigator sidebarcolor2" id="sidebarcolor2"></div>
                    </div>
                    <div class="col">
                        <div class="indigator sidebarcolor3" id="sidebarcolor3"></div>
                    </div>
                    <div class="col">
                        <div class="indigator sidebarcolor4" id="sidebarcolor4"></div>
                    </div>
                    <div class="col">
                        <div class="indigator sidebarcolor5" id="sidebarcolor5"></div>
                    </div>
                    <div class="col">
                        <div class="indigator sidebarcolor6" id="sidebarcolor6"></div>
                    </div>
                    <div class="col">
                        <div class="indigator sidebarcolor7" id="sidebarcolor7"></div>
                    </div>
                    <div class="col">
                        <div class="indigator sidebarcolor8" id="sidebarcolor8"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end switcher-->
    <!-- Bootstrap JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
    <!--plugins-->
    <script src="<?php echo BASE_URL; ?>assets/js/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/plugins/chartjs/js/Chart.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/plugins/chartjs/js/Chart.extension.js"></script>
    
    <!--app JS-->
    <script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>

    <script src="<?php echo BASE_URL; ?>assets/js/dropzone-min.js"></script>

    <script src="<?php echo BASE_URL; ?>assets/js/all.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/sweetalert2.all.min.js"></script>
    <?php
    // Solo para UX: el backend siempre valida el permiso vigente en MySQL.
    $ivcPermisosCliente = [];
    foreach (RolesAcceso::catalogo() as $ivcCodigo => $_ivcEtiqueta) {
        $ivcPermisosCliente[$ivcCodigo] = RolesAcceso::permiso($ivcCodigo);
    }
    $ivcPermisosCliente['roles.gestionar'] = RolesAcceso::permiso('roles.gestionar');
    ?>
    <script>
      window.IVC_PERMISOS_ADMIN = <?php echo json_encode($ivcPermisosCliente, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
      window.IVC_APP_BASE = <?php echo json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/ivc-alertas-permisos-v4942.js?v=4942"></script>
<script>
  const base_url = '<?php echo BASE_URL; ?>';
</script>
<script type="text/javascript" src="<?php echo BASE_URL . 'assets/DataTables/datatables.min.js'; ?>"></script>

<script src="<?php echo BASE_URL; ?>assets/js/es-ES.js"></script>
