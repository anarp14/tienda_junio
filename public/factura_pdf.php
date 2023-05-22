<?php
session_start();

use App\Tablas\Factura;

require '../vendor/autoload.php';

if (!($usuario = \App\Tablas\Usuario::logueado())) {
    return volver();
}

$id = obtener_get('id');


if (!isset($id)) {
    return volver();
}

$pdo = conectar();

$factura = Factura::obtener($id, $pdo);

$cupon_factura = $factura->getCupon_id();

if (!isset($factura)) {
    return volver();
}

if ($factura->getUsuarioId() != $usuario->id) {
    return volver();
}

$filas_tabla = '';
$total = 0;

$metodo_pago = $factura->getMetodo_pago();

foreach ($factura->getLineas($pdo) as $linea) {
    $articulo = $linea->getArticulo();
    $codigo = $articulo->getCodigo();
    $descripcion = $articulo->getDescripcion();
    $cantidad = $linea->getCantidad();
    $precio_antiguo = dinero($articulo->getPrecio());
    $precio = $articulo->getPrecio();

    if (isset($cupon_factura)) {
        $pdo = conectar();
        $sent = $pdo->prepare("SELECT * FROM cupones WHERE id = :cupon_id");
        $sent->execute([':cupon_id' => $cupon_factura]);
        foreach ($sent as $cupon) :
            $descuento = hh($cupon['descuento']);
            $cupon_descuento= $cupon['cupon'];
            $precio= $precio - ($precio * (hh($cupon['descuento']) / 100));
            $importe =($cantidad * $precio);
            $precio = dinero($precio);
           
        endforeach;
    } else {
        $importe =  dinero($cantidad * $precio);
        $precio = dinero($precio);
    }


    $filas_tabla .= <<<EOF
        <tr>
            <td>$codigo</td>
            <td>$metodo_pago </td>
            <td>$descripcion</td>
            <td>$cantidad</td>
            <td><del>$precio_antiguo </del></td>
            <td>$precio</td>
            <td>$importe</td>
        </tr>
    EOF;
}
$total += round($importe*1.21, 2); //total de abajo

$res = <<<EOT
<p>Factura número: {$factura->id}</p>

<table border="1" class="font-sans mx-auto">
    <tr>
        <th>Código</th>
        <th>Método de pago</th>
        <th>Descripción</th>
        <th>Cantidad</th>
        <th>Precio</th>
        <th>Precio rebajado</th>
        <th>Importe</th>
    </tr>
    <tbody>
        $filas_tabla
    </tbody>
</table>

<p>Total: $total €</p>
<p>Cupon utilizado: $cupon_descuento</p>
EOT;

// Create an instance of the class:
$mpdf = new \Mpdf\Mpdf();

// Write some HTML code:
$mpdf->WriteHTML(file_get_contents('css/output.css'), \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($res, \Mpdf\HTMLParserMode::HTML_BODY);

// Output a PDF file directly to the browser
$mpdf->Output();
