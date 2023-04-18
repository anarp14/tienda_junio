<?php session_start() ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/output.css" rel="stylesheet">
    <title>Portal</title>
</head>

<body>
<?php
    require '../vendor/autoload.php';
    $carrito = unserialize(carrito());

    $categoria = obtener_get('categoria');
    $etiquetas = obtener_get('etiqueta');
    $valoracion = obtener_get('valoracion');


    $pdo = conectar();

    $where = [];
    $execute = [];
    
    if (isset($categoria) && $categoria != '') {
        $where[] = 'id_categoria = :categoria';
        $execute[':categoria'] = $categoria;
    }
    
    $where = !empty($where) ? ' AND ' . implode(' AND ', $where) : '';
    
    if (isset($etiquetas) && $etiquetas != '') {
        $etiquetas_validas = [];
        $where_etiquetas = [];
        $etiquetas = explode(' ', $etiquetas);

        foreach ($etiquetas as $etiqueta) {
            $sent = $pdo->prepare("SELECT e.id
                                    FROM articulos_etiquetas ae JOIN etiquetas e ON (ae.id_etiqueta = e.id)
                                    WHERE lower(unaccent(etiqueta)) LIKE lower(unaccent(:etiqueta))");
            $sent->execute([':etiqueta' => $etiqueta]);
            $etiquevaValida = $sent->fetchColumn();
            if ($etiquevaValida) {
                array_push($etiquetas_validas, $etiquevaValida);
            }
        }

         if (!empty($etiquetas_validas)) {
            $execute[':etiquetas'] = implode(',', $etiquetas_validas);
            $sent = $pdo->prepare("SELECT a.*, c.categoria, c.id as catid
                                    FROM articulos a JOIN categorias c ON (a.id_categoria = c.id)
                                    $where AND a.id IN (SELECT ae.id_articulo FROM articulos_etiquetas ae
                                                        WHERE ae.id_etiqueta IN (:etiquetas))");
            $sent->execute($execute);
        }

    } else {
        $sent = $pdo->prepare("SELECT a.*, c.categoria, c.id as catid
                                FROM articulos a JOIN categorias c ON (a.id_categoria = c.id)
                                $where");
        $sent->execute($execute);
    }

    ?>
    <div class="container mx-auto">
        <?php require '../src/_menu.php' ?>
        <?php require '../src/_alerts.php' ?>
        <div>
            <form action="" method="GET">
                <fieldset>
                    <legend><b>Criterios de búsqueda</b></legend>
                    <br>
                    <div class="flex mb-3 font-normal text-gray-700 dark:text-gray-400">
                        <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                            Categoría:
                            <select name="categoria" id="categoria" class="border text-sm rounded-lg w-full p-2.5">
                                <?php
                                $sent2 = $pdo->query("SELECT * FROM categorias");
                                ?>
                                <option value="">Todas las categorías</option>
                                <?php foreach ($sent2 as $fila) : ?>
                                    <option value=<?= hh($fila['id']) ?> <?= ($fila['id'] == $categoria) ? 'selected' : '' ?>>
                                        <?= hh($fila['categoria']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </label>
                        <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                            Etiquetas:
                            <input type="text" name="etiqueta" value="<?= isset($etiquetas) && is_array($etiquetas) ? implode(' ', $etiquetas) : '' ?>" class="border text-sm rounded-lg w-full p-2.5">

                    </div>
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Buscar</button>
                </fieldset>
            </form>
        </div>
        <div class="flex">
            <main class="flex-1 grid grid-cols-3 gap-4 justify-center justify-items-center">
                <?php foreach ($sent as $fila) : ?>
                    <div class="p-6 max-w-xs min-w-full bg-white rounded-lg border border-gray-200 shadow-md dark:bg-gray-800 dark:border-gray-700">
                        <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"><?= hh($fila['descripcion']) ?> - <?= hh($fila['precio']) ?> € </h5>
                        <p class="mb-3 font-normal text-gray-700 dark:text-gray-400"><?= hh($fila['categoria']) ?></p>
                        <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">Existencias: <?= hh($fila['stock']) ?></p>
                        <?php if ($fila['stock'] > 0) : ?>
                            <a href="/insertar_en_carrito.php?id=<?= $fila['id'] ?>&categoria=<?= hh($categoria) ?>&etiqueta=<?= hh($etiqueta) ?>" class="inline-flex items-center py-2 px-3.5 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                Añadir al carrito
                                <svg aria-hidden="true" class="ml-3 -mr-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </a>
                        <?php else : ?>
                            <a class="inline-flex items-center py-2 px-3.5 text-sm font-medium text-center text-white bg-gray-700 rounded-lg hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                                Sin existencias
                            </a>
                        <?php endif ?>
                        <div class="flex mb-3 font-normal text-gray-700 dark:text-gray-400">
                            <form action="valorar_articulo.php" method="GET">
                                <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                                    Valoración:
                                    <?php
                                    $usuario = \App\Tablas\Usuario::logueado();
                                    $id_usuario = $usuario ? $usuario->id : null;

                                    $sent3 = $pdo->prepare("SELECT *
FROM valoraciones
WHERE usuario_id = :id_usuario AND articulo_id = :id_articulo");
                                    $sent3->execute(['id_usuario' => $id_usuario, 'id_articulo' => $fila['id']]);
                                    $valoracion_usuario = $sent3->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <select name="valoracion" id="valoracion">
                                        <option value="" <?= (!$id_usuario) ? 'selected' : '' ?>></option>
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                            <option value="<?= $i ?>" <?= ($valoracion_usuario && $valoracion_usuario['valoracion'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor ?>
                                    </select>
                                </label>
                                <input type="hidden" name="articulo_id" value="<?= $fila['id'] ?>">
                                <input type="hidden" name="usuario_id" value="<?= $id_usuario ?>">

                                <?php if (!\App\Tablas\Usuario::esta_logueado()) : ?>
                                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800" disabled>Votar</button>
                                <?php else : ?>
                                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Votar</button>
                                <?php endif ?>
                            </form>
                            <div>
                                <label class="block text-m font-medium pl-3 ml-3">
                                    Valoración media:
                                    <?php
                                    $sent4 = $pdo->prepare("SELECT avg(valoracion)::numeric(10,2)
FROM valoraciones
WHERE articulo_id = :id_articulo");
                                    $sent4->execute(['id_articulo' => $fila['id']]);
                                    $valoracionMedia = $sent4->fetchColumn();
                                    ?>
                                    <p class="mb-3 pl-3 font-normal text-gray-700 dark:text-gray-400"><?= hh($valoracionMedia) ?></p>
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </main>

            <?php if (!$carrito->vacio()) : ?>
                <aside class="flex flex-col items-center w-1/4" aria-label="Sidebar">
                    <div class="overflow-y-auto py-4 px-3 bg-gray-50 rounded dark:bg-gray-800">
                        <table class="mx-auto text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <th scope="col" class="py-3 px-6">Descripción</th>
                                <th scope="col" class="py-3 px-6">Cantidad</th>
                            </thead>
                            <tbody>
                                <?php foreach ($carrito->getLineas() as $id => $linea) : ?>
                                    <?php
                                    $articulo = $linea->getArticulo();
                                    $cantidad = $linea->getCantidad();
                                    ?>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="py-4 px-6"><?= $articulo->getDescripcion() ?> <br>
                                            <?= $articulo->getCategoriaNombre($pdo) ?>
                                            <?= $articulo->getEtiquetaNombre($pdo) ?>

                                        </td>
                                        <td class="py-4 px-6 text-center"><?= $cantidad ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <a href="/vaciar_carrito.php" class="focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">Vaciar carrito</a>
                        <a href="/comprar.php" class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900">Comprar</a>
                    </div>
                </aside>
            <?php endif ?>
        </div>
    </div>
    <script src="/js/flowbite/flowbite.js"></script>
</body>

</html>