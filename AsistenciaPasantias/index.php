<?php
/* ===========================================================
   Agenda de visitas – App PHP simple (1 archivo, JSON storage)
   Autor: Tú :)
   Requisitos: PHP 7.4+  |  Persistencia en schedule.json (en este mismo dir)
   =========================================================== */

const JSON_FILE = __DIR__ . '/schedule.json';

// ---------- Utilidades ----------
function read_data()
{
    if (!file_exists(JSON_FILE)) {
        $empty = [
            'places' => ["Arcoiris", "Homeg", "Deportes", "Deportes (P. Inclusión)", "Comando", "Jardín", "Maestranza", "Tribunal de faltas/Tránsito", "Administración"],
            'entries' => [] // cada item: id, day, time, teacher, place, group, notes, created_at
        ];
        file_put_contents(JSON_FILE, json_encode($empty, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $empty;
    }
    $raw = file_get_contents(JSON_FILE);
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = ['places' => [], 'entries' => []];
    $data['places']  = $data['places']  ?? [];
    $data['entries'] = $data['entries'] ?? [];
    return $data;
}
function write_data($data)
{
    file_put_contents(JSON_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function uuid()
{
    return bin2hex(random_bytes(8));
}
function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $days, $months)
{
    $timestamp = strtotime($date);
    $dayName = $days[date('N', $timestamp) - 1];
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    return "$dayName, $day $month $year";
}

// ---------- Manejo de acciones ----------
$data = read_data();
$days = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"];
$months = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$msg  = $_GET['msg'] ?? "";

// Agregar entrada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_entry') {
    $selectedDate = trim($_POST['day'] ?? '');
    $time    = trim($_POST['time'] ?? '');
    $teacher = trim($_POST['teacher'] ?? '');
    $placeSel = trim($_POST['place'] ?? '');
    $placeNew = trim($_POST['place_new'] ?? '');
    $group   = trim($_POST['group'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');

    if ($placeSel === '__new__' && $placeNew !== '') {
        if (!in_array($placeNew, $data['places'], true)) {
            $data['places'][] = $placeNew;
        }
        $place = $placeNew;
    } else {
        $place = $placeSel;
    }

    // Convertir la fecha seleccionada a nombre de día
    if ($selectedDate) {
        $timestamp = strtotime($selectedDate);
        $dayName = $days[date('N', $timestamp) - 1];
    }

    // Validaciones mínimas
    if ($selectedDate && $time && $teacher && $place) {
        // Limitar a 5 entradas por día
        $countDay = 0;
        foreach ($data['entries'] as $e) if (($e['date'] ?? date('Y-m-d', strtotime($e['day']))) === $selectedDate) $countDay++;
        if ($countDay >= 5) {
            // Redirigir para evitar reenvío del formulario al recargar
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode("⚠️ Límite de 5 visitas por día alcanzado para $selectedDate."));
            exit;
        } else {
            $entry = [
                'id'        => uuid(),
                'day'       => $dayName,
                'date'      => $selectedDate,
                'time'      => $time,
                'teacher'   => $teacher,
                'place'     => $place,
                'group'     => $group !== '' ? $group : ($place === 'Arcoiris' ? '— GRUPO ARCOIRIS —' : ''),
                'notes'     => $notes,
                'completed' => false,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $data['entries'][] = $entry;
            write_data($data);
            // Redirigir para evitar reenvío del formulario al recargar
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode("✅ Visita agregada."));
            exit;
        }
    } else {
        // Redirigir para evitar reenvío del formulario al recargar
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode("⚠️ Día, Horario, Docente y Lugar son obligatorios."));
        exit;
    }
}

// Marcar/desmarcar visita como completada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_completed') {
    $id = trim($_POST['id'] ?? '');
    $completed = isset($_POST['completed']);
    $updatedNotes = trim($_POST['updated_notes'] ?? '');

    foreach ($data['entries'] as &$entry) {
        if ($entry['id'] === $id) {
            $entry['completed'] = $completed;
            if ($completed && $updatedNotes !== '') {
                $entry['notes'] = $updatedNotes;
                $entry['completed_date'] = date('Y-m-d H:i:s');
            } elseif (!$completed) {
                // Si se desmarca, mantener las observaciones como estaban
                unset($entry['completed_date']);
            }
            break;
        }
    }
    unset($entry);
    write_data($data);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Borrar entrada
if (($_GET['action'] ?? '') === 'del' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $data['entries'] = array_values(array_filter($data['entries'], fn($e) => $e['id'] !== $id));
    write_data($data);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Agregar lugar manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_place') {
    $p = trim($_POST['place_new_alone'] ?? '');
    if ($p !== '' && !in_array($p, $data['places'], true)) {
        $data['places'][] = $p;
        write_data($data);
        // Redirigir para evitar reenvío del formulario al recargar
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode("✅ Lugar agregado: $p"));
        exit;
    } else {
        // Redirigir para evitar reenvío del formulario al recargar
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode("⚠️ Ingresá un lugar que no exista."));
        exit;
    }
}

// Export Excel real con formato profesional
if (($_GET['action'] ?? '') === 'export_csv') {
    $filename = 'Agenda_Visitas_Pasantias_' . date('Y-m-d_H-i-s') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    // Calcular estadísticas
    $totalVisitas = count($data['entries']);
    $visitasCompletadas = count(array_filter($data['entries'], fn($e) => $e['completed'] ?? false));
    $visitasPendientes = $totalVisitas - $visitasCompletadas;
    $porcentajeCompletadas = $totalVisitas > 0 ? round(($visitasCompletadas / $totalVisitas) * 100, 1) : 0;
    $lugaresUnicos = count(array_unique(array_column($data['entries'], 'place')));
    $docentesUnicos = count(array_unique(array_column($data['entries'], 'teacher')));

    // Ordenar datos por fecha
    usort($data['entries'], function ($a, $b) {
        $dateA = $a['date'] ?? date('Y-m-d', strtotime($a['day']));
        $dateB = $b['date'] ?? date('Y-m-d', strtotime($b['day']));
        return strcmp($dateA, $dateB);
    });

    echo '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 
<Styles>
 <Style ss:ID="titulo">
  <Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#FFFFFF"/>
  <Interior ss:Color="#2F5233" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  <Borders>
   <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
   <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2"/>
   <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2"/>
   <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
  </Borders>
 </Style>
 
 <Style ss:ID="info">
  <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#2F5233"/>
  <Interior ss:Color="#E2EFDA" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  <Borders>
   <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
  </Borders>
 </Style>
 
 <Style ss:ID="encabezado">
  <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
  <Interior ss:Color="#70AD47" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  <Borders>
   <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
  </Borders>
 </Style>
 
 <Style ss:ID="dato">
  <Font ss:FontName="Calibri" ss:Size="10"/>
  <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
  <Borders>
   <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
  </Borders>
 </Style>
 
 <Style ss:ID="datoCentro">
  <Font ss:FontName="Calibri" ss:Size="10"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  <Borders>
   <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
  </Borders>
 </Style>
 
 <Style ss:ID="completada">
  <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#006100"/>
  <Interior ss:Color="#C6EFCE" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  <Borders>
   <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
  </Borders>
 </Style>
 
 <Style ss:ID="pendiente">
  <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#9C0006"/>
  <Interior ss:Color="#FFC7CE" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  <Borders>
   <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
  </Borders>
 </Style>
 
 <Style ss:ID="estadistica">
  <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>
  <Interior ss:Color="#F8F9FA" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  <Borders>
   <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
   <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
  </Borders>
 </Style>
</Styles>

<Worksheet ss:Name="Agenda Visitas">
<Table>

<!-- TÍTULO PRINCIPAL -->
<Row ss:Height="25">
 <Cell ss:MergeAcross="8" ss:StyleID="titulo">
  <Data ss:Type="String">AGENDA DE VISITAS - PASANTÍAS</Data>
 </Cell>
</Row>

<!-- INFORMACIÓN DEL REPORTE -->
<Row ss:Height="20">
 <Cell ss:MergeAcross="8" ss:StyleID="info">
  <Data ss:Type="String">Exportado el: ' . date('d/m/Y H:i:s') . ' | Total de Registros: ' . $totalVisitas . '</Data>
 </Cell>
</Row>

<!-- FILA VACÍA -->
<Row ss:Height="15"></Row>

<!-- ENCABEZADOS -->
<Row ss:Height="22">
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">FECHA COMPLETA</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">DÍA</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">HORA</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">DOCENTE</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">LUGAR</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">GRUPO</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">OBSERVACIONES</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">ESTADO</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">FECHA REGISTRO</Data></Cell>
</Row>';

    // DATOS DE VISITAS
    foreach ($data['entries'] as $e) {
        $fecha = $e['date'] ?? date('Y-m-d', strtotime($e['day']));
        $fechaFormateada = formatDate($fecha, $days, $months);
        $completed = ($e['completed'] ?? false);
        $estado = $completed ? 'COMPLETADA' : 'PENDIENTE';
        $estadoStyle = $completed ? 'completada' : 'pendiente';
        $fechaRegistro = date('d/m/Y H:i', strtotime($e['created_at']));

        echo '<Row ss:Height="18">
 <Cell ss:StyleID="dato"><Data ss:Type="String">' . htmlspecialchars($fechaFormateada) . '</Data></Cell>
 <Cell ss:StyleID="datoCentro"><Data ss:Type="String">' . htmlspecialchars(strtoupper($e['day'])) . '</Data></Cell>
 <Cell ss:StyleID="datoCentro"><Data ss:Type="String">' . htmlspecialchars($e['time']) . '</Data></Cell>
 <Cell ss:StyleID="dato"><Data ss:Type="String">' . htmlspecialchars($e['teacher']) . '</Data></Cell>
 <Cell ss:StyleID="dato"><Data ss:Type="String">' . htmlspecialchars($e['place']) . '</Data></Cell>
 <Cell ss:StyleID="dato"><Data ss:Type="String">' . htmlspecialchars($e['group']) . '</Data></Cell>
 <Cell ss:StyleID="dato"><Data ss:Type="String">' . htmlspecialchars($e['notes'] ?: 'Sin observaciones') . '</Data></Cell>
 <Cell ss:StyleID="' . $estadoStyle . '"><Data ss:Type="String">' . $estado . '</Data></Cell>
 <Cell ss:StyleID="datoCentro"><Data ss:Type="String">' . $fechaRegistro . '</Data></Cell>
</Row>';
    }

    echo '
<!-- SEPARADOR -->
<Row ss:Height="15"></Row>
<Row ss:Height="15"></Row>

<!-- TÍTULO ESTADÍSTICAS -->
<Row ss:Height="25">
 <Cell ss:MergeAcross="5" ss:StyleID="titulo">
  <Data ss:Type="String">RESUMEN ESTADÍSTICO</Data>
 </Cell>
</Row>

<!-- ENCABEZADOS ESTADÍSTICAS -->
<Row ss:Height="20">
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">Total Visitas</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">Completadas</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">Pendientes</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">% Avance</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">Lugares Únicos</Data></Cell>
 <Cell ss:StyleID="encabezado"><Data ss:Type="String">Docentes Únicos</Data></Cell>
</Row>

<!-- VALORES ESTADÍSTICAS -->
<Row ss:Height="20">
 <Cell ss:StyleID="estadistica"><Data ss:Type="Number">' . $totalVisitas . '</Data></Cell>
 <Cell ss:StyleID="completada"><Data ss:Type="Number">' . $visitasCompletadas . '</Data></Cell>
 <Cell ss:StyleID="pendiente"><Data ss:Type="Number">' . $visitasPendientes . '</Data></Cell>
 <Cell ss:StyleID="estadistica"><Data ss:Type="String">' . $porcentajeCompletadas . '%</Data></Cell>
 <Cell ss:StyleID="estadistica"><Data ss:Type="Number">' . $lugaresUnicos . '</Data></Cell>
 <Cell ss:StyleID="estadistica"><Data ss:Type="Number">' . $docentesUnicos . '</Data></Cell>
</Row>

</Table>
</Worksheet>
</Workbook>';

    exit;
}

// Convertir fechas a nombres de días en las entradas existentes y asegurar campo completed
foreach ($data['entries'] as &$entry) {
    // Asegurar que existe el campo completed
    if (!isset($entry['completed'])) {
        $entry['completed'] = false;
    }

    // Si day es una fecha, convertir a nombre de día y asegurar que date esté configurado
    if (!in_array($entry['day'], $days, true)) {
        $timestamp = strtotime($entry['day']);
        $entry['date'] = $entry['day']; // Mantener la fecha original
        $entry['day'] = $days[date('N', $timestamp) - 1] ?? $entry['day'];
    } else {
        // Si day ya es un nombre de día, asegurar que date esté configurado
        if (empty($entry['date'])) {
            $entry['date'] = date('Y-m-d'); // Fecha por defecto si no existe
        }
    }
}
unset($entry); // Liberar referencia

// Eliminar duplicados basado en fecha, hora, docente y lugar
$unique_entries = [];
$seen = [];
foreach ($data['entries'] as $entry) {
    $key = $entry['date'] . '|' . $entry['time'] . '|' . $entry['teacher'] . '|' . $entry['place'];
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $unique_entries[] = $entry;
    }
}
$data['entries'] = $unique_entries;
write_data($data);

// Ordenar entradas por fecha -> hora
usort($data['entries'], function ($a, $b) {
    $dateA = $a['date'] ?? date('Y-m-d', strtotime($a['day']));
    $dateB = $b['date'] ?? date('Y-m-d', strtotime($b['day']));
    $d = strcmp($dateA, $dateB);
    if ($d !== 0) return $d;
    return strcmp($a['time'], $b['time']);
});

// Inicializar `$byDate` correctamente
$byDate = [];
foreach ($data['entries'] as $e) {
    $dateKey = $e['date'] ?? date('Y-m-d', strtotime($e['day']));
    if (!isset($byDate[$dateKey])) {
        $byDate[$dateKey] = [];
    }
    $byDate[$dateKey][] = $e;
}

// Ordenar visitas por fecha
ksort($byDate);
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Agenda de Visitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #f7f9fc;
        }

        .card {
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
        }

        .tag {
            display: inline-block;
            padding: .15rem .5rem;
            border-radius: 999px;
            background: #eef2ff;
            color: #1d4ed8;
            font-size: .8rem;
        }

        .limit-badge {
            font-size: .8rem;
            color: #666;
        }

        .grid-day {
            min-height: 180px;
        }

        .entry {
            border-left: 4px solid #0b5394;
            background: #fff;
            padding: .5rem .75rem;
            border-radius: 10px;
            margin-bottom: .5rem;
        }

        .entry .small {
            color: #6b7280;
        }
    </style>
</head>

<body class="py-4">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 m-0">Agenda de Visitas - Pasantías</h1>
            <div class="d-flex gap-2">
                <a class="btn btn-success d-flex align-items-center gap-2" href="?action=export_csv">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
                        <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z" />
                    </svg>
                    Exportar a Excel
                </a>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-info"><?= h($msg) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5">Nueva visita</h2>
                        <form method="post" class="row g-3">
                            <input type="hidden" name="action" value="add_entry">
                            <div class="col-12">
                                <label class="form-label">Día</label>
                                <input type="date" class="form-control" name="day" required>
                                <div class="form-text">Máximo 5 visitas por día.</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Horario</label>
                                <input type="text" class="form-control" name="time" placeholder="Ej: 14:30–15:10" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Docente</label>
                                <input type="text" class="form-control" name="teacher" placeholder="Apellido" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Lugar</label>
                                <select class="form-select" name="place" id="place" required onchange="toggleNewPlace(this)">
                                    <option value="">— Elegí —</option>
                                    <?php foreach ($data['places'] as $p): ?>
                                        <option value="<?= h($p) ?>"><?= $p ?></option>
                                    <?php endforeach; ?>
                                    <option value="__new__">+ Agregar lugar nuevo…</option>
                                </select>
                            </div>
                            <div class="col-12" id="newPlaceBox" style="display:none;">
                                <input type="text" class="form-control" name="place_new" placeholder="Nuevo lugar (se guardará en la lista)">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Grupo / Estudiantes</label>
                                <input type="text" class="form-control" name="group" placeholder="Ej: — GRUPO ARCOIRIS — o lista de nombres">
                                <div class="form-text">Si el lugar es Arcoiris y dejás vacío, se completa con “— GRUPO ARCOIRIS —”.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Detalles breves"></textarea>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-success">Agregar visita</button>
                            </div>
                        </form>

                        <hr class="my-4">
                        <h2 class="h6">Agregar lugar manual</h2>
                        <form method="post" class="row g-2">
                            <input type="hidden" name="action" value="add_place">
                            <div class="col-8">
                                <input type="text" class="form-control" name="place_new_alone" placeholder="Nombre del lugar">
                            </div>
                            <div class="col-4 d-grid">
                                <button class="btn btn-outline-primary">Agregar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5 d-flex align-items-center justify-content-between">
                            Visitas realizadas y programadas
                            <span class="limit-badge"><?= count($data['entries']) ?> visitas</span>
                        </h2>
                        <div class="row g-3">
                            <?php if (!empty($byDate)): ?>
                                <?php foreach ($byDate as $date => $entries): ?>
                                    <div class="col-12">
                                        <div class="p-3 border rounded bg-white">
                                            <?php
                                            $formattedDate = formatDate($date, $days, $months);
                                            ?>
                                            <h6 class="mb-3 text-primary"><?= h($formattedDate) ?></h6>
                                            <?php foreach ($entries as $e): ?>
                                                <div class="entry mb-2 <?= ($e['completed'] ?? false) ? 'opacity-75' : '' ?>">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <div><strong><?= h($e['time']) ?></strong> · <?= h($e['teacher']) ?></div>
                                                            <div class="small">Lugar: <?= h($e['place']) ?></div>
                                                            <?php if ($e['group'] !== ''): ?>
                                                                <div class="small">Grupo/Estudiantes: <?= h($e['group']) ?></div>
                                                            <?php endif; ?>
                                                            <?php if ($e['notes'] !== ''): ?>
                                                                <div class="small">Observaciones: <?= h($e['notes']) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="ms-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="completed_<?= h($e['id']) ?>"
                                                                    <?= ($e['completed'] ?? false) ? 'checked' : '' ?>
                                                                    onchange="toggleCompleted('<?= h($e['id']) ?>', this.checked, '<?= h($e['notes']) ?>')">
                                                                <label class="form-check-label small text-muted" for="completed_<?= h($e['id']) ?>">
                                                                    Realizada
                                                                </label>
                                                            </div>
                                                            <?php if (($e['completed'] ?? false) && !empty($e['completed_date'])): ?>
                                                                <div class="mt-2 p-2 bg-success bg-opacity-10 rounded small">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div class="flex-grow-1">
                                                                            <div class="text-success fw-bold mb-1">✅ Completada</div>
                                                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                                                <?= date('d/m/Y H:i', strtotime($e['completed_date'])) ?>
                                                                            </div>
                                                                        </div>
                                                                        <button class="btn btn-sm btn-outline-secondary ms-2"
                                                                            onclick="editarObservaciones('<?= h($e['id']) ?>', '<?= h($e['notes']) ?>')"
                                                                            title="Editar observaciones">
                                                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                                                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L13.707 6H14.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5V4.293L10.854 5.439a.5.5 0 0 1-.708-.708L12.293 2.584 15 5.293 5 15H1a1 1 0 0 1-1-1V10L10.293 0z" />
                                                                            </svg>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <button class="btn btn-sm btn-outline-danger" onclick="confirmarEliminacion('<?= h($e['id']) ?>', '<?= h($e['teacher']) ?>', '<?= h($e['place']) ?>', '<?= h($e['time']) ?>')">Eliminar</button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="text-muted fst-italic">No hay visitas programadas.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center mt-4 text-muted">
            <small>Los datos se guardan en <code>schedule.json</code> (en este directorio).</small>
        </footer>
    </div>

    <script>
        function toggleNewPlace(sel) {
            const box = document.getElementById('newPlaceBox');
            box.style.display = (sel.value === '__new__') ? 'block' : 'none';
        }

        function confirmarEliminacion(id, docente, lugar, hora) {
            Swal.fire({
                title: '¿Estás seguro?',
                html: `
                    <div class="text-start">
                        <p><strong>Se eliminará permanentemente esta visita:</strong></p>
                        <ul class="list-unstyled ms-3">
                            <li><i class="text-primary">👨‍🏫</i> <strong>Docente:</strong> ${docente}</li>
                            <li><i class="text-success">📍</i> <strong>Lugar:</strong> ${lugar}</li>
                            <li><i class="text-warning">🕒</i> <strong>Horario:</strong> ${hora}</li>
                        </ul>
                        <div class="alert alert-warning mt-3 mb-0">
                            <small><i class="text-danger">⚠️</i> Esta acción no se puede deshacer</small>
                        </div>
                    </div>
                `,
                icon: 'warning',
                iconColor: '#d33',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    popup: 'rounded-3',
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar mensaje de eliminación en progreso
                    Swal.fire({
                        title: 'Eliminando...',
                        text: 'Por favor espera',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Redirigir para eliminar
                    setTimeout(() => {
                        window.location.href = `?action=del&id=${id}`;
                    }, 500);
                }
            });
        }

        function toggleCompleted(id, isChecked, currentNotes) {
            if (isChecked) {
                // Si se marca como completada, mostrar diálogo para editar observaciones
                Swal.fire({
                    title: '¡Visita completada!',
                    html: `
                        <div class="text-start">
                            <p class="mb-3">¿Deseas actualizar las observaciones de esta visita?</p>
                            <textarea id="notesInput" class="form-control" rows="4">${currentNotes}</textarea>
                            <div class="form-text mt-2">
                                <small class="text-muted">Puedes mantener las observaciones originales o ampliarlas con detalles de la visita realizada.</small>
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    iconColor: '#28a745',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Mantener observaciones',
                    reverseButtons: false,
                    customClass: {
                        popup: 'rounded-3',
                        confirmButton: 'btn btn-success me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        return document.getElementById('notesInput').value;
                    }
                }).then((result) => {
                    let notes = currentNotes;
                    if (result.isConfirmed) {
                        notes = result.value || '';
                    } else if (result.isDismissed && result.dismiss !== Swal.DismissReason.cancel) {
                        // Si se cierra el diálogo sin elegir, desmarcar el checkbox
                        document.getElementById(`completed_${id}`).checked = false;
                        return;
                    }

                    // Enviar formulario con las observaciones
                    submitCompletedForm(id, true, notes);
                });
            } else {
                // Si se desmarca, preguntar confirmación
                Swal.fire({
                    title: '¿Marcar como pendiente?',
                    text: 'La visita se marcará como no realizada pero se mantendrán las observaciones.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, marcar pendiente',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'rounded-3',
                        confirmButton: 'btn btn-warning me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitCompletedForm(id, false, currentNotes);
                    } else {
                        // Si cancela, volver a marcar el checkbox
                        document.getElementById(`completed_${id}`).checked = true;
                    }
                });
            }
        }

        function submitCompletedForm(id, completed, notes) {
            // Crear formulario dinámico y enviarlo
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'toggle_completed';
            form.appendChild(actionInput);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            form.appendChild(idInput);

            if (completed) {
                const completedInput = document.createElement('input');
                completedInput.type = 'hidden';
                completedInput.name = 'completed';
                completedInput.value = '1';
                form.appendChild(completedInput);
            }

            const notesInput = document.createElement('input');
            notesInput.type = 'hidden';
            notesInput.name = 'updated_notes';
            notesInput.value = notes;
            form.appendChild(notesInput);

            document.body.appendChild(form);
            form.submit();
        }

        function editarObservaciones(id, observacionesActuales) {
            Swal.fire({
                title: 'Editar observaciones',
                html: `
                    <div class="text-start">
                        <p class="mb-3">Modifica las observaciones de esta visita:</p>
                        <textarea id="editNotesInput" class="form-control" rows="4">${observacionesActuales}</textarea>
                        <div class="form-text mt-2">
                            <small class="text-muted">Las observaciones se actualizarán inmediatamente.</small>
                        </div>
                    </div>
                `,
                icon: 'info',
                iconColor: '#17a2b8',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                reverseButtons: false,
                customClass: {
                    popup: 'rounded-3',
                    confirmButton: 'btn btn-info me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                preConfirm: () => {
                    return document.getElementById('editNotesInput').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Actualizar observaciones
                    submitCompletedForm(id, true, result.value);
                }
            });

            // Seleccionar todo el texto al abrir
            setTimeout(() => {
                const textarea = document.getElementById('editNotesInput');
                if (textarea) {
                    textarea.select();
                    textarea.focus();
                }
            }, 300);
        }
    </script>
</body>

</html>