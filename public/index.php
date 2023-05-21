<?php session_start() ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/output.css" rel="stylesheet">
    <title>Portal</title>
    <script>
        function cambiar(el, articulo_id, usuario_id) {
            el.preventDefault();
            const oculto_art = document.getElementById('ocultoId');
            oculto_art.setAttribute('value', articulo_id);
            const oculto_usuario = document.getElementById('ocultoIdUsuario');
            oculto_usuario.setAttribute('value', usuario_id);
        }
    </script>

</head>

<body>
    <?php
    require '../vendor/autoload.php';

    use \App\Tablas\Factura;
    use App\Tablas\Usuario;


    $carrito = unserialize(carrito());
    $categoria = obtener_get('categoria');
    $etiquetas = obtener_get('etiqueta');
    $valoracion = obtener_get('valoracion');
    $precio_min = obtener_get('precio_min');
    $precio_max = obtener_get('precio_max');

    $where = '';
    $having = '';
    $execute = [];
    $valid_etiquetas = [];

    $pdo = conectar();


    if (isset($precio_min) && $precio_min != '') {
        $where .= ' AND precio >= :precio_min';
        $execute[':precio_min'] = $precio_min;
    }

    if (isset($precio_max) && $precio_max != '') {
        $where .= ' AND precio <= :precio_max';
        $execute[':precio_max'] = $precio_max;
    }

    // si se ha enviado algún valor para las etiquetas se separan por espacio utilizando la función explode() y se itera sobre cada una de las etiquetas
    $etiquetas = isset($etiquetas) ? explode(" ", $etiquetas) : [];

    foreach ($etiquetas as $etiqueta) {
        //Para cada etiqueta, se prepara una consulta que busca su ID en la tabla "etiquetas" y se ejecuta con el valor de la etiqueta como parámetro.
        //Si la consulta devuelve un resultado, se agrega la etiqueta al array $valid_etiquetas
        $sent = $pdo->prepare("SELECT id FROM etiquetas WHERE lower(unaccent(etiqueta)) = lower(unaccent(:etiqueta))");
        $sent->execute([':etiqueta' => $etiqueta]);
        //se utiliza para comprobar si una consulta SQL devuelve resultados o no
        if ($sent->fetchColumn() !== false) {
            array_push($valid_etiquetas, $etiqueta);
        }
    }
    //Si hay etiquetas válidas, se prepara una cláusula WHERE que busca los registros que contengan alguna de las etiquetas válidas utilizando la función implode()
    // para unir las cláusulas en una sola cadena
    if (!empty($valid_etiquetas)) {
        $where_clauses = [];
        foreach ($valid_etiquetas as $key => $etiqueta) {
            $where_clauses[] = 'lower(unaccent(e.etiqueta)) LIKE lower(unaccent(:etiqueta' . $key . '))';
            $execute[':etiqueta' . $key] = $etiqueta;
        }
        $where = 'WHERE (' . implode(' OR ', $where_clauses) . ')';
        $having = 'HAVING COUNT(DISTINCT ae.etiqueta_id) = ' . count($valid_etiquetas); //cuenta las etiquetas en los registros y verifica que se hayan encontrado todas las etiquetas válidas
    }

    if (isset($categoria) && $categoria != '') {
        $where .= ' AND categoria_id = :categoria';
        $execute[':categoria'] = $categoria;
    }

    $sin_valoracion = isset($_GET['sin_valoracion']) ? $_GET['sin_valoracion'] : false;
    $where_sin_valoracion = '';

    if ($sin_valoracion) {
        $where_sin_valoracion = 'AND articulos.id NOT IN (SELECT DISTINCT articulo_id FROM valoraciones)';
    }

    $mas_valoraciones = isset($_GET['mas_valoraciones']) ? $_GET['mas_valoraciones'] : false;

    $having_mas_valoraciones = '';
    $cond = '';
    $condicion = '';

    if ($mas_valoraciones) {
        $having_mas_valoraciones  = 'HAVING COUNT (usuario_id) >= ALL (SELECT DISTINCT COUNT (usuario_id) FROM valoraciones group by articulo_id)';
        $condicion = 'JOIN valoraciones val ON (val.articulo_id = articulos.id)';
        $cond = ', count(usuario_id)';
    }

    $mayor_valoracion = isset($_GET['mayor_valoracion']) ? $_GET['mayor_valoracion'] : false;

    $having_mayor_valoracion = '';
    $cond2 = '';
    $condicion2 = '';
    $condicion3 = '';


    if ($mayor_valoracion) {
        $condicion2 = 'JOIN valoraciones val ON (val.articulo_id = articulos.id) ';
        $condicion3 = 'ORDER BY AVG(valoracion) DESC LIMIT 1';
        $cond2 = ', AVG(valoracion)';
    }


    $sent = $pdo->prepare("SELECT articulos.*, c.categoria, c.id as catid $cond $cond2 
    FROM articulos
    JOIN categorias c ON (articulos.categoria_id = c.id) 
    JOIN articulos_etiquetas ae ON (articulos.id = ae.articulo_id)
    JOIN etiquetas e ON (ae.etiqueta_id = e.id)
    $condicion $condicion2
    $where $where_sin_valoracion
    GROUP BY articulos.id, c.categoria, c.id $condicion3
    $having  $having_mas_valoraciones 
    ");


    $sent->execute($execute);

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
                        </label>
                        <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                            Precio mínimo:
                            <input type="text" name="precio_min" value="<?= isset($precio_min) ? $precio_min : '' ?>" class="border text-sm rounded-lg w-full p-2.5">
                        </label>
                        <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                            Precio máximo:
                            <input type="text" name="precio_max" value="<?= isset($precio_max) ? $precio_max : '' ?>" class="border text-sm rounded-lg w-full p-2.5">
                        </label>
                    </div>
                    <div class="flex mb-3 font-normal text-gray-700 dark:text-gray-400">
                        <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                            <input type="radio" name="sin_valoracion" value="1">
                            Mostrar sólo artículos sin valoración
                            <br>
                            <input type="radio" name="mas_valoraciones" value="2">
                            Mostrar artículo/s con más valoraciones <br>
                            <input type="radio" name="mayor_valoracion" value="3">
                            Mostrar sólo artículos con mayor valoración
                            <br>
                        </label>
                    </div>
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Buscar</button>
                </fieldset>
            </form>
        </div>
        <div class="flex">
            <main class="flex-1 grid grid-cols-3 gap-4 justify-center justify-items-center">
                <?php
                $usuario = \App\Tablas\Usuario::logueado();
                $usuario_id = $usuario ? $usuario->id : null;

                $facturas = Factura::todosConTotal(
                    ['usuario_id = :usuario_id'],
                    [':usuario_id' => $usuario_id]
                );


                ?>
                <?php foreach ($sent as $fila) : ?>
                    <div class="p-6 max-w-xs min-w-full bg-white rounded-lg border border-gray-200 shadow-md dark:bg-gray-800 dark:border-gray-700">
                        <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"><?= hh($fila['descripcion']) ?> - <?= hh($fila['precio']) ?> € </h5>
                        <p class="mb-3 font-normal text-gray-700 dark:text-gray-400"><?= hh($fila['categoria']) ?></p>
                        <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">Existencias: <?= hh($fila['stock']) ?></p>
                        <?php if ($fila['stock'] > 0) : ?>
                            <a href="/insertar_en_carrito.php?id=<?= $fila['id'] ?>&categoria=<?= hh($categoria) ?>&etiqueta=<?= hh(implode(' ', $etiquetas)) ?>" class="inline-flex items-center py-2 px-3.5 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
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
                            <?php if (!empty($facturas)) : ?>

                                <?php foreach ($facturas as $factura) : ?>
                                    <?php
                                    $articuloId = $fila['id']; // Obtén el ID del artículo actual

                                    // Verifica si el artículo está comprado utilizando el método seHaComprado
                                    $estaComprado = $factura->seHaComprado($articuloId);
                                    ?>
                                    <form action="valorar_articulo.php" method="GET">
                                        <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                                            Valoración:
                                            <?php
                                            $sent3 = $pdo->prepare("SELECT *
                                                        FROM valoraciones
                                                        WHERE usuario_id = :usuario_id AND articulo_id = :articulo_id");
                                            $sent3->execute(['usuario_id' => $usuario_id, 'articulo_id' => $fila['id']]);
                                            $valoracion_usuario = $sent3->fetch(PDO::FETCH_ASSOC);
                                            ?>
                                            <select name="valoracion" id="valoracion">
                                                <option value="" <?= (!$usuario_id) ? 'selected' : '' ?>></option>
                                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                    <option value="<?= $i ?>" <?= ($valoracion_usuario && $valoracion_usuario['valoracion'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                                <?php endfor ?>
                                            </select>
                                        </label>
                                        <input type="hidden" name="articulo_id" value="<?= $fila['id'] ?>">
                                        <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">

                                        <?php if (!(\App\Tablas\Usuario::esta_logueado() && $estaComprado)) : ?>
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
                                                        WHERE articulo_id = :articulo_id");
                                            $sent4->execute(['articulo_id' => $fila['id']]);
                                            $valoracionMedia = $sent4->fetchColumn();
                                            ?>
                                            <p class="mb-3 pl-3 font-normal text-gray-700 dark:text-gray-400"><?= hh($valoracionMedia) ?></p>
                                        </label>
                                    </div>
                        </div>

                        <form action="comentar_articulo.php" method="POST" class="inline">
                            <input type="hidden" name="articulo_id" value="<?= $fila['id'] ?>">
                            <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
                            <?php $estaComprado = $factura->seHaComprado($articuloId); ?>
                            <?php if (!(\App\Tablas\Usuario::esta_logueado() && $estaComprado)) : ?>
                                <button type="submit" onclick="cambiar(event, <?= $fila['id'] ?>, <?= $usuario_id ?>)" class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 mr-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900" disabled data-modal-toggle="insertar_comentario">Comentar</button>
                            <?php else : ?>
                                <button type="submit" onclick="cambiar(event, <?= $fila['id'] ?>, <?= $usuario_id ?>)" class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 mr-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900" data-modal-toggle="insertar_comentario">Comentar</button>
                            <?php endif ?>
                        </form>
                    <?php endforeach ?>
                <?php else : ?>
                    <form action="valorar_articulo.php" method="GET">
                        <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                            Valoración:
                            <?php
                                $usuario = \App\Tablas\Usuario::logueado();
                                $usuario_id = $usuario ? $usuario->id : null;

                                $sent3 = $pdo->prepare("SELECT *
                                                        FROM valoraciones
                                                        WHERE usuario_id = :usuario_id AND articulo_id = :articulo_id");
                                $sent3->execute(['usuario_id' => $usuario_id, 'articulo_id' => $fila['id']]);
                                $valoracion_usuario = $sent3->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <select name="valoracion" id="valoracion">
                                <option value="" <?= (!$usuario_id) ? 'selected' : '' ?>></option>
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <option value="<?= $i ?>" <?= ($valoracion_usuario && $valoracion_usuario['valoracion'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor ?>
                            </select>
                        </label>
                        <input type="hidden" name="articulo_id" value="<?= $fila['id'] ?>">
                        <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">


                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800" disabled>Votar</button>

                    </form>
                    <div>
                        <label class="block text-m font-medium pl-3 ml-3">
                            Valoración media:
                            <?php
                                $sent4 = $pdo->prepare("SELECT avg(valoracion)::numeric(10,2)
                                                        FROM valoraciones
                                                        WHERE articulo_id = :articulo_id");
                                $sent4->execute(['articulo_id' => $fila['id']]);
                                $valoracionMedia = $sent4->fetchColumn();
                            ?>
                            <p class="mb-3 pl-3 font-normal text-gray-700 dark:text-gray-400"><?= hh($valoracionMedia) ?></p>
                        </label>
                    </div>
                    </div>

                    <form action="comentar_articulo.php" method="POST" class="inline">
                        <input type="hidden" name="articulo_id" value="<?= $fila['id'] ?>">
                        <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
                        <button type="submit" disabled onclick="cambiar(event, <?= $fila['id'] ?>, <?= $usuario_id ?>)" class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 mr-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900" data-modal-toggle="insertar_comentario">Comentar</button>
                    </form>
                <?php endif ?>
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
    <!-- Esto es para añadir un nuevo comentario -->
    <div id="insertar_comentario" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 md:inset-0 h-modal md:h-full">
        <div class="relative p-4 w-full max-w-md h-full md:h-auto">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <button type="button" class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-toggle="insertar_comentario">
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Cerrar ventana</span>
                </button>
                <div class="p-6 text-center">
                    <form action="/comentar_articulo.php" method="POST">
                        <div class="mb-6">
                            <label for="comentario" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Comentario
                                <textarea name="comentario" id="comentario" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600  dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required rows="5"></textarea>
                            </label>
                            <input id="ocultoId" type="hidden" name="articulo_id">
                            <input id="ocultoIdUsuario" type="hidden" name="usuario_id">
                        </div>
                        <button data-modal-toggle="insertar_comentario" type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center mr-2">
                            Enviar
                        </button>
                        <button data-modal-toggle="insertar_comentario" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
                            No, cancelar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="/js/flowbite/flowbite.js"></script>
</body>

</html>