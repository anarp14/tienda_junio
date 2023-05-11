<?php
session_start();
require '../vendor/autoload.php';

$comentario = obtener_post('comentario');

$articulo_id = obtener_post('articulo_id');

$usuario_id = obtener_post('usuario_id'); // Suponiendo que ya tienes el ID del usuario en una sesión

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