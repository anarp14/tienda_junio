<?php
session_start();

require '../../vendor/autoload.php';

$id = obtener_post('id');

if (!isset($id)) {
    return volver_categoria();
}



$pdo = conectar();

$sent = $pdo->prepare("SELECT a.id FROM articulos a JOIN categorias c ON (c.id = a.id_categoria) WHERE c.id = :id ");
$sent->execute([':id' => $id]);
$res = $sent->fetchColumn();

if ($res < 1) {
    $sent = $pdo->prepare("DELETE FROM categorias WHERE id = :id");
    $sent->execute([':id' => $id]);
    $_SESSION['exito'] = 'El artículo se ha borrado correctamente.';
} else {
    $_SESSION['error'] = 'La categoría se encuentra asociada a un artículo.';
}

volver_categoria();