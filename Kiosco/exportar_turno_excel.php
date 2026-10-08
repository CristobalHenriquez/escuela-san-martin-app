<?php
// Si está disponible, importar PhpSpreadsheet al inicio
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require_once __DIR__.'/vendor/autoload.php';
}

// Importar clases de PhpSpreadsheet si están disponibles
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

require 'db.php';
$id = intval($_GET['id'] ?? 0);
if (!$id) { die('Turno no encontrado'); }

// Obtener datos del turno
$turno = $db->prepare("SELECT t.*, g.nombre AS grupo_nombre
FROM turnos t
LEFT JOIN grupos g ON g.id = t.grupo_id
WHERE t.id = ?");
$turno->execute([$id]);
$turno = $turno->fetch(PDO::FETCH_ASSOC);
if (!$turno) { die('Turno no encontrado'); }

// Obtener transacciones del turno
$stmt = $db->prepare("
    SELECT t.*, p.nombre as producto_nombre, p.precio, cp.nombre AS cuenta_nombre
    FROM transacciones t 
    JOIN productos p ON t.producto_id = p.id 
    LEFT JOIN cuentas_pago cp ON cp.id = t.cuenta_id
    WHERE t.turno_id = ? 
    ORDER BY t.fecha
");
$stmt->execute([$id]);
$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_efectivo = 0;
$total_transferencia = 0;
$total_ventas = 0;
$productos_vendidos = [];
$ventas_discriminadas = [];
foreach ($transacciones as $t) {
    if ($t['tipo'] === 'Venta') {
        $total_ventas += $t['importe'];
        if ($t['metodo_pago'] === 'Efectivo') {
            $total_efectivo += $t['importe'];
        } else {
            $total_transferencia += $t['importe'];
        }
        // Agrupar productos vendidos
        if (!isset($productos_vendidos[$t['producto_id']])) {
            $productos_vendidos[$t['producto_id']] = [
                'nombre' => $t['producto_nombre'],
                'cantidad' => 0,
                'total' => 0
            ];
        }
        $productos_vendidos[$t['producto_id']]['cantidad'] += $t['cantidad'];
        $productos_vendidos[$t['producto_id']]['total'] += $t['importe'];
        // Agrupar ventas discriminadas
        $ventas_discriminadas[] = $t;
    }
}
$diferencia_caja = $turno['saldo_cierre'] - $turno['saldo_inicial'] - $total_efectivo;

// Intentar usar PhpSpreadsheet
$phpspreadsheet_ok = false;
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        $phpspreadsheet_ok = true;
    }
}

if ($phpspreadsheet_ok) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Configurar el ancho de las columnas
    $sheet->getColumnDimension('A')->setWidth(20);
    $sheet->getColumnDimension('B')->setWidth(25);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(25);
    
    $row = 1;
    
    // Título principal
    $sheet->setCellValue('A'.$row, 'POS San Martín 5°C');
    $sheet->mergeCells('A'.$row.':F'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    
    // Subtítulo
    $sheet->setCellValue('A'.$row, 'Reporte de Turno #'.$turno['id']);
    $sheet->mergeCells('A'.$row.':F'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row += 2;
    
    // Información del turno
    $sheet->setCellValue('A'.$row, 'Apertura:');
    $sheet->setCellValue('B'.$row, date('d/m/Y H:i', strtotime($turno['fecha_apertura'])));
    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
    $row++;
    
    $sheet->setCellValue('A'.$row, 'Cierre:');
    $sheet->setCellValue('B'.$row, date('d/m/Y H:i', strtotime($turno['fecha_cierre'])));
    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
    $row++;

    $sheet->setCellValue('A'.$row, 'Grupo:');
    $sheet->setCellValue('B'.$row, (string) ($turno['grupo_nombre'] ?? 'Sin grupo'));
    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
    $row += 2;
    
    // Resumen de Caja
    $sheet->setCellValue('A'.$row, 'RESUMEN DE CAJA');
    $sheet->mergeCells('A'.$row.':F'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    
    $resumen_data = [
        ['Saldo inicial', $turno['saldo_inicial']],
        ['Ventas en efectivo', $total_efectivo],
        ['Ventas en transferencia', $total_transferencia],
        ['Total esperado en caja', $turno['saldo_inicial']+$total_efectivo],
        ['Saldo real en caja', $turno['saldo_cierre']],
        ['Diferencia', $diferencia_caja],
    ];
    
    $sheet->fromArray($resumen_data, null, 'A'.$row);
    
    // Aplicar formato de moneda a la columna B
    for ($i = $row; $i < $row + count($resumen_data); $i++) {
        $sheet->getStyle('B'.$i)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A'.$i)->getFont()->setBold(true);
    }
    
    $row += count($resumen_data) + 2;
    
    // Ventas del Turno
    $sheet->setCellValue('A'.$row, 'VENTAS DEL TURNO');
    $sheet->mergeCells('A'.$row.':E'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    
    // Encabezados de ventas
    $headers = ['Hora', 'Producto', 'Cantidad', 'Importe', 'Método', 'Cuenta'];
    $sheet->fromArray($headers, null, 'A'.$row);
    $sheet->getStyle('A'.$row.':F'.$row)->getFont()->setBold(true);
    $sheet->getStyle('A'.$row.':F'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    $row++;
    
    // Datos de ventas
    foreach($ventas_discriminadas as $v) {
        $sheet->fromArray([
            date('H:i', strtotime($v['fecha'])),
            $v['producto_nombre'],
            $v['cantidad'],
            $v['importe'],
            $v['metodo_pago'],
            $v['cuenta_nombre'] ?? ''
        ], null, 'A'.$row);
        $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;
    }
    
    $row++;
    
    // Productos Vendidos
    $sheet->setCellValue('A'.$row, 'PRODUCTOS VENDIDOS');
    $sheet->mergeCells('A'.$row.':C'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    
    // Encabezados de productos
    $headers = ['Producto', 'Cantidad', 'Total'];
    $sheet->fromArray($headers, null, 'A'.$row);
    $sheet->getStyle('A'.$row.':C'.$row)->getFont()->setBold(true);
    $sheet->getStyle('A'.$row.':C'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    $row++;
    
    // Datos de productos
    foreach($productos_vendidos as $p) {
        $sheet->fromArray([
            $p['nombre'],
            $p['cantidad'],
            $p['total']
        ], null, 'A'.$row);
        $sheet->getStyle('C'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;
    }
    
    // Aplicar bordes a toda la hoja
    $sheet->getStyle('A1:F'.($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="reporte_turno_'.$turno['id'].'.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Si no hay PhpSpreadsheet, exportar como CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="reporte_turno_'.$turno['id'].'.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['POS San Martín 5°C']);
fputcsv($out, ['Reporte de Turno #'.$turno['id']]);
fputcsv($out, []);
fputcsv($out, ['Apertura', date('d/m/Y H:i', strtotime($turno['fecha_apertura']))]);
fputcsv($out, ['Cierre', date('d/m/Y H:i', strtotime($turno['fecha_cierre']))]);
fputcsv($out, ['Grupo', $turno['grupo_nombre'] ?? 'Sin grupo']);
fputcsv($out, []);
fputcsv($out, ['Resumen de Caja']);
fputcsv($out, ['Saldo inicial', $turno['saldo_inicial']]);
fputcsv($out, ['Ventas en efectivo', $total_efectivo]);
fputcsv($out, ['Ventas en transferencia', $total_transferencia]);
fputcsv($out, ['Total esperado en caja', $turno['saldo_inicial']+$total_efectivo]);
fputcsv($out, ['Saldo real en caja', $turno['saldo_cierre']]);
fputcsv($out, ['Diferencia', $diferencia_caja]);
fputcsv($out, []);
fputcsv($out, ['Ventas del Turno']);
fputcsv($out, ['Hora','Producto','Cantidad','Importe','Método','Cuenta']);
foreach($ventas_discriminadas as $v) {
    fputcsv($out, [date('H:i', strtotime($v['fecha'])), $v['producto_nombre'], $v['cantidad'], $v['importe'], $v['metodo_pago'], $v['cuenta_nombre'] ?? '']);
}
fputcsv($out, []);
fputcsv($out, ['Productos Vendidos']);
fputcsv($out, ['Producto','Cantidad','Total']);
foreach($productos_vendidos as $p) {
    fputcsv($out, [$p['nombre'], $p['cantidad'], $p['total']]);
}
fclose($out);
exit; 
