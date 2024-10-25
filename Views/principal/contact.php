<?php include_once 'Views/template-principal/header.php'?>

    <!-- Start Content Page -->
    <div class="container-fluid bg-light py-5">
        <div class="col-md-6 m-auto text-center">
            <h1 class="h1">Cantacto</h1>
            <p>
                Envianos un mensaje , nos pondremos en contacto lo mas pronto posible 
            </p>
        </div>
    </div>

    <!-- Start Map -->
    <div id="mapid" style="width: 100%; height: 300px;"></div>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>
 
    <!-- Ena Map -->

    <!-- Start Contact -->
    <div class="container py-5">
        <div class="row py-5">
            <form class="col-md-9 m-auto" method="post" role="form">
                <div class="row">
                    <div class="form-group col-md-6 mb-3">
                        <label for="inputname">Nombre</label>
                        <input type="text" class="form-control mt-1" id="name" name="name" placeholder="Ingrese su nombre">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label for="inputemail">Correo</label>
                        <input type="email" class="form-control mt-1" id="email" name="email" placeholder="Ingrese su correo electronico ">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="inputsubject">Asunto</label>
                    <input type="text" class="form-control mt-1" id="subject" name="subject" placeholder="Asuntoi">
                </div>
                <div class="mb-3">
                    <label for="inputmessage">Mensaje</label>
                    <textarea class="form-control mt-1" id="message" name="message" placeholder="Mensaje" rows="8"></textarea>
                </div>
                <div class="row">
                    <div class="col text-end mt-2">
                        <button type="submit" class="btn btn-success btn-lg px-3">Enviar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- End Contact -->

    <?php include_once 'Views/template-principal/footer.php'?>


</body>

</html>