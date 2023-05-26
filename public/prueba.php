<main class="flex-1 grid grid-cols-3 gap-4 justify-center justify-items-center">
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
                <form action="valorar_articulo.php" method="GET">
                    <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                        Valoración:
                        <select name="valoracion" id="valoracion">
                            <option value="" <?= (!$usuario_id) ? 'selected' : '' ?>></option>
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <option value="<?= $i ?>" <?= ($valoracion_usuario && $valoracion_usuario['valoracion'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor ?>
                        </select>
                    </label>
                    <input type="hidden" name="articulo_id" value="<?= $fila['id'] ?>">
                    <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
                    <?php if (!\App\Tablas\Usuario::esta_logueado()) : ?>
                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800" disabled>Votar</button>
                    <?php else : ?>
                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Votar</button>
                    <?php endif ?>
                </form>
                <div>
                    <label class="block text-m font-medium pl-3 ml-3">
                        Valoración media: <?= hh($valoracionMedia) ?>
                    </label>
                </div>
            </div>
            <form action="comentar_articulo.php" method="POST" class="inline">
                <input type="hidden" name="articulo_id" value="<?= $fila['id'] ?>">
                <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
                <button type="submit" onclick="cambiar(event, <?= $fila['id'] ?>, <?= $usuario_id ?>)" class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 mr-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900" data-modal-toggle="insertar_comentario">Comentar</button>
            </form>
        </div>
    <?php endforeach ?>
</main>
