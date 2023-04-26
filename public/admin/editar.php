<?php
session_start();

require '../../vendor/autoload.php';

$id = obtener_post('id');
$codigo = obtener_post('codigo');
$descripcion = obtener_post('descripcion');
$precio = obtener_post('precio');
$stock = obtener_post('stock');
$categoria = obtener_post('categoria');
$etiquetas = obtener_post('etiquetas');
$set = [];
$execute = [];


$pdo = conectar();

if (!isset($id)) {
    return volver_admin();
}


// Toma los valores actuales del artículo
$sent = $pdo->prepare("SELECT * FROM articulos WHERE id = :id");
$sent->execute([':id' => $id]);
$anterior = $sent->fetch(PDO::FETCH_ASSOC);


if (isset($id)) {
    $execute[':id'] = $id;
} else {
    $execute[':id'] = $anterior['id'];
}

if (isset($codigo)  && $codigo != '') {
    $set[] = 'codigo = :codigo';
    $execute[':codigo'] = $codigo;
} else {
    $set[] = 'codigo = :codigo';
    $execute[':codigo'] = $anterior['codigo'];
}

if (isset($descripcion) && $descripcion != '') {
    $set[] = 'descripcion = :descripcion';
    $execute[':descripcion'] = $descripcion;
} else {
    $set[] = 'descripcion = :descripcion';
    $execute[':descripcion'] = $anterior['descripcion'];
}

if (isset($precio) && $precio != '') {
    $set[] = 'precio = :precio';
    $execute[':precio'] = $precio;
} else {
    $set[] = 'precio = :precio';
    $execute[':precio'] = $anterior['precio'];
}

if (isset($stock) && $stock != '') {
    $set[] = 'stock = :stock';
    $execute[':stock'] = $stock;
} else {
    $set[] = 'stock = :stock';
    $execute[':stock'] = $anterior['stock'] > 0 ? $anterior['stock'] : 0;
}

if (isset($categoria) && $categoria != '') {
    $set[] = 'categoria_id = :categoria_id';
    $execute[':categoria_id'] = $categoria;
} else {
    $set[] = 'categoria_id = :categoria_id';
    $execute[':categoria_id'] = $anterior['categoria_id'];
}

$set = !empty($set) ? 'SET ' . implode(', ', $set) : '';

try {
    $sent = $pdo->prepare("UPDATE articulos
                            $set
                            WHERE  id = :id");
    $sent->execute($execute);
} catch (\Throwable $th) {
    print_r($th);
}



$_SESSION['exito'] = 'El artículo se ha Modificado correctamente.';

volver_admin();
