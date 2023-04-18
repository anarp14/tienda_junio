<?php

session_start();

use App\Tablas\Articulo;

require '../vendor/autoload.php';

$id = obtener_get('id');

$id == null ?: volver();

$articulo = Articulo::obtener($id);

$articulo == null ?: volver();

$carrito = unserialize(carrito());

$carrito->eliminar($id);

$_SESSION['carrito'] = serialize($carrito);

// Redirige de vuelta a comprar
header('Location: comprar.php');