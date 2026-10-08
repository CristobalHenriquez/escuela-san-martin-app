<?php
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

// Obtener todos los productos
$stmt = $db->prepare("SELECT * FROM productos ORDER BY nombre");
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_productos = count($productos);
$total_stock = 0;
$total_valor_stock = 0;
$productos_sin_stock = 0;

foreach ($productos as $p) {
    $total_stock += $p['stock'];
    $total_valor_stock += ($p['stock'] * $p['costo']);
    if ($p['stock'] <= 0) {
        $productos_sin_stock++;
    }
}

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
    $sheet->getColumnDimension('A')->setWidth(15);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(20);
    
    $row = 1;
    
    // Título principal
    $sheet->setCellValue('A'.$row, 'POS San Martín 5°C');
    $sheet->mergeCells('A'.$row.':F'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    
    // Subtítulo
    $sheet->setCellValue('A'.$row, 'Inventario de Productos');
    $sheet->mergeCells('A'.$row.':F'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    
    // Fecha de generación
    $sheet->setCellValue('A'.$row, 'Generado el:');
    $sheet->setCellValue('B'.$row, date('d/m/Y H:i:s'));
    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
    $row += 2;
    
    // Resumen
    $sheet->setCellValue('A'.$row, 'RESUMEN DEL INVENTARIO');
    $sheet->mergeCells('A'.$row.':F'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    
    $resumen_data = [
        ['Total de productos', $total_productos],
        ['Total de unidades en stock', $total_stock],
        ['Valor total del inventario', $total_valor_stock],
        ['Productos sin stock', $productos_sin_stock],
    ];
    
    $sheet->fromArray($resumen_data, null, 'A'.$row);
    
    // Aplicar formato a la columna B
    for ($i = $row; $i < $row + count($resumen_data); $i++) {
        if ($i == $row + 2) { // Valor total del inventario
            $sheet->getStyle('B'.$i)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->getStyle('A'.$i)->getFont()->setBold(true);
    }
    
    $row += count($resumen_data) + 2;
    
    // Encabezados de productos
    $headers = ['Referencia', 'Producto', 'Stock', 'Precio Venta', 'Costo', 'Valor Stock'];
    $sheet->fromArray($headers, null, 'A'.$row);
    $sheet->getStyle('A'.$row.':F'.$row)->getFont()->setBold(true);
    $sheet->getStyle('A'.$row.':F'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    $sheet->getStyle('A'.$row.':F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    
    // Datos de productos
    foreach($productos as $p) {
        $valor_stock = $p['stock'] * $p['costo'];
        $sheet->fromArray([
            $p['ref'],
            $p['nombre'],
            $p['stock'],
            $p['precio'],
            $p['costo'],
            $valor_stock
        ], null, 'A'.$row);
        
        // Aplicar formato de moneda
        $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Resaltar productos sin stock
        if ($p['stock'] <= 0) {
            $sheet->getStyle('A'.$row.':F'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE6E6');
        } elseif ($p['stock'] <= 5) {
            $sheet->getStyle('A'.$row.':F'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2E6');
        }
        
        $row++;
    }
    
    // Totales
    $sheet->setCellValue('A'.$row, 'TOTALES');
    $sheet->mergeCells('A'.$row.':C'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
    $sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
    
    $sheet->setCellValue('D'.$row, '=SUM(D'.($row-$total_productos-1).':D'.($row-1).')');
    $sheet->setCellValue('E'.$row, '=SUM(E'.($row-$total_productos-1).':E'.($row-1).')');
    $sheet->setCellValue('F'.$row, '=SUM(F'.($row-$total_productos-1).':F'.($row-1).')');
    
    $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('D'.$row.':F'.$row)->getFont()->setBold(true);
    
    // Aplicar bordes a toda la hoja
    $sheet->getStyle('A1:F'.($row))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="inventario_'.date('Y-m-d_H-i-s').'.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Si no hay PhpSpreadsheet, exportar como CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="inventario_'.date('Y-m-d_H-i-s').'.csv"');
$out = fopen('php://output', 'w');

fputcsv($out, ['POS San Martín 5°C']);
fputcsv($out, ['Inventario de Productos']);
fputcsv($out, ['Generado el:', date('d/m/Y H:i:s')]);
fputcsv($out, []);
fputcsv($out, ['RESUMEN DEL INVENTARIO']);
fputcsv($out, ['Total de productos', $total_productos]);
fputcsv($out, ['Total de unidades en stock', $total_stock]);
fputcsv($out, ['Valor total del inventario', $total_valor_stock]);
fputcsv($out, ['Productos sin stock', $productos_sin_stock]);
fputcsv($out, []);
fputcsv($out, ['Referencia', 'Producto', 'Stock', 'Precio Venta', 'Costo', 'Valor Stock']);

foreach($productos as $p) {
    $valor_stock = $p['stock'] * $p['costo'];
    fputcsv($out, [
        $p['ref'],
        $p['nombre'],
        $p['stock'],
        $p['precio'],
        $p['costo'],
        $valor_stock
    ]);
}

fclose($out);
exit; 