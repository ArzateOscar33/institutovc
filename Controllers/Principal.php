<?php
class Principal extends Controller
{
    public function __construct() {
        parent::__construct();
        session_start();
    }
    public function index()
    {
        
    }
    //vista about
    public function about(){
        $data['title']='Sobre Nosotros';
        $this->views->getView('principal',"about",$data);
    }

     //vista SHOP
     public function shop(){
        $data['title']='Nuestros Productos';
        $this->views->getView('principal',"shop",$data);
    }
    //vista detalle
    public function detail($id_producto){
        $data['title']='-------';
        $this->views->getView('principal',"shop-single",$data);
    }
     //vista Contacto
     public function contacto(){
        $data['title']='Contactanos';
        $this->views->getView('principal',"contact",$data);
    }






}//class