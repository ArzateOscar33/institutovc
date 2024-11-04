<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h1 class="modal-title fs-5" id="exampleModalLabel"><i class="fas fa-cart-arrow-down"></i> Carrito</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
       <div class="table-responsive" >
        <table class="table table-bordered table-striped table-hover align-middle" id="tableListaCarrito">
            <thead>
                <tr>
                    <th  >#</th>
                    <th  >Prtoducto</th>
                    <th  >Precio</th>
                    <th  >Cantidad</th>
                    <th  >Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr class="">
                   <td></td>
                   <td></td>
                   <td></td>
                   <td></td>
                   <td></td>
                   <td></td>
                </tr>
            </tbody>
        </table>
       </div>
       <div class="modal-footer">
        <h3 id="totalGeneral"></h3>
       </div>
      </div>
      </div>
      </div>
      </div>
 

 
 <!-- Start Footer -->
 <footer class="bg-dark" id="tempaltemo_footer">
        <div class="container">
            <div class="row">

                <div class="col-md-6 pt-5">
                    <h2 class="h2 text-success border-bottom pb-3 border-light logo">Instituto Visual California</h2>
                    <ul class="list-unstyled text-light footer-link-list">
                        <li>
                            <i class="fas fa-map-marker-alt fa-fw"></i>
                            Otay, Blvd. Alberto Limón Padilla 1-8-8, La Pechuga, Fovissste, 22457 Tijuana, B.C.
                        </li>
                        <li>
                            <i class="fa fa-phone fa-fw"></i>
                            <a class="text-decoration-none" href="#">664-491-31-56</a>
                        </li>
                        <li>
                            <i class="fa fa-envelope fa-fw"></i>
                            <a class="text-decoration-none" href="#">institutovc@info.com</a>
                        </li>
                    </ul>
                </div>


                <div class="col-md-6 pt-5">
                    <h2 class="h2 text-light border-bottom pb-3 border-light">Mas Informacion</h2>
                    <ul class="list-unstyled text-light footer-link-list">
                        <li><a class="text-decoration-none" href="#">Inicio</a></li>
                        <li><a class="text-decoration-none" href="#">Servicios</a></li>
                        <li><a class="text-decoration-none" href="#">Agenda tu Cita</a></li>
                        <li><a class="text-decoration-none" href="#">Tienda de Armazones</a></li>
                        <li><a class="text-decoration-none" href="#">Ubicacion</a></li>
                    </ul>
                </div>

            </div>

            <div class="row text-light mb-4">
                <div class="col-12 mb-3">
                    <div class="w-100 my-3 border-top border-light"></div>
                </div>
                <div class="col-auto me-auto">
                    <ul class="list-inline text-left footer-icons">
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="http://facebook.com/"><i class="fab fa-facebook-f fa-lg fa-fw"></i></a>
                        </li>
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://www.instagram.com/"><i class="fab fa-instagram fa-lg fa-fw"></i></a>
                        </li>
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://twitter.com/"><i class="fab fa-twitter fa-lg fa-fw"></i></a>
                        </li>
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://www.linkedin.com/"><i class="fab fa-linkedin fa-lg fa-fw"></i></a>
                        </li>
                    </ul>
                </div>
                
            </div>
        </div>

        <div class="w-100 bg-black py-3">
            <div class="container">
                <div class="row pt-2">
                    <div class="col-12">
                        <p class="text-left text-light">
                            Copyright &copy; 2024 Instituto Visual California 
                            | Pagina Creada por  <a rel="sponsored" href="#" target="_blank">ArzaTech</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </footer>
    <!-- End Footer -->

    <!-- Start Script -->
    <script src="<?php echo BASE_URL;?>assets/js/jquery-1.11.0.min.js"></script>
    <script src="<?php echo BASE_URL;?>assets/js/jquery-migrate-1.2.1.min.js"></script>
    <script src="<?php echo BASE_URL;?>assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL;?>assets/js/templatemo.js"></script>
    <script src="<?php echo BASE_URL;?>assets/js/custom.js"></script>
    <script src="<?php echo BASE_URL;?>assets/js/sweetalert2.all.min.js"></script>
    <script>
        const base_url='<?php echo BASE_URL;?>';
    </script>
    
    <script src="<?php echo BASE_URL;?>assets/js/modulos/details.js"></script>
    <script src="<?php echo BASE_URL;?>assets/js/carrito.js"></script>
    <!-- End Script -->