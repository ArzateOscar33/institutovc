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
       <div class="d-flex justify-content-around mb-3">
        <h3 id="totalGeneral"></h3>
        <?php if (!empty($_SESSION['correoCliente'])) { ?>
         <a class="btn btn-outline-primary " href="<?php echo BASE_URL.'clientes';?>">Procesar Pedido</a>
        <?php } else{ ?>
        <a class="btn btn-outline-primary " href="#" onclick="abrirModalLogin();"> Login</a>

       <?php }?>
       </div>
      </div>
      </div>
      </div>
      </div>
 
<!--Login directo -->
<div class="modal fade" id="modalLogin" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog  ">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h1 class="modal-title fs-5" id="titleLogin"> Iniciar Sesión</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body m-3">
        <form action="" method="get" class="mb-3">
            <div class="text-center">
            <img class="img-thumbnail rounded-circle" src="<?php echo BASE_URL .'assets/img/logo.jpg';?>" alt="" width="100">
            </div>
            <div class="row">
            <div class="col-md-12" id="frmLogin">
            <div class="form-group mb-3">
                <label for="correoLogin" class="form-label"> <i class="fas fa-envelope"></i>Correo</label>
                <input
                    type="text"
                    name="correoLogin"
                    id="correoLogin"
                    class="form-control"
                    placeholder="Correo Electronico"
                />
                <label for="claveLogin" class="form-label"><i class="fas fa-key"></i>Contraseña</label>
                <input
                    type="text"
                    name="claveLogin"
                    id="claveLogin"
                    class="form-control"
                    placeholder="Contraseña"
                />
            <a href="#" id="btnRegister">¿Todavia no tienes una Cuenta?</a>
            <div class="float-end">
       <button class="btn btn-primary float-end btn-lg" type="button" id="login">Login </button>
        </div>
            </div>
            
     


            </div>
                   <!--formulario de registro-->
                   <div class="col-md-12 d-none" id="frmRegister">
            <div class="form-group mb-3">
                <label for="apellidoPaterno" class="form-label"> <i class="fas fa-list"></i>Apellido Paterno</label>
                <input
                    type="text"
                    name="nombreCliente"
                    id="apellidoPaternoRegistro"
                    class="form-control"
                    placeholder="Apellido Paterno"
                />
                <label for="apellidoMaternoRegistro" class="form-label"> <i class="fas fa-list"></i>Apellido Materno</label>
                <input
                    type="text"
                    name="apellidoMaternoRegistro"
                    id="apellidoMaternoRegistro"
                    class="form-control"
                    placeholder="Apellido Materno"
                />
                <label for="nombreCliente" class="form-label"> <i class="fas fa-list"></i>Nombre</label>
                <input
                    type="text"
                    name="nombreCliente"
                    id="nombreRegistro"
                    class="form-control"
                    placeholder="Nombre Completo"
                />
                <label for="correoRegistro" class="form-label"> <i class="fas fa-envelope"></i>Correo</label>
                <input
                    type="text"
                    name="correoRegistro"
                    id="correoRegistro"
                    class="form-control"
                    placeholder="Correo Electronico"
                />
                <label for="claveRegistro" class="form-label"><i class="fas fa-key"></i>Contraseña</label>
                <input
                    type="text"
                    name="claveRegistro"
                    id="claveRegistro"
                    class="form-control"
                    placeholder="Contraseña"
                />
            </div>
            <a href="#" id="btnLogin">Ya tienes una Cuenta</a>
            <div class="float-end">
       <button class="btn btn-primary btn-lg float-end" type="button" id="registrarse">Registrarse </button>
        </div>
            </div>
           
        </form>
      


       </div>

      </div>
      </div>
      </div>
</div>
 <!-- Start Footer -->
 <footer class="bg-dark" id="tempaltemo_footer" >
        <div class="container pt-4">
            <div class="row">

                <div class="col-md-6 pt-5" >
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
 
            <div class="row text-light mb-4 pt-5">
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
                        
                    </ul>
                </div>
                
            </div>
            
        </div>
 
        <div class="w-100 bg-black py-3 pt-4">
            <div class="container">
                <div class="row pt-4">
                    <div class="col-12 mb-4">
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
    <script src="<?php echo BASE_URL;?>assets/js/login.js"></script>
    <!-- End Script -->