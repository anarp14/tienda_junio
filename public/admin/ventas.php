<?php session_start() ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/output.css" rel="stylesheet">
    <script>
        function cambiar(el, id) {
            el.preventDefault();
            const oculto = document.getElementById('oculto');
            oculto.setAttribute('value', id);
        }
    </script>
    <title>Listado de artículos vendidos </title>
</head>

<body>
    <?php require '../../vendor/autoload.php';

    if ($usuario = \App\Tablas\Usuario::logueado()) {
        if (!$usuario->es_admin()) {
            $_SESSION['error'] = 'Acceso no autorizado.';
            return volver();
        }
    } else {
        return redirigir_login();
    }

    $pdo = conectar();

    $sent = $pdo->query("SELECT DISTINCT art.*, u.usuario, val.valoracion, af.cantidad, com.texto
    FROM articulos art
    JOIN articulos_facturas af ON (art.id = af.articulo_id)
    JOIN facturas f ON (f.id = af.factura_id)
    JOIN usuarios u ON (f.usuario_id = u.id)
    LEFT JOIN valoraciones val ON (val.usuario_id = u.id AND val.articulo_id = art.id)
    LEFT JOIN comentarios com ON (com.usuario_id = u.id AND com.articulo_id = art.id);
    ");

    ?>

    <div class="container mx-auto">
        <?php
        require '../../src/_menu.php';
        require '../../src/_alerts.php';
        ?>

        <div class="overflow-x-auto relative mt-4">
            <table class="mx-auto text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <th scope="col" class="py-3 px-6">Artículo</th>
                    <th scope="col" class="py-3 px-6">Precio</th>
                    <th scope="col" class="py-3 px-6">Cantidad</th>
                    <th scope="col" class="py-3 px-6">Total</th>
                    <th scope="col" class="py-3 px-6">Usuario</th>
                    <th scope="col" class="py-3 px-6">Valoración</th>
                    <th scope="col" class="py-3 px-6">Comentario</th>
                </thead>
                <tbody>
                    <?php foreach ($sent as $fila) : ?>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="py-4 px-6"><?= $fila['descripcion'] ?></td>
                            <td class="py-4 px-6"><?= $fila['precio'] ?></td>
                            <td class="py-4 px-6"><?= $fila['cantidad'] ?></td>
                            <td class="py-4 px-6"><?= $fila['cantidad'] * $fila['precio']  ?> </td>
                            <td class="py-4 px-6"><?= $fila['usuario'] ?></td>
                            <td class="py-4 px-6"><?= $fila['valoracion'] ? $fila['valoracion'] : '' ?></td>
                            <td class="py-4 px-6"><?= $fila['texto'] ? $fila['texto'] : '' ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="/js/flowbite/flowbite.js"></script>
</body>

</html>