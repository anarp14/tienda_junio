<?php

session_start();

require '../vendor/autoload.php';

$valoracion = obtener_get('valoracion');
$id_articulo = obtener_get('articulo_id');
$id_usuario = obtener_get('usuario_id');

$pdo = conectar();

$sent = $pdo->prepare("SELECT *
                            FROM valoraciones
                            WHERE usuario_id = :id_usuario AND articulo_id = :id_articulo");
$sent->execute([':id_usuario' => $id_usuario, ':id_articulo' => $id_articulo]);


if (isset($valoracion) && $valoracion != '' &&  $valoracion != null) {
    if ($sent->rowCount() > 0) {
        $sent = $pdo->prepare("UPDATE valoraciones
                                SET valoracion = :valoracion
                                WHERE usuario_id = :usuario_id AND articulo_id = :articulo_id");
        $sent->execute(['valoracion' => $valoracion, 'usuario_id' => $id_usuario, 'articulo_id' => $id_articulo]);
    } else {
        $sent = $pdo->prepare("INSERT INTO valoraciones (articulo_id, usuario_id, valoracion)
                                    VALUES (:articulo_id, :usuario_id, :valoracion)");
        $sent->execute([':valoracion' => $valoracion, ':usuario_id' => $id_usuario, ':articulo_id' => $id_articulo]);
    }
} else {
    $sent = $pdo -> prepare("DELETE FROM valoraciones
                                    WHERE usuario_id = :id_usuario AND articulo_id = :id_articulo");
    $sent->execute([':id_usuario' => $id_usuario, ':id_articulo' => $id_articulo]);
}

volver();