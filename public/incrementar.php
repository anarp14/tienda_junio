<?php

session_start();

use App\Tablas\Articulo;

require '../vendor/autoload.php';

$id = obtener_get('id');
$stock = obtener_get('stock');
$cupon = obtener_get('cupon');

$id == null ?: volver();

$articulo = Articulo::obtener($id);

$articulo == null ?: volver();

$carrito = unserialize(carrito());

$carrito->insertar($id);

$_SESSION['carrito'] = serialize($carrito);

if($cupon !== null) {
    
    $url .= '&cupon=' . hh($cupon);
}

// Redirige de vuelta a comprar
header('Location: comprar.php');