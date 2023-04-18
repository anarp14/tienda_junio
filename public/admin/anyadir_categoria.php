<?php
session_start();

require '../../vendor/autoload.php';

$categoria = obtener_post('categoria');
$values = [];
$execute = [];

$pdo = conectar();

$categoria = ucfirst($categoria);

if (isset($categoria) && $categoria != '') {
    $values[] = ':categoria';
    $execute[':categoria'] = $categoria;
}

$values = !empty($values) ? 'VALUES (' . implode(' , ', $values) . ')'  : '';

try {
    $sent = $pdo->prepare("INSERT INTO categorias (categoria)
                            $values");
    $sent->execute($execute);
    $_SESSION['exito'] = 'La categoria se ha insertado correctamente.';
} catch (\Throwable $th) {
    print_r($th);
    die();
    $_SESSION['error'] = 'Debe rellenar todos los campos';
}

volver_categoria();