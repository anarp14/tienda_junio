<?php
session_start();

require '../../vendor/autoload.php';

$id = obtener_post('id');
$categoria_nombre = obtener_post('categoria');
$set = [];
$execute = [];


$pdo = conectar();

if (!isset($id)) {
    return volver_categoria();
}

$categoria_nombre = ucfirst($categoria_nombre);

// Toma los valores actuales del artículo
$sent = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
$sent->execute([':id' => $id]);
$anterior = $sent->fetch(PDO::FETCH_ASSOC);

if (isset($id)) {
    $execute[':id'] = $id;
} else {
    $execute[':id'] = $anterior['id'];
}

if (isset($categoria_nombre) && $categoria_nombre != '') {
    $set[] = 'categoria = :categoria';
    $execute[':categoria'] = $categoria_nombre;
} else {
    $set[] = 'categoria = :categoria';
    $execute[':categoria'] = $anterior['categoria'];
}

$set = !empty($set) ? 'SET ' . implode(', ', $set) : '';
print_r($set);
die();

try {
    if ($set != '') {
        $sent = $pdo->prepare("UPDATE categorias
                                $set
                                WHERE  id = :id");
        $sent->execute($execute);
        $_SESSION['exito'] = 'La categoria se ha Modificado correctamente.';
    } else {
        $_SESSION['error'] = 'Debe rellenar el formulario para modificar la categoría';
    }
} catch (\Throwable $th) {
    print_r($th);
}


volver_categoria();