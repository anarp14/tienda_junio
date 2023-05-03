<?php
session_start();
require '../vendor/autoload.php';
// Obtener los datos del formulario de votación
$comentario = ''; // inicializar la variable $comentario

if(isset($_POST['comentario'])) {
  $comentario = $_POST['comentario'];
}

$articulo_id = $_GET['articulo_id'];

$usuario_id = $_GET['usuario_id']; // Suponiendo que ya tienes el ID del usuario en una sesión

var_dump($comentario);
var_dump($articulo_id);
var_dump($usuario_id);

$pdo = conectar();

// Verificar si el usuario ya ha comentado en la tabla de comentarios
$sent = $pdo->prepare("SELECT * FROM comentarios WHERE usuario_id = :usuario_id AND articulo_id = :articulo_id");
$sent->execute(['usuario_id' => $usuario_id, 'articulo_id' => $articulo_id]);

if ($sent->rowCount() > 0) {
  // Si el usuario ya ha comentado, actualizar su comentario en la tabla de comentarios
  $sent = $pdo->prepare("UPDATE comentarios SET comentario = :comentario WHERE usuario_id = :usuario_id AND articulo_id = :articulo_id");
  $sent->execute(['comentario' => $comentario, 'usuario_id' => $usuario_id, 'articulo_id' => $articulo_id]);
} else {
  // Si el usuario no ha comentado todavía, insertar su comentario en la tabla de comentario
  $sent = $pdo->prepare("INSERT INTO comentarios (comentario, usuario_id, articulo_id) VALUES (:comentario, :usuario_id, :articulo_id)");
  $sent->execute(['comentario' => $comentario, 'usuario_id' => $usuario_id, 'articulo_id' => $articulo_id]);
}

// Redirigir al usuario a la página del artículo
volver();