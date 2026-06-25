<?php
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$hoy = date('Y-m-d');
$modo = $_GET['modo'] ?? 'diario';
$modosPermitidos = ['diario', 'semanal', 'historico'];
if (!in_array($modo, $modosPermitidos, true)) {
    $modo = 'diario';
}

$parseFecha = static function (?string $valor, string $fallback): string {
    if ($valor === null || $valor === '') {
        return $fallback;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $valor);
    if (!$dt || $dt->format('Y-m-d') !== $valor) {
        return $fallback;
    }
    return $valor;
};

$fecha = $parseFecha($_GET['fecha'] ?? null, $hoy);
$semana = $parseFecha($_GET['semana'] ?? null, $hoy);
$desde = $parseFecha($_GET['desde'] ?? null, date('Y-m-01'));
$hasta = $parseFecha($_GET['hasta'] ?? null, $hoy);

$periodoDesde = $fecha;
$periodoHasta = $fecha;
$tituloPeriodo = 'Dia';

if ($modo === 'semanal') {
    $periodoDesde = get_inicio_semana_lunes($semana);
    $periodoHasta = get_fin_semana_domingo($periodoDesde);
    $tituloPeriodo = 'Semana';
} elseif ($modo === 'historico') {
    if ($desde > $hasta) {
        [$desde, $hasta] = [$hasta, $desde];
    }
    $periodoDesde = $desde;
    $periodoHasta = $hasta;
    $tituloPeriodo = 'Historico';
}

$resumen = $modo === 'diario'
    ? calcular_resumen_balance_dia($db, $periodoDesde)
    : calcular_resumen_balance_periodo($db, $periodoDesde, $periodoHasta);
$capital_inicial_efectivo = $resumen['capital_inicial_efectivo'];
$capital_inicial_transferencia = $resumen['capital_inicial_transferencia'];
$ventas = [
    'ventas_efectivo' => $resumen['ventas_efectivo'],
    'ventas_transferencia' => $resumen['ventas_transferencia'],
];
$compras = [
    'compras_efectivo' => $resumen['compras_efectivo'],
    'compras_transferencia' => $resumen['compras_transferencia'],
];
$ingresos = [
    'ingresos_efectivo' => $resumen['ingresos_efectivo'],
    'ingresos_transferencia' => $resumen['ingresos_transferencia'],
];
$egresos = [
    'egresos_efectivo' => $resumen['egresos_efectivo'],
    'egresos_transferencia' => $resumen['egresos_transferencia'],
];
$capital_final_efectivo = $resumen['capital_final_efectivo'];
$capital_final_transferencia = $resumen['capital_final_transferencia'];
$inventario = ['valor_inventario' => $resumen['capital_productos']];

$ventas_det = $db->prepare("SELECT id, fecha, importe, metodo_pago, usuario_id
FROM transacciones
WHERE DATE(fecha) BETWEEN ? AND ?
  AND tipo = 'Venta'
ORDER BY fecha DESC");
$ventas_det->execute([$periodoDesde, $periodoHasta]);
$ventas_det = $ventas_det->fetchAll(PDO::FETCH_ASSOC);

$compras_det = $db->prepare("SELECT * FROM compras_mercaderia WHERE fecha BETWEEN ? AND ? ORDER BY created_at DESC, fecha DESC");
$compras_det->execute([$periodoDesde, $periodoHasta]);
$compras_det = $compras_det->fetchAll(PDO::FETCH_ASSOC);

$ingresos_det = $db->prepare("SELECT * FROM capital_liquido WHERE fecha BETWEEN ? AND ? AND tipo = 'ingreso' ORDER BY created_at DESC, fecha DESC");
$ingresos_det->execute([$periodoDesde, $periodoHasta]);
$ingresos_det = $ingresos_det->fetchAll(PDO::FETCH_ASSOC);

$egresos_det = $db->prepare("SELECT * FROM capital_liquido WHERE fecha BETWEEN ? AND ? AND tipo = 'egreso' ORDER BY created_at DESC, fecha DESC");
$egresos_det->execute([$periodoDesde, $periodoHasta]);
$egresos_det = $egresos_det->fetchAll(PDO::FETCH_ASSOC);

$periodoEtiqueta = date('d/m/Y', strtotime($periodoDesde));
if ($periodoDesde !== $periodoHasta) {
    $periodoEtiqueta .= ' al ' . date('d/m/Y', strtotime($periodoHasta));
}
$slugPeriodo = $periodoDesde;
if ($periodoDesde !== $periodoHasta) {
    $slugPeriodo .= '_a_' . $periodoHasta;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Balance');

// --- ESTILOS ---
$headerStyle = [
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => [ 'fillType' => Fill::FILL_GRADIENT_LINEAR, 'startColor' => ['rgb' => '4f8cff'], 'endColor' => ['rgb' => '38f9d7'] ]
];
$sectionTitleStyle = [
    'font' => ['bold' => true, 'size' => 13],
    'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'e3f0fb'] ],
];
$tableHeaderStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4f8cff'] ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$tableCellStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$currencyFormat = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

$row = 1;
$sheet->mergeCells("A$row:F$row");
$sheet->setCellValue("A$row", "Balance Completo " . $tituloPeriodo . ": " . $periodoEtiqueta);
$sheet->getStyle("A$row:F$row")->applyFromArray($headerStyle);
$row += 2;

// --- RESUMEN ---
$sheet->setCellValue("A$row", "Resumen del " . $tituloPeriodo);
$sheet->getStyle("A$row")->applyFromArray($sectionTitleStyle);
$row++;
$resumen = [
    ["Capital inicial efectivo", $capital_inicial_efectivo],
    ["Capital inicial transferencia", $capital_inicial_transferencia],
    ["Ventas efectivo", $ventas['ventas_efectivo']],
    ["Ventas transferencia", $ventas['ventas_transferencia']],
    ["Compras efectivo", $compras['compras_efectivo']],
    ["Compras transferencia", $compras['compras_transferencia']],
    ["Ingresos de capital efectivo", $ingresos['ingresos_efectivo']],
    ["Ingresos de capital transferencia", $ingresos['ingresos_transferencia']],
    ["Egresos de capital efectivo", $egresos['egresos_efectivo']],
    ["Egresos de capital transferencia", $egresos['egresos_transferencia']],
    ["Capital final efectivo", $capital_final_efectivo],
    ["Capital final transferencia", $capital_final_transferencia],
    ["Valor inventario", $inventario['valor_inventario']],
];
foreach($resumen as $item) {
    $sheet->setCellValue("A$row", $item[0]);
    $sheet->setCellValue("B$row", $item[1]);
    $sheet->getStyle("A$row:B$row")->applyFromArray($tableCellStyle);
    $sheet->getStyle("B$row")->getNumberFormat()->setFormatCode($currencyFormat);
    $row++;
}
$row++;

// --- TABLA VENTAS ---
$sheet->setCellValue("A$row", "Ventas");
$sheet->getStyle("A$row")->applyFromArray($sectionTitleStyle);
$row++;
$ventasHeaders = ['ID','Fecha','Total','Método','Usuario'];
$col = 'A';
foreach($ventasHeaders as $h) {
    $sheet->setCellValue($col.$row, $h);
    $sheet->getStyle($col.$row)->applyFromArray($tableHeaderStyle);
    $col++;
}
$row++;
foreach($ventas_det as $v) {
    $sheet->setCellValue("A$row", $v['id']);
    $sheet->setCellValue("B$row", $v['fecha']);
    $sheet->setCellValue("C$row", $v['importe']);
    $sheet->setCellValue("D$row", ucfirst($v['metodo_pago']));
    $sheet->setCellValue("E$row", $v['usuario_id']);
    $sheet->getStyle("A$row:E$row")->applyFromArray($tableCellStyle);
    $sheet->getStyle("C$row")->getNumberFormat()->setFormatCode($currencyFormat);
    $row++;
}
if (count($ventas_det) == 0) {
    $sheet->setCellValue("A$row", "Sin ventas");
    $sheet->mergeCells("A$row:E$row");
    $row++;
}
$row++;

// --- TABLA COMPRAS ---
$sheet->setCellValue("A$row", "Compras");
$sheet->getStyle("A$row")->applyFromArray($sectionTitleStyle);
$row++;
$comprasHeaders = ['ID','Fecha','Monto','Método','Descripción'];
$col = 'A';
foreach($comprasHeaders as $h) {
    $sheet->setCellValue($col.$row, $h);
    $sheet->getStyle($col.$row)->applyFromArray($tableHeaderStyle);
    $col++;
}
$row++;
foreach($compras_det as $c) {
    $sheet->setCellValue("A$row", $c['id']);
    $sheet->setCellValue("B$row", $c['fecha']);
    $sheet->setCellValue("C$row", $c['monto']);
    $sheet->setCellValue("D$row", ucfirst($c['metodo_pago']));
    $sheet->setCellValue("E$row", $c['descripcion']);
    $sheet->getStyle("A$row:E$row")->applyFromArray($tableCellStyle);
    $sheet->getStyle("C$row")->getNumberFormat()->setFormatCode($currencyFormat);
    $row++;
}
if (count($compras_det) == 0) {
    $sheet->setCellValue("A$row", "Sin compras");
    $sheet->mergeCells("A$row:E$row");
    $row++;
}
$row++;

// --- TABLA INGRESOS ---
$sheet->setCellValue("A$row", "Ingresos de Capital");
$sheet->getStyle("A$row")->applyFromArray($sectionTitleStyle);
$row++;
$ingresosHeaders = ['ID','Fecha','Efectivo','Transferencia','Descripción'];
$col = 'A';
foreach($ingresosHeaders as $h) {
    $sheet->setCellValue($col.$row, $h);
    $sheet->getStyle($col.$row)->applyFromArray($tableHeaderStyle);
    $col++;
}
$row++;
foreach($ingresos_det as $i) {
    $sheet->setCellValue("A$row", $i['id']);
    $sheet->setCellValue("B$row", $i['fecha']);
    $sheet->setCellValue("C$row", $i['efectivo']);
    $sheet->setCellValue("D$row", $i['transferencia']);
    $sheet->setCellValue("E$row", $i['descripcion']);
    $sheet->getStyle("A$row:E$row")->applyFromArray($tableCellStyle);
    $sheet->getStyle("C$row:D$row")->getNumberFormat()->setFormatCode($currencyFormat);
    $row++;
}
if (count($ingresos_det) == 0) {
    $sheet->setCellValue("A$row", "Sin ingresos");
    $sheet->mergeCells("A$row:E$row");
    $row++;
}
$row++;

// --- TABLA EGRESOS ---
$sheet->setCellValue("A$row", "Egresos de Capital");
$sheet->getStyle("A$row")->applyFromArray($sectionTitleStyle);
$row++;
$egresosHeaders = ['ID','Fecha','Efectivo','Transferencia','Descripción'];
$col = 'A';
foreach($egresosHeaders as $h) {
    $sheet->setCellValue($col.$row, $h);
    $sheet->getStyle($col.$row)->applyFromArray($tableHeaderStyle);
    $col++;
}
$row++;
foreach($egresos_det as $e) {
    $sheet->setCellValue("A$row", $e['id']);
    $sheet->setCellValue("B$row", $e['fecha']);
    $sheet->setCellValue("C$row", $e['efectivo']);
    $sheet->setCellValue("D$row", $e['transferencia']);
    $sheet->setCellValue("E$row", $e['descripcion']);
    $sheet->getStyle("A$row:E$row")->applyFromArray($tableCellStyle);
    $sheet->getStyle("C$row:D$row")->getNumberFormat()->setFormatCode($currencyFormat);
    $row++;
}
if (count($egresos_det) == 0) {
    $sheet->setCellValue("A$row", "Sin egresos");
    $sheet->mergeCells("A$row:E$row");
    $row++;
}

// Ajustar anchos
foreach(range('A','F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Balance_' . $modo . '_' . $slugPeriodo . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit; 
