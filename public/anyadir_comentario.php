<?php
session_start();
require '../vendor/autoload.php';
// Obtener los datos del formulario de votación
$texto = ''; // inicializar la variable $texto

if(isset($_POST['texto'])) {
  $texto = $_POST['texto'];
}
$articulo_id = $_GET['articulo_id'];

$usuario_id = $_GET['usuario_id']; // Suponiendo que ya tienes el ID del usuario en una sesión

$pdo = conectar();

// Verificar si el usuario ya ha comentado en la tabla de comentarios
$sent = $pdo->prepare("SELECT * FROM comentarios_facturas WHERE usuario_id = :usuario_id AND articulo_id = :articulo_id");
$sent->execute(['usuario_id' => $usuario_id, 'articulo_id' => $articulo_id]);

if ($sent->rowCount() > 0) {
  // Si el usuario ya ha comentado, actualizar su comentario en la tabla de comentarios
  $sent = $pdo->prepare("UPDATE comentarios_facturas SET texto = :texto WHERE usuario_id = :usuario_id AND articulo_id = :articulo_id");
  $sent->execute(['texto' => $texto, 'usuario_id' => $usuario_id, 'articulo_id' => $articulo_id]);
} else {
  // Si el usuario no ha comentado todavía, insertar su comentario en la tabla de texto
  $sent = $pdo->prepare("INSERT INTO comentarios_facturas (texto, usuario_id, articulo_id) VALUES (:texto, :usuario_id, :articulo_id)");
  $sent->execute(['texto' => $texto, 'usuario_id' => $usuario_id, 'articulo_id' => $articulo_id]);
}

// Redirigir al usuario a la página del artículo
volver();
