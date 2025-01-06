<?php include_once 'Views/template/header-admin.php'; ?>

<link rel="stylesheet" href="<?php echo BASE_URL . 'assets/css/main.min.css'; ?>">

<div class="card">
    <div class="card-body">
        <div class="container mb-1">
            <div id="calendar"></div>



            <div class="modal fade" tab-index="-1" id="modalAgenda" aria-hidden="true" >
                <div class="modal-dialog">
                    <div class="modal-content" role="document">
                        <div class="modal-header bg-info">
                            <h5 class="modal-title" id="tituloModalAgenda">Agendar Nueva Cita</h5>
                            <button type="button" id="btnCerrarModalAgenda" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formularioAgenda" autocomplete="off">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">


                                        <div class="row">
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="fechaCitaAgenda" type="date" disabled="true" >
                                                <label for="start" class="form-label">Fecha De La Cita</label>
                                                <div class="row">

                                                    <div class="form-group col-md-12 mb-3">
                                                        <label for="">Nombre del Paciente</label>
                                                        <input disabled="true" type="text" class="form-control mt-1" name="nombrePaciente" id="nombrePaciente" placeholder="">
                                                    </div>

                                                    <div class="form-group col-md-12 mb-3">
                                                        <label for="fechaCitaAgenda">Hora de la Cita</label>
                                                        <input disabled="true" type="text" class="form-control mt-1" name="horaCitaAgenda" id="horaCitaAgenda" placeholder="">
                                                 

                                                    </div>
                                                </div>
                                                </div> 




                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="button" class="btn btn-danger" id="btnEliminarAgenda">Eliminar Cita</button> 
                                       
                                    </div>
                        </form>

                    </div>
                </div>
            </div>







        </div>
    </div>
</div>





<?php include_once 'Views/template/footer-admin.php'; ?>

<script src="<?php echo BASE_URL . 'assets/js/modulos/citas.js'; ?>"></script>
<script src="<?php echo BASE_URL . 'assets/js/main.min.js'; ?>"></script>
 
<script src="<?php echo BASE_URL . 'assets/js/modulos/es.js'; ?>"></script>

<script src="<?php echo BASE_URL . 'assets/js/moment.js'; ?>"></script>

</body>

</html>