<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
secretaria_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !secretaria_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Solicitud no válida. Regrese al formulario e intente nuevamente.');
}

$documento = (string) ($_POST['documento'] ?? '');
$fecha = (string) ($_POST['fecha'] ?? '');
$date = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$date || $date->format('Y-m-d') !== $fecha) {
    http_response_code(422);
    exit('La fecha indicada no es válida.');
}

function pdf_value(string $key, bool $required = false): string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    if ($required && $value === '') {
        http_response_code(422);
        exit('Falta completar un campo obligatorio.');
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function pdf_multiline(string $key, bool $required = false): string
{
    return nl2br(pdf_value($key, $required), false);
}

function pdf_logo_data_uri(): string
{
    $path = __DIR__ . '/../assets/images/logo/logo-escuela.jpg';
    return is_file($path) ? 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents($path)) : '';
}

function pdf_header(string $subtitle): string
{
    $logo = pdf_logo_data_uri();
    return '<table class="masthead"><tr><td class="brand">' . ($logo ? '<img src="' . $logo . '" class="logo" width="78" height="46">' : '') . '<div><div class="school-name">E.E.S.O. N° 225</div><div class="school-full">General José de San Martín</div><div class="school-place">Pérez · Santa Fe</div></div></td><td class="area">ÁREA<br><strong>SECRETARÍA</strong><br><span>' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</span></td></tr></table><div class="header-rule"></div>';
}

$css = '<style>@page { margin: 18mm 18mm 17mm 18mm; } body { font-family: sans-serif; color: #242424; font-size: 10.5pt; } .masthead { width: 100%; border-collapse: collapse; } .brand { vertical-align: middle; } .logo { width: 78px; height: 46px; margin-right: 12px; vertical-align: middle; } .school-name { color: #6A1B9A; font-size: 15pt; font-weight: bold; } .school-full { font-size: 10pt; font-weight: bold; } .school-place { color: #616161; font-size: 9pt; margin-top: 2px; } .area { width: 30%; text-align: right; color: #616161; font-size: 8pt; letter-spacing: 1px; line-height: 1.35; } .area strong { color: #6A1B9A; font-size: 11pt; letter-spacing: .5px; } .area span { font-size: 8pt; letter-spacing: 0; } .header-rule { height: 3px; background: #6A1B9A; margin-top: 10px; margin-bottom: 23px; } h1 { color: #6A1B9A; text-align: center; font-size: 18pt; margin: 0 0 5px; } .document-subtitle { text-align: center; color: #616161; font-size: 9pt; margin-bottom: 24px; } .meta { width: 100%; border-collapse: collapse; margin-bottom: 17px; } .meta td { border: 0.5px solid #d5d5d5; padding: 8px 9px; } .meta .label { display: block; color: #6A1B9A; font-size: 8pt; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; } .meta .value { font-size: 10.5pt; } .section { color: #6A1B9A; font-size: 11pt; font-weight: bold; border-bottom: 1.2px solid #6A1B9A; padding-bottom: 4px; margin: 14px 0 8px; } .text-box { border: 0.5px solid #d5d5d5; padding: 9px 10px; min-height: 47px; line-height: 1.45; } .signature-table { width: 100%; border-collapse: collapse; margin-top: 55px; page-break-inside: avoid; } .signature-table td { width: 33.33%; text-align: center; padding: 0 7px; vertical-align: top; } .signature-line { border-top: .7px solid #444; padding-top: 7px; font-size: 8.5pt; } .footer-note { margin-top: 18px; text-align: center; color: #777; font-size: 7.5pt; } .declaration { line-height: 1.65; text-align: justify; margin: 7px 0 16px; } .place-date { text-align: right; color: #555; margin: 8px 0 18px; } .request-signatures td { width: 50%; } </style>';

if ($documento === 'acta') {
    $actaNro = pdf_value('acta_nro');
    $ciclo = pdf_value('ciclo_lectivo', true);
    $estudiante = pdf_value('estudiante', true);
    $curso = pdf_value('curso_div', true);
    $concepto = pdf_value('concepto', true);
    $adulto = pdf_value('adulto_responsable', true);
    $docente = pdf_value('docente_administrativo');
    $motivo = pdf_multiline('motivo', true);
    $acuerdos = pdf_multiline('acuerdos_logrados');
    $observaciones = pdf_multiline('observaciones');
    $html = $css . pdf_header('Acta institucional') . '<h1>ACTA</h1><div class="document-subtitle">Registro institucional de reunión y acuerdos</div><table class="meta"><tr><td><span class="label">Acta N.º</span><br><span class="value">' . ($actaNro ?: '—') . '</span></td><td><span class="label">Ciclo lectivo</span><br><span class="value">' . $ciclo . '</span></td><td><span class="label">Fecha</span><br><span class="value">' . secretaria_date_display($fecha) . '</span></td></tr><tr><td colspan="2"><span class="label">Nombre del alumno/a</span><br><span class="value">' . $estudiante . '</span></td><td><span class="label">Curso / división</span><br><span class="value">' . $curso . '</span></td></tr><tr><td colspan="2"><span class="label">Concepto</span><br><span class="value">' . $concepto . '</span></td><td><span class="label">Adulto responsable</span><br><span class="value">' . $adulto . '</span></td></tr><tr><td colspan="2"><span class="label">Docente / administrativo</span><br><span class="value">' . ($docente ?: '—') . '</span></td><td><span class="label">Documento</span><br><span class="value">Acta institucional</span></td></tr></table><div class="section">Motivo</div><div class="text-box">' . $motivo . '</div><div class="section">Acuerdos logrados</div><div class="text-box">' . ($acuerdos ?: 'Sin acuerdos consignados.') . '</div><div class="section">Observaciones / aclaraciones</div><div class="text-box">' . ($observaciones ?: 'Sin observaciones.') . '</div><table class="signature-table"><tr><td><div class="signature-line">Firma directivo/a o docente</div></td><td><div class="signature-line">Firma adulto responsable</div></td><td><div class="signature-line">Firma estudiante</div></td></tr></table><div class="footer-note">Documento generado por el Área Secretaría · EESO N° 225 General José de San Martín</div>';
    $filename = 'acta-' . ($actaNro ?: date('Ymd')) . '.pdf';
} elseif ($documento === 'reincorporacion') {
    $anio = pdf_value('anio_div', true);
    $inasistencias = pdf_value('inasistencias', true);
    $adulto = pdf_value('adulto_responsable', true);
    $vinculo = pdf_value('vinculo', true);
    $estudiante = pdf_value('estudiante', true);
    $fechaConsta = trim((string) ($_POST['fecha_consta'] ?? ''));
    $fechaConstaDisplay = $fechaConsta ? secretaria_date_display($fechaConsta) : '—';
    $motivo = pdf_multiline('motivo', true);
    $html = $css . pdf_header('Solicitud escolar') . '<h1>SOLICITUD DE REINCORPORACIÓN</h1><div class="document-subtitle">Presentación ante la Secretaría de la institución</div><div class="place-date">Pérez, ' . secretaria_date_display($fecha) . '</div><div class="declaration">En presencia del/de la Sr./a. <strong>' . $adulto . '</strong>, en carácter de <strong>' . $vinculo . '</strong>, adulto responsable del/de la estudiante <strong>' . $estudiante . '</strong>, correspondiente a <strong>' . $anio . '</strong>, se deja constancia de que registra <strong>' . $inasistencias . '</strong> inasistencias' . ($fechaConsta ? ' desde el ' . $fechaConstaDisplay : '') . ', y se solicita su reincorporación.</div><div class="section">Motivo de la solicitud</div><div class="text-box">' . $motivo . '</div><table class="signature-table request-signatures"><tr><td><div class="signature-line">Firma padre, madre, tutor/a o adulto responsable</div></td><td><div class="signature-line">Aclaración y DNI</div></td></tr></table><div class="footer-note">Documento generado por el Área Secretaría · EESO N° 225 General José de San Martín</div>';
    $filename = 'solicitud-reincorporacion-' . date('Ymd') . '.pdf';
} else {
    http_response_code(422);
    exit('Tipo de documento no válido.');
}

try {
    require_once secretaria_pdf_autoload();
    $mpdf = new \Mpdf\Mpdf(['format' => 'A4', 'tempDir' => sys_get_temp_dir()]);
    $mpdf->SetTitle($documento === 'acta' ? 'Acta institucional' : 'Solicitud de reincorporación');
    $mpdf->SetAuthor('EESO N° 225 General José de San Martín');
    $mpdf->WriteHTML($html);
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
} catch (Throwable $exception) {
    http_response_code(500);
    error_log('Secretaria PDF: ' . $exception->getMessage());
    exit('No se pudo generar el PDF. Verifique la instalación de la librería y vuelva a intentar.');
}
