<?php
// Dentro del bucle foreach que itera sobre las facturas
$articulo_id = $factura->getArticuloId();
$oferta_id = $pdo->prepare("SELECT oferta_id FROM articulos WHERE id = :articulo_id");
$oferta_id->execute([':articulo_id' => $articulo_id]);
$oferta_id = $oferta_id->fetchColumn();

if ($oferta_id) {
    $oferta = $pdo->prepare("SELECT descuento FROM ofertas WHERE id = :oferta_id");
    $oferta->execute([':oferta_id' => $oferta_id]);
    $descuento_oferta = $oferta->fetchColumn();
} else {
    $descuento_oferta = 0;
}

$total_factura = $factura->getTotal();

if ($cupon_factura == null) {
    $total_con_descuento = round(($total_factura * 1.21), 2);
} elseif ($cupon_factura) {
    // Cálculo con descuento de cupón
    $descuento_cupon = 0; // Aquí debes obtener el descuento del cupón, supongamos que se guarda en la variable $descuento_cupon
    $total_con_descuento = round(($total_factura - ($total_factura * $descuento_cupon / 100)) * 1.21, 2);
} elseif ($descuento_oferta) {
    $total_con_descuento = round(($total_factura - ($total_factura * $descuento_oferta / 100)) * 1.21, 2);
} else {
    $total_con_descuento = $total_factura;
}

echo $total_con_descuento;
?>
