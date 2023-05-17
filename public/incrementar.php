<?php

session_start();

use App\Tablas\Articulo;

require '../vendor/autoload.php';

$id = obtener_get('id');

$id == null ?: volver();

$articulo = Articulo::obtener($id);

$articulo == null ?: volver();

$cant = empty($lineas[$id]) ? 0 : $lineas[$id]->getCantidad();
 
if( $stock <= $cant){
    $_SESSION['error'] = 'No hay existencias suficientes.';
    return volver_comprar();
}

$carrito = unserialize(carrito());

$carrito->insertar($id);

$_SESSION['carrito'] = serialize($carrito);

// Redirige de vuelta a comprar
header('Location: comprar.php');