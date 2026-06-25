<?php
require_once __DIR__ . '/vendor/autoload.php';
require 'db.php';

// Consultar productos con stock bajo (3 o menos)
$productos = $db->query("SELECT nombre, stock, precio, costo FROM productos WHERE stock <= 3 ORDER BY stock, nombre")->fetchAll(PDO::FETCH_ASSOC);

$fecha = date('d/m/Y H:i');

$html = '<h2 style="text-align:center;">Productos en falta</h2>';
$html .= '<p>Generado: ' . $fecha . '</p>';
if (count($productos) === 0) {
    $html .= '<p style="color:green;">No hay productos con stock bajo.</p>';
} else {
    $html .= '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse; font-size:14px;">
        <thead>
            <tr style="background:#f8d7da; color:#721c24;">
                <th>Producto</th>
                <th>Stock</th>
                <th>Precio</th>
                <th>Costo</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($productos as $p) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($p['nombre']) . '</td>';
        $html .= '<td style="text-align:center;">' . $p['stock'] . '</td>';
        $html .= '<td style="text-align:right;">$' . number_format($p['precio'], 0) . '</td>';
        $html .= '<td style="text-align:right;">$' . number_format($p['costo'], 0) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
}

$mpdf = new \Mpdf\Mpdf();
$mpdf->SetTitle('Productos en falta');
$mpdf->WriteHTML($html);
$mpdf->Output('productos_en_falta.pdf', 'D');
exit; 