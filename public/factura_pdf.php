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
$descuentoTotal = 0;

foreach ($factura->getLineas($pdo) as $linea) {
    $articulo = $linea->getArticulo();
    $codigo = $articulo->getCodigo();
    $descripcion = $articulo->getDescripcion();
    $cantidad = $linea->getCantidad();
    $oferta = $articulo->getOferta() ? $articulo->getOferta() : '';

    $precio = $articulo->getPrecio(); // Inicializar el precio correctamente

    if (isset($cupon_factura)) {
        $pdo = conectar();
        $sent = $pdo->prepare("SELECT * FROM cupones WHERE id = :cupon_id");
        $sent->execute([':cupon_id' => $cupon_factura]);
        foreach ($sent as $cupon) {
            $descuento = hh($cupon['descuento']);
            $cupon_descuento = $cupon['cupon'];
            $precio = $precio - (($precio * $descuento) / 100);
            $descuentoTotal += (($factura->getTotal() * $descuento) / 100);
        }
    }

    $importe = $articulo->aplicarOferta($oferta, $cantidad, $precio)['importe'];
    $ahorro = $articulo->aplicarOferta($oferta, $cantidad, $precio)['ahorro'] + $descuentoTotal;
    $total += $importe;

    $precio = dinero($precio);
    $importe = dinero($importe);
    $ahorro = dinero($ahorro);
    $iva = $total * 0.21;
    $totalConIva = dinero($total + $iva);

    $filas_tabla .= <<<EOF
        <tr>
            <td>$codigo</td>
            <td>$descripcion</td>
            <td>$cantidad</td>
            <td>$precio</td>
            <td>$importe</td>
            <td>$ahorro</td>
            <td>$oferta</td>
        </tr>
    EOF;
}

$res = <<<EOT
<p>Factura número: {$factura->id}</p>

<table border="1" class="font-sans mx-auto">
    <tr>
        <th>Código</th>
        <th>Descripción</th>
        <th>Cantidad</th>
        <th>Precio</th>
        <th>Importe</th>
        <td>Ahorro</td>
        <td>Oferta</td>
    </tr>
    <tbody>
        $filas_tabla
    </tbody>
</table>

<p>Total (+IVA 21%): $totalConIva</p>
<p>Cupón utilizado: $cupon_descuento</p>
EOT;

// Create an instance of the class:
$mpdf = new \Mpdf\Mpdf();

// Write some HTML code:
$mpdf->WriteHTML(file_get_contents('css/output.css'), \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($res, \Mpdf\HTMLParserMode::HTML_BODY);

// Output a PDF file directly to the browser
$mpdf->Output();
?>
