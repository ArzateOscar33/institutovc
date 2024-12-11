<?php include_once 'Views/template/header.php';
?>

    <!-- Start Content -->
    <div class="container py-5">
        <div class="row">

         

            <div class="col-lg-12">
                 
                <div class="row">
                    <?php foreach ($data['productos'] as  $producto){ ?>
                    <div class="col-md-4">
                        <div class="card mb-4 product-wap rounded-0">
                            <div class="card rounded-0">
                                <img class="card-img rounded-0 img-fluid" src="<?php echo $producto['imagen']; ?> ">
                                <div class="card-img-overlay rounded-0 product-overlay d-flex align-items-center justify-content-center">
                                    <ul class="list-unstyled">
                                        <li><a class="btn btn-success text-white btnAddDeseo" href="#" prod="<?php echo $producto['id']; ?>"><i class="fas fa-heart"></i></a></li>
                                        <li><a class="btn btn-success text-white mt-2" href="<?php echo BASE_URL .'principal/detail/'.$producto['id']?>"><i class="far fa-eye"></i></a></li>
                                        <li><a class="btn btn-success text-white mt-2 btnAddCarrito" href="#" prod="<?php echo $producto['id']; ?>"><i class="fas fa-cart-plus"></i></a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <a href="<?php echo BASE_URL .'principal/detail/'.$relacion['id']?>" class="h3 text-decoration-none"><?php echo $producto['nombre'];?></a>
                                <ul class="w-100 list-unstyled d-flex justify-content-between mb-0">
                                    
                                    <li class="pt-2">
                                        <span class="product-color-dot color-dot-red float-left rounded-circle ml-1"></span>
                                        <span class="product-color-dot color-dot-blue float-left rounded-circle ml-1"></span>
                                        <span class="product-color-dot color-dot-black float-left rounded-circle ml-1"></span>
                                        <span class="product-color-dot color-dot-light float-left rounded-circle ml-1"></span>
                                        <span class="product-color-dot color-dot-green float-left rounded-circle ml-1"></span>
                                    </li>
                                </ul>
                                <ul class="list-unstyled d-flex justify-content-center mb-1">
                                    
                                    <li>
                                    <i class="text-warning fa fa-star"></i>
                                    <i class="text-warning fa fa-star"></i>
                                    <i class="text-warning fa fa-star"></i>
                                    <i class="text-warning fa fa-star"></i>
                                    <i class="text-muted fa fa-star"></i>
                                </li>
                                    
                                </ul>
                                <p class="text-center mb-0"><?php echo MONEDA .' '.$producto['precio'];?></p>
                            </div>
                        </div>
                    </div>
                    <?php }?>
                </div>
                <div div="row">
                    <ul class="pagination pagination-lg justify-content-end">
                        <?php
                        $anterior=$data['pagina'] -1;
                        $siguiente =$data['pagina']+1;
                        $url= BASE_URL .'principal/shop/';
                        if($data['pagina']  > 1){
                            echo '<li class="page-item  ">
                            <a class="page-link active rounded-0 mr-3 shadow-sm border-top-0 border-left-0 text-white" href="'.$url.$anterior.'" tabindex="-1">Anterior</a>
                        </li>';
                        }
                        if($data['total']>=$siguiente){
                            echo '<li class="page-item">
                            <a class="page-link active rounded-0 mr-3 shadow-sm border-top-0 border-left-0 text-white" href="'.$url.$siguiente.'">Siguiente</a>
                        </li>';

                        } ?> 
                    </ul>
                </div>
            </div>

        </div>
    </div>
    <!-- End Content -->




    <?php include_once 'Views/template/footer.php'?>
