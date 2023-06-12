<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/output.css" rel="stylesheet">
    <title>Comprar</title>
</head>

<body>
    <?php
    require '../vendor/autoload.php';

    if (!\App\Tablas\Usuario::esta_logueado()) {
        return redirigir_login();
    }

    $carrito = unserialize(carrito());

       // Obtener el ID del cupón si se proporciona
       $cupon_id = null;
       $cupon = obtener_get('cupon');

       if (isset($cupon)) {
        $pdo = conectar();
           $sent = $pdo->prepare('SELECT id FROM cupones WHERE upper(unaccent(cupon)) = upper(unaccent(:cupon))');
           $sent->execute([':cupon' => $cupon]);
           $cupon_encontrado = $sent->fetch();
           if ($cupon_encontrado) {
               $cupon_id = $cupon_encontrado['id'];
           }
       }

       function calcular_total_factura($carrito, $cupon_id)
       {
           $total = 0;
       
           foreach ($carrito->getLineas() as $id => $linea) {
               $articulo = $linea->getArticulo();
               $cantidad = $linea->getCantidad();
               $precio = $articulo->getPrecio();
               $oferta = $articulo->getOferta() ? $articulo->getOferta() : '';
               $importe = $articulo->aplicarOferta($oferta, $cantidad, $precio)['importe'];
               $total += $importe;
           }
       
           // Aplicar descuento del cupón si se proporciona
           if ($cupon_id) {
               $pdo = conectar();
               $sent = $pdo->prepare('SELECT descuento FROM cupones WHERE id = :cupon_id');
               $sent->execute([':cupon_id' => $cupon_id]);
               $cupon = $sent->fetch();
               if ($cupon) {
                   $descuento = $cupon['descuento'];
                   $total -= ($total * ($descuento / 100));
               }
           }
       
           return $total;
       }

    if (obtener_post('_testigo') !== null) {
        $pdo = conectar();
        $sent = $pdo->prepare('SELECT *
                         FROM articulos
                        WHERE id IN (:ids)');

        foreach ($sent->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            if ($fila['stock'] < $carrito->getLinea($fila['id'])->getCantidad()) {
                $_SESSION['error'] = 'No hay existencias suficientes para crear la factura.';
                return volver();
            }
        }

        // Crear factura


        if (obtener_post('_puntos') !== null) {

            $total = calcular_total_factura($carrito, $cupon_id);
            $usuario = \App\Tablas\Usuario::logueado();
            $usuario_id = $usuario->id;
            $metodo_pago = obtener_post('metodo_pago');
            $puntos = $usuario->getPuntos();
            $rebaja = $total - $puntos;
            $restantes  = 0;
            if ($rebaja < 0) {
                $rebaja = 0;
                $restantes = $puntos - $total;
            }

            $pdo->beginTransaction();
            $sent = $pdo->prepare('INSERT INTO facturas (usuario_id, metodo_pago, cupon_id, total)
            VALUES (:usuario_id, :metodo_pago, :cupon_id, :total)
            RETURNING id');
            $sent->execute([':usuario_id' => $usuario_id, ':metodo_pago' => $metodo_pago, ':cupon_id' => $cupon_id, ':total' => $rebaja]);
            $factura_id = $sent->fetchColumn();
            $lineas = $carrito->getLineas();
            $values = [];
            $execute = [':f' => $factura_id];
            $i = 1;

            $sent3 = $pdo->query("UPDATE usuarios 
                                  SET puntos = $restantes 
                                  WHERE id = $usuario_id");
        } else {
            // Obtener el total de la factura
            $usuario = \App\Tablas\Usuario::logueado();
            $usuario_id = $usuario->id;
            $metodo_pago = obtener_post('metodo_pago');
            $puntos = $usuario->getPuntos();
            $total = calcular_total_factura($carrito, $cupon_id);


            $pdo->beginTransaction();
            $sent = $pdo->prepare('INSERT INTO facturas (usuario_id, metodo_pago, cupon_id, total)
                    VALUES (:usuario_id, :metodo_pago, :cupon_id, :total)
                    RETURNING id');
            $sent->execute([
                ':usuario_id' => $usuario_id,
                ':metodo_pago' => $metodo_pago,
                ':cupon_id' => $cupon_id,
                ':total' => $total
            ]);
            $factura_id = $sent->fetchColumn();


            $lineas = $carrito->getLineas();
            $values = [];
            $execute = [':f' => $factura_id];
            $i = 1;

            $sumaPuntos = round(($total / 2 + $puntos));


            $sent3 = $pdo->prepare("UPDATE usuarios 
                                  SET puntos = :puntos
                                  WHERE id = :id");
            $sent3->execute([
                ':puntos' => $sumaPuntos,
                ':id' => $usuario_id
            ]);
        }
        foreach ($lineas as $id => $linea) {
            $values[] = "(:a$i, :f, :c$i)";
            $execute[":a$i"] = $id;
            $execute[":c$i"] = $linea->getCantidad();
            $i++;
        }

        $values = implode(', ', $values);
        $sent = $pdo->prepare("INSERT INTO articulos_facturas (articulo_id, factura_id, cantidad)
                           VALUES $values");
        $sent->execute($execute);

        foreach ($lineas as $id => $linea) {
            $cantidad = $linea->getCantidad();
            $sent = $pdo->prepare('UPDATE articulos
                                  SET stock = stock - :cantidad
                                WHERE id = :id');
            $sent->execute([':id' => $id, ':cantidad' => $cantidad]);
        }

        $pdo->commit();

        $_SESSION['exito'] = 'La factura se ha creado correctamente.';
        unset($_SESSION['carrito']);

        return volver();
    }

    $cupon = obtener_get("cupon");

    $errores = ['cupon' => []];

    if (isset($cupon)) {
        $pdo = conectar();
        $sent = $pdo->prepare('SELECT * FROM cupones WHERE cupon = :cupon');
        $sent->execute([':cupon' => $cupon]);

        $cupon_encontrado = false;

        foreach ($sent as $fila) {
            if ($fila['cupon'] === $cupon) {
                $cupon_encontrado = true;
                if ($fila['fecha_fin'] <= date('Y-m-d')) {
                    $errores['cupon'][] = 'El cupón ha caducado.';
                }
                break;
            }
        }

        if (!$cupon_encontrado) {
            $errores['cupon'][] = 'No existe ese cupón.';
        }
    }

    $vacio = empty($errores['cupon']);
    ?>

    <div class="container mx-auto">
        <?php require '../src/_menu.php';
        require '../src/_alerts.php';
        ?>
        <div class="overflow-y-auto py-4 px-3 bg-gray-50 rounded dark:bg-gray-800">
            <table class="mx-auto text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <th scope="col" class="py-3 px-6">Código</th>
                    <th scope="col" class="py-3 px-6">Descripción</th>
                    <th scope="col" class="py-3 px-6">Cantidad</th>
                    <th scope="col" class="py-3 px-6">Precio</th>
                    <th scope="col" class="py-3 px-6">Importe</th>
                    <th scope="col" class="py-3 px-6">Ahorro</th>
                    <th scope="col" class="py-3 px-6">Oferta</th>
                    <th scope="col" class="py-3 px-6">Acciones</th>
                </thead>
                <tbody>
                    <?php $total = 0; ?>
                    <?php foreach ($carrito->getLineas() as $id => $linea) : ?>
                        <?php
                        $articulo = $linea->getArticulo();
                        $codigo = $articulo->getCodigo();
                        $cantidad = $linea->getCantidad();
                        $precio = $articulo->getPrecio();
                        $oferta = $articulo->getOferta() ? $articulo->getOferta() : '';
                        $importe = $articulo->aplicarOferta($oferta, $cantidad, $precio)['importe'];
                        $ahorro = $articulo->aplicarOferta($oferta, $cantidad, $precio)['ahorro'];
                        $total += $importe;
                        ?>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="py-4 px-6"><?= $articulo->getCodigo() ?></td>
                            <td class="py-4 px-6"><?= $articulo->getDescripcion() ?></td>
                            <td class="py-4 px-6 text-center"><?= $cantidad ?></td>
                            <td class="py-4 px-6 text-center"><?= dinero($precio) ?></td>
                            <td class="py-4 px-6 text-center"><?= dinero($importe) ?></td>
                            <?php if (isset($cupon)) : ?>
                                <?php
                                $pdo = conectar();
                                $cupones_ = $pdo->query("SELECT * FROM cupones WHERE cupon='" . hh($cupon) . "'");
                                foreach ($cupones_ as $cupo) {
                                    $descuento = hh($cupo['descuento']);
                                    $ahorro = $ahorro + ($total * ($descuento / 100));
                                }
                                ?>
                                <td class="py-4 px-6 text-center"><?= dinero($ahorro) ?></td>
                            <?php else : ?>
                                <td class="py-4 px-6 text-center"><?= dinero($ahorro) ?></td>
                            <?php endif ?>

                            <td class="py-4 px-6 text-center"><?= $oferta ?></td>

                            <td class="py-4 px-6 text-center">
                                <?php if (isset($cupon)) { ?>
                                    <a href="/incrementar.php?id=<?= $articulo->getId() ?>&cupon=<?= hh($cupon) ?>" class="focus:outline-none text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-900">+</a>
                                    <a href="/decrementar.php?id=<?= $articulo->getId() ?>&cupon=<?= hh($cupon) ?>" class="focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">-</a>
                                <?php } else { ?>
                                    <a href="/incrementar.php?id=<?= $articulo->getId() ?>" class="focus:outline-none text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-900">+</a>
                                    <a href="/decrementar.php?id=<?= $articulo->getId() ?>" class="focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">-</a>
                                <?php } ?>

                            </td>
                        </tr>
                    <?php endforeach ?>
                    <!-- Formulario para aplicar cupón de descuento -->
                    <div>
                        <h2>¿Tienes algún cupón de descuento?:</h2>
                        <form action="" method="GET" class="mx-auto flex mt-4">
                            <label>
                                <input type="text" name="cupon" value="<?= $cupon ?>" class="border text-sm rounded-lg p-2.5">
                                <button type="submit" class="mx-auto focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900">Aplicar cupon</button>
                                <?php foreach ($errores['cupon'] as $err) : ?>
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-bold">¡Error!</span> <?= $err ?></p>
                                <?php endforeach ?>
                            </label>
                        </form>
                    </div> <br>

                    <!-- Formulario para seleccionar método de pago -->

                    <h2>Método de pago:</h2>
                    <?php $metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : ''; ?>
                    <form action="" method="POST" class="mx-auto flex mt-4">
                        <input type="hidden" name="_testigo" value="1">
                        <select name="metodo_pago" id="metodo_pago" class="border text-sm rounded-lg p-2.5">
                            <option value="Tarjeta de crédito" <?= ($metodo_pago == "tarjeta") ? 'selected' : '' ?>>Tarjeta de crédito</option>
                            <option value="Paypal" <?= ($metodo_pago == "paypal") ? 'selected' : '' ?>>PayPal</option>
                            <option value="Transferencia bancaria" <?= ($metodo_pago == "transferencia") ? 'selected' : '' ?>>Transferencia bancaria</option>
                        </select>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td class="text-center font-semibold">TOTAL:</td>
                        <td class="text-center font-semibold"><?= dinero($total) ?></td>
                    </tr>
                    <?php if ($vacio && isset($cupon)) {
                        $pdo = conectar();
                        $cupones_ = $pdo->query("SELECT * FROM cupones WHERE cupon='" . hh($cupon) . "'");
                        foreach ($cupones_ as $cupo) {
                            $descuento = hh($cupo['descuento']);
                            $total_descuento = $total - ($total * ($descuento / 100));
                        }
                    ?>
                        <tr>
                            <td colspan="3"></td>
                            <td class="text-center font-semibold">TOTAL con descuento</td>
                            <td class="text-center font-semibold"><?= dinero($total_descuento) ?></td>
                            <td scope="col" class="py-3 px-6"> <?= $cupon ?> <?= $descuento ?> % </td>
                        </tr>
                    <?php } ?>

                    <tr>
                        <td colspan="3"></td>
                        <td class="text-center font-semibold">TOTAL + IVA (21%):</td>
                        <td class="text-center font-semibold"><?= dinero($vacio && isset($cupon) ? ($total_descuento * 1.21) : ($total * 1.21)) ?></td>
                    </tr>
                </tfoot>
            </table> <br>
            <div class="flex justify-center font-normal text-gray-700 dark:text-gray-400">
                <label class="block mb-2 text-sm font-medium w-1/4 pr-4">
                    <input type="checkbox" name="_puntos" value="1">
                    <?php $usuario = \App\Tablas\Usuario::logueado(); ?>
                    Utilizar los puntos acumulados: <?= $usuario->getPuntos() ?>
                </label>
            </div> <br>
            <div class="flex justify-center">
                <input type="hidden" name="_testigo" value="1">
                <button type="submit" href="" class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900">Realizar pedido</button>
            </div>
            </form>
        </div>
    </div>
    <script src="/js/flowbite/flowbite.js"></script>
</body>

</html>