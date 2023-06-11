<?php
session_start();

require '../vendor/autoload.php';


$id = \App\Tablas\Usuario::logueado()->id;
$nombre= obtener_post('nombre');
$apellidos= obtener_post('apellidos');
$email= obtener_post('email');

$set = [];
$execute = [];
$pdo = conectar();

if (isset($nombre)  && $nombre != '') {
    $set[] = 'nombre = :nombre';
    $execute[':nombre'] = $nombre;
}
if (isset($apellidos)  && $apellidos != '') {
    $set[] = ':apellidos = :apellidos';
    $execute[':apellidos'] = $apellidos;
}
if (isset($email) && $email != '') {
    if (!preg_match("/^[A-z0-9\\._-]+@[A-z0-9][A-z0-9-]*(\\.[A-z0-9_-]+)*\\.([A-z]{2,6})$/", $email)) {
        $_SESSION['error'] = "El email es inválido.";
        return (volver_a("/perfil.php"));
        
    }
    $set[] = 'email = :email';
    $execute[':email'] = $email;
}

$set= !empty($set) ? 'SET ' . implode(' , ', $set) : '';

$sent = $pdo->prepare("UPDATE usuarios
                        $set
                       WHERE id = $id");

$sent->execute($execute);

$_SESSION['exito'] = 'El perfil del usuario se ha añadido correctamente.';

volver_a("/perfil.php");