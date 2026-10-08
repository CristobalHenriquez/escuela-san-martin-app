<?php
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require_once __DIR__.'/vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

require 'db.php';

// Obtener datos
// 1. Capital actual
$stmt = $db->query("SELECT 
    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN efectivo ELSE -efectivo END), 0) as efectivo_actual,
    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN transferencia ELSE -transferencia END), 0) as transferencia_actual
FROM capital_liquido");
$capital_actual = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Valor inventario
$stmt = $db->query("SELECT COALESCE(SUM(stock * costo), 0) as valor_inventario FROM productos");
$inventario = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Último balance diario
$stmt = $db->query("SELECT * FROM balance_diario ORDER BY fecha DESC LIMIT 1");
$balance = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. Transacciones
$stmt = $db->query("SELECT cl.*, cp.nombre AS cuenta_nombre
FROM capital_liquido cl
LEFT JOIN cuentas_pago cp ON cp.id = cl.cuenta_id
ORDER BY cl.fecha DESC, cl.id DESC");
$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Compras
$stmt = $db->query("SELECT cm.*, cp.nombre AS cuenta_nombre
FROM compras_mercaderia cm
LEFT JOIN cuentas_pago cp ON cp.id = cm.cuenta_id
ORDER BY cm.fecha DESC, cm.id DESC");
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$row = 1;

// Título principal
$sheet->setCellValue('A'.$row, 'Control de Capital - POS San Martín 5°C');
$sheet->mergeCells('A'.$row.':F'.$row);
$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;

// Fecha de generación
$sheet->setCellValue('A'.$row, 'Generado el:');
$sheet->setCellValue('B'.$row, date('d/m/Y H:i:s'));
$sheet->getStyle('A'.$row)->getFont()->setBold(true);
$row += 2;

// Resumen general
$sheet->setCellValue('A'.$row, 'RESUMEN GENERAL');
$sheet->mergeCells('A'.$row.':F'.$row);
$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
$sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;

$resumen = [
    ['Capital líquido total', $capital_actual['efectivo_actual'] + $capital_actual['transferencia_actual']],
    ['- Efectivo', $capital_actual['efectivo_actual']],
    ['- Transferencia', $capital_actual['transferencia_actual']],
    ['Capital en productos', $inventario['valor_inventario']],
    ['Capital total', $capital_actual['efectivo_actual'] + $capital_actual['transferencia_actual'] + $inventario['valor_inventario']],
];
$sheet->fromArray($resumen, null, 'A'.$row);
for ($i = $row; $i < $row + count($resumen); $i++) {
    $sheet->getStyle('A'.$i)->getFont()->setBold(true);
    $sheet->getStyle('B'.$i)->getNumberFormat()->setFormatCode('#,##0.00');
}
$row += count($resumen) + 2;

// Balance diario (si existe)
if ($balance) {
    $sheet->setCellValue('A'.$row, 'ÚLTIMO BALANCE DIARIO');
    $sheet->mergeCells('A'.$row.':F'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    $balance_data = [
        ['Fecha', $balance['fecha']],
        ['Capital inicial efectivo', $balance['capital_inicial_efectivo']],
        ['Capital inicial transferencia', $balance['capital_inicial_transferencia']],
        ['Ventas efectivo', $balance['ventas_efectivo']],
        ['Ventas transferencia', $balance['ventas_transferencia']],
        ['Compras efectivo', $balance['compras_efectivo']],
        ['Compras transferencia', $balance['compras_transferencia']],
        ['Capital final efectivo', $balance['capital_final_efectivo']],
        ['Capital final transferencia', $balance['capital_final_transferencia']],
        ['Capital en productos', $balance['capital_productos']],
    ];
    $sheet->fromArray($balance_data, null, 'A'.$row);
    for ($i = $row; $i < $row + count($balance_data); $i++) {
        $sheet->getStyle('A'.$i)->getFont()->setBold(true);
        $sheet->getStyle('B'.$i)->getNumberFormat()->setFormatCode('#,##0.00');
    }
    $row += count($balance_data) + 2;
}

// Transacciones
$sheet->setCellValue('A'.$row, 'TRANSACCIONES DE CAPITAL');
$sheet->mergeCells('A'.$row.':F'.$row);
$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');
$sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;
$headers = ['Fecha', 'Tipo', 'Efectivo', 'Transferencia', 'Cuenta', 'Descripción', 'Comprobante', 'Registrado'];
$sheet->fromArray($headers, null, 'A'.$row);
$sheet->getStyle('A'.$row.':H'.$row)->getFont()->setBold(true);
$row++;
foreach ($transacciones as $t) {
    $sheet->fromArray([
        $t['fecha'],
        ucfirst($t['tipo']),
        $t['efectivo'],
        $t['transferencia'],
        $t['cuenta_nombre'] ?? '',
        $t['descripcion'],
        $t['comprobante_path'] ?? '',
        $t['created_at']
    ], null, 'A'.$row);
    $sheet->getStyle('C'.$row.':D'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
    $row++;
}
$row++;

// Compras de mercadería
$sheet->setCellValue('A'.$row, 'COMPRAS DE MERCADERÍA');
$sheet->mergeCells('A'.$row.':F'.$row);
$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9E79F');
$sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;
$headers = ['Fecha', 'Monto', 'Método de pago', 'Cuenta', 'Descripción', 'Comprobante', 'Registrado'];
$sheet->fromArray($headers, null, 'A'.$row);
$sheet->getStyle('A'.$row.':G'.$row)->getFont()->setBold(true);
$row++;
foreach ($compras as $c) {
    $sheet->fromArray([
        $c['fecha'],
        $c['monto'],
        ucfirst($c['metodo_pago']),
        $c['cuenta_nombre'] ?? '',
        $c['descripcion'],
        $c['comprobante_path'] ?? '',
        $c['created_at']
    ], null, 'A'.$row);
    $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
    $row++;
}

// Ajustar anchos
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="capital_'.date('Y-m-d_H-i-s').'.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit; 
