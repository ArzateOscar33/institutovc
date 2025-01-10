</div>
        </div>
        <div class="modal fade" id="modalAgendarNuevaCita" tab-index="-1" aria-labelledby="my-example-modal" aria-hidden="true" >
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-info">
                            <h5 class="modal-title" id="tituloModalAgendarNuevaCita">Agendar Nueva Cita</h5>
                            <button type="button"   class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">

                                    <form class="col-md-12 m-auto" method="POST" role="form" id="frmAgendarCita">
                                        <div class="row">
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="fechaNuevaCita" name="fechaNuevaCita" type="date"  >
                                                <label for="" class="form-label">Fecha De La Cita</label>
                                                <div class="row">
                                                    <div class="form-group col-md-6 mb-3">
                                                        <label for="inputname">Apellido Paterno</label>
                                                        <input type="text" class="form-control mt-1" name="iapPaterno" id="iapPaterno" placeholder="Introduzca su Apellido Paterno">
                                                    </div>
                                                    <div class="form-group col-md-6 mb-3">
                                                        <label for="inputname">Apellido Materno</label>
                                                        <input type="text" class="form-control mt-1" name="iapMaterno" id="iapMaterno" placeholder="Introduzca su Apellido Materno">
                                                    </div>
                                                    <div class="form-group col-md-6 mb-3">
                                                        <label for="inputname">Nombre</label>
                                                        <input type="text" class="form-control mt-1" name="iNombre" id="iNombre" placeholder="Introduzca su Nombre">
                                                    </div>
                                                    <div class="form-group col-md-6 mb-3">
                                                        <label for="inputemail">Correo</label>
                                                        <input type="email" class="form-control mt-1" name="iCorreo" id="iCorreo" placeholder="Introduzca su Coreo">
                                                    </div>
                                                    <div class="form-group col-md-6 mb-3">
                                                        <label for="">Telefono</label>
                                                        <input type="text" class="form-control mt-1" name="iTelefono" id="iTelefono" placeholder="Introduzca su Num. Telefono">
                                                    </div>
                                                    <div class="form-group col-md-6 mb-3">
                                                        <label for="fechaCita">Selecciona el Horario de tu Cita</label>


                                                        <select class="form-select" aria-label="Default select example" name="selectHorarios" id="selectHorarios">

                                                            <option value="">Selecciona un horario</option>

                                                        </select>

                                                    </div>
                                                </div>
                                                <div class="form-group col-md-12 mb-3">
                                                    <label for="">Comentarios</label>
                                                    <textarea class="form-control " name="comentarios" id="comentariosAgendarCitaNueva" placeholder="Comentarios u Observaciones"></textarea>

                                                </div>

                                                <button type="submit" id="btnAgendar" name="btnAgendar" class="btn btn-success btn-lg px-3">Agendar Cita</button>

                                    </form>

                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>


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
<script>
  const base_url = '<?php echo BASE_URL; ?>';
</script>
<script type="text/javascript" src="<?php echo BASE_URL . 'assets/DataTables/datatables.min.js'; ?>"></script>

<script src="<?php echo BASE_URL; ?>assets/js/es-ES.js"></script>
