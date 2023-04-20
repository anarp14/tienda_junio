<?php

use App\Tablas\Articulo;

session_start();

require '../vendor/autoload.php';

$categoria = obtener_get('categoria');

try {
    $id = obtener_get('id');


    if ($id === null) {
        return volver();
    }

    $articulo = Articulo::obtener($id);

    if ($articulo === null) {
        return volver();
    }

    if ($articulo->getStock() <= 0) {
        $_SESSION['error'] = 'No hay existencias suficientes.';
        return volver();
    }

    $carrito = unserialize(carrito());
    $carrito->insertar($id);
    $_SESSION['carrito'] = serialize($carrito);
} catch (ValueError $e) {
    // TODO: mostrar mensaje de error en un Alert
}

$params = "";
if ($categoria !== null) {
    $params .= '&categoria=' . hh($categoria);
}


header("Location: /index.php?$params");
