<?php
session_start();

require '../../vendor/autoload.php';

$codigo = obtener_post('codigo');
$descripcion = obtener_post('descripcion');
$precio = obtener_post('precio');
$stock = obtener_post('stock');
$categoria = obtener_post('categoria');
$etiquetas = obtener_post('etiquetas');
$values = [];
$execute = [];

$pdo = conectar();

if (isset($codigo)  && $codigo != '') {
    $values[] = ':codigo';
    $execute[':codigo'] = $codigo;
}

if (isset($descripcion) && $descripcion != '') {
    $values[] = ':descripcion';
    $execute[':descripcion'] = $descripcion;
}

if (isset($precio) && $precio != '') {
    $values[] = ':precio';
    $execute[':precio'] = $precio;
}

if (isset($stock) && $stock != '') {
    $values[] = ':stock';
    $execute[':stock'] = $stock;
}

if (isset($categoria) && $categoria != '') {
    $values[] = ':id_categoria';
    $execute[':id_categoria'] = $categoria;
}


$values = !empty($values) ? 'VALUES (' . implode(' , ', $values) . ')'  : '';


try {
    $sent = $pdo->prepare("INSERT INTO articulos (codigo, descripcion, precio, stock, id_categoria)
                            $values");
    $sent->execute($execute);
    $_SESSION['exito'] = 'El artículo se ha insertado correctamente.';
} catch (\Throwable $th) {
    $_SESSION['error'] = 'debe rellenar todos los campos';
}

volver_admin();
