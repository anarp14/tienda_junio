<?php

use App\Tablas\Articulo;

session_start();

require '../vendor/autoload.php';

$categoria = obtener_get('categoria');
$nombre = obtener_get('nombre');
$etiqueta = obtener_get('etiqueta');

try {
    $id = obtener_get('id');

    if ($id === null) {
        volver();
    }

    $articulo = Articulo::obtener($id);

    if ($articulo === null) {
        volver();
    }

    if ($articulo->getStock() <= 0) {
        $_SESSION['error'] = 'No hay existencias suficientes.';
        volver();
    }

    $carrito = unserialize(carrito());

    //$carrito->insertar($id);

    // Impide insertar en el carrito más articículos que los que hay en stock
    $stock = $articulo->getStock();
    $lineas = $carrito->getLineas();
    /*  $cantidad = empty($lineas) ? 0 : $lineas[$id]->getCantidad(); --> 
        No funciona;  si $lineas[$id] es nulo, entonces llamar a getCantidad() sobre él resultará en error */
    /* 
        agregamos una verificación para !isset($lineas[$id]), que asegura que $lineas[$id] no sea nulo
        antes de llamar a getCantidad() sobre él. Si $lineas[$id] es nulo o no está definido, entonces
        la variable $cantidad se establece en 0, lo que evita que se produzca el error.
     */
    $cantidad = empty($lineas) || !isset($lineas[$id]) ? 0 : $lineas[$id]->getCantidad();
    
    if ($stock > $cantidad) {
        $carrito->insertar($id);
    }
    
    
    
    
    $_SESSION['carrito'] = serialize($carrito);

    $params = "";
    if ($nombre !== null) {
        $params .= '&nombre=' . hh($nombre);
    }

        
    if ($categoria !== null) {
        $params .= '&categoria=' . hh($categoria);
    }

            
    if ($etiqueta !== null) {
        $params .= '&etiqueta=' . hh($etiqueta);
    }

    header("Location: /index.php?$params");
} catch (ValueError $e) {
    // TODO: mostrar mensaje de error en un Alert
    volver();
}
