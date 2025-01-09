<?php include_once 'Views/template/header.php' ?>
<style>
        .card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .icon-box {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px;
            font-size: 24px;
            color: #fff;
        }
        .mission-icon {
            background-color: #f39c12;
        }
        .vision-icon {
            background-color: #27ae60;
        }
        .values-icon {
            background-color: #2980b9;
        }
    </style>
<!-- Start Section -->
<section class="container py-5">
    <div class="row text-center pt-5 pb-3">
        <div class="col-lg-6 m-auto">
            <h1 class="h1"><b>Sobre Nosotros </b></h1>
            <p>En Instituto Visual California, estamos a la vanguardia de la tecnología óptica. Ofrecemos las últimas novedades en lentes y tratamientos para mejorar tu visión y tu calidad de vida.

Pero no solo nos preocupamos por tu salud visual. También estamos comprometidos con nuestra comunidad. A través de diversas iniciativas, buscamos promover la importancia de la salud ocular y garantizar el acceso a servicios ópticos de calidad para todos.

            </p>
             
        </div>

    </div>

</section>
<!-- End Section -->

<!-- Start Brands -->
<section class="bg-light py-5">
    <div class="container my-4">
        <div class="row text-center py-3">
            <div class="col-lg-12 m-auto">
                <h1 class="h1">Mision, Vision y Valores</h1>
                <div class="container py-5">
        <div class="row g-4">
            <!-- Mission Card -->
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <div class="icon-box mission-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h4 class="card-title fw-bold text-warning">MISION</h4> 
                    <p class="card-text">
                    Proveer servicios ópticos de alta calidad, ofreciendo una atención
personalizada y profesional que mejore la salud visual de nuestros
clientes. Nos comprometemos a ofrecer una amplia gama de productos
ópticos, incluyendo lentes y monturas de la mejor calidad, acompañados
de un servicio excepcional que supere las expectativas de nuestros
clientes.
                    </p>
                </div>
            </div>
            <!-- Vision Card -->
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <div class="icon-box vision-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h4 class="card-title fw-bold text-success">VISION</h4> 
                    <p class="card-text">Ser líderes en el sector óptico, reconocidos por nuestra excelencia en
el cuidado visual y por la innovación en nuestros servicios y productos.
Aspiramos a expandir nuestra presencia a nivel regional, brindando
soluciones visuales de vanguardia y contribuyendo al bienestar visual
de la comunidad.</p>
                </div>
            </div>
            <!-- Values Card -->
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <div class="icon-box values-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h4 class="card-title fw-bold text-primary">VALORES</h4> 
                    <p class="card-text"><ul>
      <li><strong>Excelencia:</strong> Buscamos siempre la perfección en cada detalle, desde la atención al cliente hasta la calidad de nuestros productos.</li>
      <li><strong>Confianza:</strong> Construimos relaciones sólidas y duraderas basadas en la transparencia y la honestidad con nuestros clientes.</li>
      <li><strong>Profesionalismo:</strong> Contamos con un equipo altamente capacitado y actualizado en las últimas tendencias y tecnologías del sector óptico.</li>
      </ul> </p>
                </div>
            </div>
        </div>
        </div>
    </div>
</section>
<!--End Brands-->
<!-- Start Section -->
<section class="container py-5">
    <div class="row text-center pt-5 pb-3">
        <div class="col-lg-6 m-auto">
            <h1 class="h1"><b>Ubicanos en el Mapa </b></h1>
            <p>
                Ubicanos en el mapa de una manera rapida y sencilla
            </p>
        </div>
<!-- Map Section -->
<div class="row mt-5">
            <div class="col-12"> 
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                    <iframe 
    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3362.3352500207823!2d-116.94746357484243!3d32.53261462797978!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80d9471dc77f80dd%3A0x77e78ae8607ee6d!2sInstituto%20Visual%20California!5e0!3m2!1ses-419!2smx!4v1695756017882!5m2!1ses-419!2smx" 
    width="100%" 
    height="450" 
    style="border:0;" 
    allowfullscreen="" 
    loading="lazy" 
    referrerpolicy="no-referrer-when-downgrade">
</iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

</section>
<!-- End Section -->
<?php include_once 'Views/template/footer.php' ?>

<!-- Start Script -->
<script src="assets/js/jquery-1.11.0.min.js"></script>
<script src="assets/js/jquery-migrate-1.2.1.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/templatemo.js"></script>
<script src="assets/js/custom.js"></script>
<!-- End Script -->