<?php include_once 'Views/template-principal/header.php';
?>

<!-- Start Content -->
<div class="container py-5">
   <div class="row">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-body">   
                        <div class="table-responsive" >
                        <table class="table table-bordered table-striped table-hover align-middle" id="tableListaProductos">
                         <thead>
                <tr>
                    <th  >#</th>
                    <th  >Producto</th>
                    <th  >Precio</th>
                    <th  >Cantidad</th>
                    <th  >Subtotal</th> 
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
       </div>
    </div>
    <div class="card-footer text-end">
        <h3 id="totalProducto"></h3>
    </div>
    </div>
    
    </div>


    <div class="col-md-4 ">
        <div class="card shadow-lg"> 
            <div class="card-body text-center"> 
                <img
                    src="<?php echo BASE_URL . 'assets/img/banner_img_004.png';?>"
                    class="img-thumbnail rounded-circle"
                    alt=""
                    width="150"
                />
                <hr>
                <p>Karla Castillo</p>
                <div class="accordion" id="accordionExample">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        PayPal
      </button>
    </h2>
    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">
      <div class="accordion-body">
       
    </div>
    </div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
        Otros Metodos de Pago
      </button>
    </h2>
    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
      <div class="accordion-body">
        </div>
    </div>
  </div>
</div>
            </div>
        </div>
        
     
</div> 
   </div>
   

</div>
</div>
<!-- End Content -->




<?php include_once 'Views/template-principal/footer.php' ?>
<script src="<?php echo BASE_URL .'assets/js/clientes.js';?>"></script>
</body>

</html>
