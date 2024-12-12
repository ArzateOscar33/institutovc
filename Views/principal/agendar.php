<?php include_once 'Views/template/header.php' ?> 

<!-- Start Content Page -->
<div class="container-fluid bg-light py-5">
    <div class="col-md-6 m-auto text-center">
        <h1 class="h1">Agenda tu Cita </h1>
        <p>
            Descubre la diferencia de un examen visual completo y adaptado a tus necesidades. ¡Reserva ahora!
        </p>
    </div>
</div>

<!-- Start Map -->
<div id="mapid" style="width: 100%;"></div>
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>

<!-- Ena Map -->

<!-- Start Contact -->
<div class="container py-5">
    <div class="row py-5">
        <form class="col-md-9 m-auto" method="post" role="form" id="frmAgendar">
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
                    <input type="text" class="form-control mt-1" name="iNombre"id="iNombre" placeholder="Introduzca su Nombre">
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="inputemail">Correo</label>
                    <input type="email" class="form-control mt-1" name="iCorreo"id="iCorreo" placeholder="Introduzca su Coreo">
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="">Telefono</label>
                    <input type="text" class="form-control mt-1" name="iTelefono"id="iTelefono" placeholder="Introduzca su Num. Telefono">
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="">Selecciona la fecha de tu cita</label>
                    <input type="date" class="form-control mt-1" name="fechaCita" id="fechaCita"  >
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="fechaCita">Selecciona el Horario de tu Cita</label>
                   
                        
                    <select class="form-select" aria-label="Default select example" name="selectHorarios" id="selectHorarios">
                     
                    <option  value="">Selecciona un horario</option>
                     
                    </select>
                    
                </div>

                <div class="row">
                    <div class="col text-end mt-2">
                        <button type="submit" id="btnAgendar" name="btnAgendar" class="btn btn-success btn-lg px-3">Agendar Cita</button>
                    </div>
                </div>
        </form>
    </div>
</div>
</div>
<!-- End Contact -->

<script src="<?php echo BASE_URL . 'assets/js/modulos/agendar.js'; ?>"></script>
<?php include_once 'Views/template/footer.php' ?>
