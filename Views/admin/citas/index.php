<?php include_once 'Views/template/header-admin.php'; ?>

<link rel="stylesheet" href="<?php echo BASE_URL . 'assets/css/main.min.css'; ?>">

<div class="card">
    <div class="card-body">
        <div class="container mb-1">

            <div id="calendar"></div>
        </div>
        <div class="modal fade" id="modalAgenda" tabindex="-1" aria-labelledby="Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info">
                        <h5 class="modal-title" id="tituloModalAgenda">Agendar Nueva Cita</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formularioAgenda" autocomplete="off">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">

                                    <form class="col-md-12 m-auto" method="post" role="form" id="frmAgenda">
                                        <div class="row"> 
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="fechaCitaAgenda" type="date" name="fechaCitaAgenda">
                                                <label for="start" class="form-label">Fecha De La Cita</label>
                                                <div class="row">
                                                     
                                                    <div class="form-group col-md-12 mb-3">
                                                        <label for="">Nombre del Paciente</label>
                                                        <input type="text" class="form-control mt-1" name="nombrePaciente" id="nombrePaciente" placeholder="">
                                                    </div>
                                                
                                                    
                                                </div>
                                                <div class="form-group col-md-12 mb-3">
                                                    <label for="">Comentarios</label>
                                                    <textarea class="form-control " name="comentarios" id="comentarios" placeholder="Comentarios u Observaciones"></textarea>

                                                </div>


                                    </form>
 
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-danger" id="btnEliminarAgenda">Eliminar Cita</button>
                            <button type="submit" class="btn btn-primary" id="btnAccionAgenda">Modificar</button>
                            <button type="submit" id="btnAgendarNuevaCita" name="btnAgendarNuevaCita" class="btn btn-success btn-lg px-3">Agendar Nueva Cita</button>
                                         
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="modal fade" id="modalAgendarNuevaCita" tabindex="-1" aria-labelledby="Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info">
                        <h5 class="modal-title" id="tituloModalAgendarNuevaCita">Agendar Nueva Cita</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
           
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">

                                    <form class="col-md-12 m-auto" method="post" role="form" id="frmAgendarCita">
                                        <div class="row"> 
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="fechaNuevaCita" type="date" name="fechaNuevaCita">
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
                                                    <textarea class="form-control " name="comentarios" id="comentarios" placeholder="Comentarios u Observaciones"></textarea>

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
        <?php include_once 'Views/template/footer-admin.php'; ?>


        <script src="<?php echo BASE_URL . 'assets/js/main.min.js'; ?>"></script>
 
        <script src="<?php echo BASE_URL . 'assets/js/modulos/citas.js'; ?>"></script>
        <script src="<?php echo BASE_URL . 'assets/js/modulos/es.js'; ?>"></script>
        <script src="<?php echo BASE_URL . 'assets/js/moment.js'; ?>"></script>
        </body>

        </html>