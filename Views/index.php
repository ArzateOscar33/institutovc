<?php
include_once 'Views/template-principal/header.php';

?>


    <!-- Start Banner Hero -->
    <div id="template-mo-zay-hero-carousel" class="carousel slide" data-bs-ride="carousel">
        <ol class="carousel-indicators">
            <li data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="0" class="active"></li>
            <li data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="1"></li>
            <li data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="2"></li>
        </ol>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <div class="container">
                    <div class="row p-5">
                        <div class="mx-auto col-md-8 col-lg-6 order-lg-last">
                            <img class="img-fluid" style="width:800px; height: 590px;" src="./assets/img/banner_img_004.png" alt="banner_01">
                        </div>
                        <div class="col-lg-6 mb-0 d-flex align-items-center">
                            <div class="text-align-left align-self-center">
                                <h1 class="h1 text-success"><b>¡Tu visión, nuestro compromiso!</b> </h1>
                                <h3 class="h2">Armazones de diseño para todas las edades </h3>
                                <p>
                                En Instituto Visual California, encontrarás las gafas perfectas para ti. ¡Visítanos y descubre nuestra amplia variedad de modelos!
       <br>
                                     <a rel="sponsored" class="text-success" href="#" target="_blank">Agenda tu Cita</a>,
                                 
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="container">
                    <div class="row p-5">
                        <div class="mx-auto col-md-8 col-lg-6 order-lg-last">
                            <img class="img-fluid"  style="width:800px; height: 590px;" src="./assets/img/banner_img_006.jpg" alt="">
                        </div>
                        <div class="col-lg-6 mb-0 d-flex align-items-center">
                            <div class="text-align-left">
                                <h1 class="h1 text-success"><b>Siente la diferencia</b></h1>
                                <h3 class="h2">Armazones que expresan tu personalidad</h3>
                                <p>
                                    En el <strong>Instituto Visual California</strong> creemos que tus anteojos son mucho 
                                    más que una corrección visual. Son una expresión de tu estilo y personalidad. 
                                    ¡Encuentra los tuyos hoy mismo!
                                
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="container">
                    <div class="row p-5">
                        <div class="mx-auto col-md-8 col-lg-6 order-lg-last">
                            <img class="img-fluid"  style="width:800px; height: 590px;"src="./assets/img/banner_img_001.jpg" alt="">
                        </div>
                        <div class="col-lg-6 mb-0 d-flex align-items-center">
                            <div class="text-align-left">
                                <h1 class="h1 text-success"><b>La tecnología al servicio de tu vista</b></h1>
                                <h3 class="h2">Diagnósticos precisos y tratamientos personalizados</h3>
                                <p>
                                Contamos con el equipo más avanzado para ofrecerte una atención de excelencia.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <a class="carousel-control-prev text-decoration-none w-auto ps-3" href="#template-mo-zay-hero-carousel" role="button" data-bs-slide="prev">
            <i class="fas fa-chevron-left"></i>
        </a>
        <a class="carousel-control-next text-decoration-none w-auto pe-3" href="#template-mo-zay-hero-carousel" role="button" data-bs-slide="next">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>
    <!-- End Banner Hero -->


    <!-- Start Categories of The Month -->
    <section class="container py-5">
        <div class="row text-center pt-3">

            <div class="col-lg-6 m-auto">
                <h1 class="h1"><b>Encuentra el estilo perfecto para ti</b></h1>
                <p>
                Explora nuestra selección de las marcas más reconocidas y encuentra los modelos más actuales.

                </p>
            </div>
        </div>
        <div class="row">
        <?php foreach($data['categorias'] as $categoria){ ?>
                

            <div class="col-12 col-md-2 p-5 mt-3">
                <a href="<?php echo BASE_URL . 'principal/categorias/'. $categoria['id'];?>"><img src="<?php echo $categoria['imagen']?>" class="rounded-circle img-fluid border" ></a>
                <h5 class="text-center mt-3 mb-3"><?php echo $categoria['categoria']; ?> </h5>
                <p class="text-center"><a class="btn btn-success">Ir a Tienda</a></p>
            </div>
        
       <?php  }?>
        </div>
    </section>
    <!-- End Categories of The Month -->


    <!-- Start Featured Product -->
    <section class="bg-light">
        <div class="container py-5">
            <div class="row text-center py-3">
                <div class="col-lg-6 m-auto">
                    <h1 class="h1">Nuestros productos más populares</h1>
                    <p>
                    Una selección exclusiva de productos de alta calidad, diseñados para ti.
 
                    </p>
                </div>
            </div>
            <div class="row">
<?php foreach($data['nuevoProducto'] as $producto) {?>
                <div class="col-12 col-md-4 mb-4">
                    <div class="card h-100">
                        <a href="<?php echo BASE_URL . 'principal/detail/'.$producto['id']; ?>">
                            <img src="<?php echo $producto['imagen']?> " class="card-img-top" alt="<?php echo $producto['nombre']?>">
                        </a>
                        <div class="card-body">
                            <ul class="list-unstyled d-flex justify-content-between">
                                <li>
                                </li>
                                <li class="text-muted text-right">$ <?php echo  MONEDA .' ' . $producto['precio'] ?></li>
                            </ul>
                            <a href="<?php echo BASE_URL . 'principal/detail/' .$producto['id']; ?>" class="h2 text-decoration-none text-dark"><?php echo $producto['nombre']?></a>
                            <p class="card-text">
                                <?php echo $producto['descripcion']?>
                            </p>
                         
                        </div>
                    </div>
                </div>
<?php } ?>
            </div>
        </div>
    </section>
    <!-- End Featured Product -->



    <!-- Start Script -->
    <script src="assets/js/jquery-1.11.0.min.js"></script>
    <script src="assets/js/jquery-migrate-1.2.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/templatemo.js"></script>
    <script src="assets/js/custom.js"></script>
    <!-- End Script -->
    <?php 
include_once 'Views/template-principal/footer.php';
?>
 