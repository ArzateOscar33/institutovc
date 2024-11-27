<?php include_once 'Views/template/header.php'?>

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
            <form class="col-md-9 m-auto" method="post" role="form">
                <div class="row">
                    <div class="form-group col-md-6 mb-3">
                        <label for="inputname">Apellido Paterno</label>
                        <input type="text" class="form-control mt-1" id="name" name="name" placeholder="Name">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label for="inputname">Apellido Materno</label>
                        <input type="text" class="form-control mt-1" id="name" name="name" placeholder="Name">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label for="inputname">Nombre</label>
                        <input type="text" class="form-control mt-1" id="name" name="name" placeholder="Name">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label for="inputemail">Correo</label>
                        <input type="email" class="form-control mt-1" id="email" name="email" placeholder="Email">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label for="inputname">Telefono</label>
                        <input type="text" class="form-control mt-1" id="name" name="name" placeholder="Name">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label for="inputname">Direccion</label>
                        <input type="text" class="form-control mt-1" id="name" name="name" placeholder="Name">
                    </div>
                </div>
                <div class="form-group col-md-6 mb-3">
                        <label for="inputemail">Selecciona la fecha de tu cita</label>
                        <input type="date" class="form-control mt-1" id="fechaCita" name="fechaCita" >
                    </div>
                <div class="row">
                    <div class="col text-end mt-2">
                        <button type="submit" class="btn btn-success btn-lg px-3">Confirmar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- End Contact -->

    <?php include_once 'Views/template/footer.php'?>

 