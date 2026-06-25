<?php

function app_bootstrap(PDO $db): void
{
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }
    $bootstrapped = true;

    $db->exec("CREATE TABLE IF NOT EXISTS grupos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS cuentas_pago (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        tipo ENUM('transferencia') NOT NULL DEFAULT 'transferencia',
        detalle VARCHAR(255) NULL,
        orden INT NOT NULL DEFAULT 0,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS semanas_operativas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        grupo_id INT NOT NULL,
        estado ENUM('planificada','activa','cerrada') NOT NULL DEFAULT 'planificada',
        observaciones VARCHAR(255) NULL,
        created_by INT NULL,
        closed_by INT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_semanas_rango (fecha_inicio, fecha_fin),
        INDEX idx_semanas_estado (estado),
        INDEX idx_semanas_grupo (grupo_id),
        CONSTRAINT fk_semanas_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE RESTRICT,
        CONSTRAINT fk_semanas_created_by FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL,
        CONSTRAINT fk_semanas_closed_by FOREIGN KEY (closed_by) REFERENCES usuarios (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    app_add_column_if_missing($db, 'turnos', 'grupo_id', 'INT NULL');
    app_add_column_if_missing($db, 'usuarios', 'grupo_id', 'INT NULL');
    app_add_column_if_missing($db, 'usuarios', 'super_admin', 'TINYINT(1) NOT NULL DEFAULT 0');
    app_add_column_if_missing($db, 'usuarios', 'session_token', 'VARCHAR(128) NULL');
    app_add_column_if_missing($db, 'usuarios', 'session_token_created_at', 'DATETIME NULL');
    app_add_column_if_missing($db, 'transacciones', 'cuenta_id', 'INT NULL');
    app_add_column_if_missing($db, 'transacciones', 'venta_uid', 'VARCHAR(64) NULL');
    app_add_column_if_missing($db, 'capital_liquido', 'cuenta_id', 'INT NULL');
    app_add_column_if_missing($db, 'capital_liquido', 'comprobante_path', 'VARCHAR(255) NULL');
    app_add_column_if_missing($db, 'capital_liquido', 'comprobante_tipo', "ENUM('webp','pdf') NULL");
    app_add_column_if_missing($db, 'compras_mercaderia', 'cuenta_id', 'INT NULL');
    app_add_column_if_missing($db, 'compras_mercaderia', 'comprobante_path', 'VARCHAR(255) NULL');
    app_add_column_if_missing($db, 'compras_mercaderia', 'comprobante_tipo', "ENUM('webp','pdf') NULL");

    $gruposCount = (int) $db->query("SELECT COUNT(*) FROM grupos")->fetchColumn();
    if ($gruposCount === 0) {
        $stmtGrupo = $db->prepare("INSERT INTO grupos (nombre, activo) VALUES (?, 1)");
        foreach (['Grupo 1', 'Grupo 2', 'Grupo 3', 'Grupo 4'] as $grupoNombre) {
            $stmtGrupo->execute([$grupoNombre]);
        }
    }

    $cuentasCount = (int) $db->query("SELECT COUNT(*) FROM cuentas_pago")->fetchColumn();
    if ($cuentasCount === 0) {
        $stmtCuenta = $db->prepare("INSERT INTO cuentas_pago (nombre, tipo, detalle, orden, activo) VALUES (?, 'transferencia', ?, ?, 1)");
        $cuentasDefault = [
            ['MP Grupo 1', 'Cuenta Mercado Pago Grupo 1', 1],
            ['MP Grupo 2', 'Cuenta Mercado Pago Grupo 2', 2],
            ['MP Grupo 3', 'Cuenta Mercado Pago Grupo 3', 3],
            ['MP Grupo 4', 'Cuenta Mercado Pago Grupo 4', 4],
        ];
        foreach ($cuentasDefault as $cuentaData) {
            $stmtCuenta->execute($cuentaData);
        }
    }

    $superAdmins = (int) $db->query("SELECT COUNT(*) FROM usuarios WHERE super_admin = 1")->fetchColumn();
    if ($superAdmins === 0) {
        $primerAdminId = (int) $db->query("SELECT id FROM usuarios WHERE es_admin = 1 ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($primerAdminId > 0) {
            $db->prepare("UPDATE usuarios SET super_admin = 1 WHERE id = ?")->execute([$primerAdminId]);
        }
    }

    $storageDir = __DIR__ . '/../uploads/comprobantes';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0775, true);
    }
}

function app_add_column_if_missing(PDO $db, string $table, string $column, string $sqlType): void
{
    if (app_column_exists($db, $table, $column)) {
        return;
    }
    $db->exec(sprintf("ALTER TABLE `%s` ADD COLUMN `%s` %s", $table, $column, $sqlType));
}

function app_column_exists(PDO $db, string $table, string $column): bool
{
    $sql = "SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function get_grupos_activos(PDO $db): array
{
    $stmt = $db->query("SELECT id, nombre FROM grupos WHERE activo = 1 ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function is_grupo_activo_valido(PDO $db, int $grupoId): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM grupos WHERE id = ? AND activo = 1");
    $stmt->execute([$grupoId]);
    return (int) $stmt->fetchColumn() > 0;
}

function get_grupo_nombre_por_id(PDO $db, int $grupoId): ?string
{
    $stmt = $db->prepare("SELECT nombre FROM grupos WHERE id = ? LIMIT 1");
    $stmt->execute([$grupoId]);
    $nombre = $stmt->fetchColumn();
    if ($nombre === false || $nombre === null || $nombre === '') {
        return null;
    }
    return (string) $nombre;
}

function get_cuentas_transferencia(PDO $db): array
{
    $stmt = $db->query("SELECT id, nombre, detalle FROM cuentas_pago WHERE activo = 1 AND tipo = 'transferencia' ORDER BY orden ASC, id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function is_cuenta_transferencia_valida(PDO $db, int $cuentaId): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM cuentas_pago WHERE id = ? AND activo = 1 AND tipo = 'transferencia'");
    $stmt->execute([$cuentaId]);
    return (int) $stmt->fetchColumn() > 0;
}

function get_inicio_semana_lunes(string $fechaBase): string
{
    $fecha = DateTime::createFromFormat('Y-m-d', $fechaBase);
    if (!$fecha) {
        $fecha = new DateTime('today');
    }
    $numeroDia = (int) $fecha->format('N');
    if ($numeroDia > 1) {
        $fecha->modify('-' . ($numeroDia - 1) . ' days');
    }
    return $fecha->format('Y-m-d');
}

function get_fin_semana_domingo(string $fechaInicio): string
{
    $fecha = DateTime::createFromFormat('Y-m-d', $fechaInicio);
    if (!$fecha) {
        $fecha = new DateTime('today');
        $numeroDia = (int) $fecha->format('N');
        if ($numeroDia > 1) {
            $fecha->modify('-' . ($numeroDia - 1) . ' days');
        }
    }
    $fecha->modify('+6 days');
    return $fecha->format('Y-m-d');
}

function get_semana_operativa_actual(PDO $db, ?string $fecha = null): ?array
{
    $fechaRef = $fecha ?: date('Y-m-d');
    $stmt = $db->prepare("SELECT so.*, g.nombre AS grupo_nombre
        FROM semanas_operativas so
        INNER JOIN grupos g ON g.id = so.grupo_id
        WHERE so.fecha_inicio <= ?
          AND so.fecha_fin >= ?
          AND so.estado <> 'cerrada'
        ORDER BY (so.estado = 'activa') DESC, so.fecha_inicio DESC, so.id DESC
        LIMIT 1");
    $stmt->execute([$fechaRef, $fechaRef]);
    $semana = $stmt->fetch(PDO::FETCH_ASSOC);

    return $semana ?: null;
}

function get_capital_efectivo_disponible(PDO $db): float
{
    $sql = "SELECT COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN efectivo ELSE -efectivo END), 0) FROM capital_liquido";
    return (float) $db->query($sql)->fetchColumn();
}

function get_saldo_transferencia_por_cuenta(PDO $db, int $cuentaId): float
{
    $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN transferencia ELSE -transferencia END), 0)
        FROM capital_liquido
        WHERE cuenta_id = ?");
    $stmt->execute([$cuentaId]);
    return (float) $stmt->fetchColumn();
}

function guardar_comprobante(array $file, ?string &$error = null): ?array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'No se pudo subir el comprobante.';
        return null;
    }

    $maxSize = 8 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSize) {
        $error = 'El comprobante supera el limite de 8MB.';
        return null;
    }

    $tmpPath = $file['tmp_name'];
    if (!is_uploaded_file($tmpPath)) {
        $error = 'Archivo de comprobante invalido.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpPath);

    $allowed = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];
    if (!in_array($mime, $allowed, true)) {
        $error = 'Formato de comprobante no permitido. Usa PDF o imagen.';
        return null;
    }

    $year = date('Y');
    $month = date('m');
    $baseDir = __DIR__ . '/../uploads/comprobantes/' . $year . '/' . $month;
    if (!is_dir($baseDir) && !@mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
        $error = 'No se pudo crear la carpeta de comprobantes.';
        return null;
    }

    try {
        $unique = bin2hex(random_bytes(8));
    } catch (Throwable $e) {
        $unique = uniqid('comp_', true);
    }

    if ($mime === 'application/pdf') {
        $fileName = 'comp_' . $unique . '.pdf';
        $destPath = $baseDir . '/' . $fileName;
        if (!move_uploaded_file($tmpPath, $destPath)) {
            $error = 'No se pudo guardar el comprobante PDF.';
            return null;
        }
        return [
            'path' => 'uploads/comprobantes/' . $year . '/' . $month . '/' . $fileName,
            'tipo' => 'pdf',
        ];
    }

    if (!function_exists('imagewebp')) {
        $error = 'El servidor no soporta conversion a WebP.';
        return null;
    }

    $content = file_get_contents($tmpPath);
    if ($content === false) {
        $error = 'No se pudo leer la imagen subida.';
        return null;
    }

    $img = @imagecreatefromstring($content);
    if ($img === false) {
        $error = 'La imagen del comprobante no es valida.';
        return null;
    }

    $fileName = 'comp_' . $unique . '.webp';
    $destPath = $baseDir . '/' . $fileName;
    $saved = imagewebp($img, $destPath, 82);
    imagedestroy($img);

    if (!$saved) {
        $error = 'No se pudo convertir la imagen a WebP.';
        return null;
    }

    return [
        'path' => 'uploads/comprobantes/' . $year . '/' . $month . '/' . $fileName,
        'tipo' => 'webp',
    ];
}

function get_balance_anterior(PDO $db, string $fecha): array
{
    $stmt = $db->prepare("SELECT capital_final_efectivo, capital_final_transferencia
        FROM balance_diario
        WHERE fecha < ?
        ORDER BY fecha DESC
        LIMIT 1");
    $stmt->execute([$fecha]);
    $previo = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'capital_inicial_efectivo' => (float) ($previo['capital_final_efectivo'] ?? 0),
        'capital_inicial_transferencia' => (float) ($previo['capital_final_transferencia'] ?? 0),
    ];
}

function get_ventas_del_dia(PDO $db, string $fecha): array
{
    $stmt = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN metodo_pago = 'Efectivo' AND tipo = 'Venta' THEN importe ELSE 0 END), 0) AS ventas_efectivo,
        COALESCE(SUM(CASE WHEN metodo_pago = 'Transferencia' AND tipo = 'Venta' THEN importe ELSE 0 END), 0) AS ventas_transferencia
        FROM transacciones
        WHERE DATE(fecha) = ?");
    $stmt->execute([$fecha]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'ventas_efectivo' => (float) ($row['ventas_efectivo'] ?? 0),
        'ventas_transferencia' => (float) ($row['ventas_transferencia'] ?? 0),
    ];
}

function get_compras_del_dia(PDO $db, string $fecha): array
{
    $stmt = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto ELSE 0 END), 0) AS compras_efectivo,
        COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN monto ELSE 0 END), 0) AS compras_transferencia
        FROM compras_mercaderia
        WHERE fecha = ?");
    $stmt->execute([$fecha]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'compras_efectivo' => (float) ($row['compras_efectivo'] ?? 0),
        'compras_transferencia' => (float) ($row['compras_transferencia'] ?? 0),
    ];
}

function get_movimientos_capital_del_dia(PDO $db, string $fecha): array
{
    $stmt = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN efectivo ELSE 0 END), 0) AS ingresos_efectivo,
        COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN transferencia ELSE 0 END), 0) AS ingresos_transferencia,
        COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN efectivo ELSE 0 END), 0) AS egresos_efectivo,
        COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN transferencia ELSE 0 END), 0) AS egresos_transferencia
        FROM capital_liquido
        WHERE fecha = ?");
    $stmt->execute([$fecha]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'ingresos_efectivo' => (float) ($row['ingresos_efectivo'] ?? 0),
        'ingresos_transferencia' => (float) ($row['ingresos_transferencia'] ?? 0),
        'egresos_efectivo' => (float) ($row['egresos_efectivo'] ?? 0),
        'egresos_transferencia' => (float) ($row['egresos_transferencia'] ?? 0),
    ];
}

function calcular_resumen_balance_dia(PDO $db, string $fecha): array
{
    $anterior = get_balance_anterior($db, $fecha);
    $ventas = get_ventas_del_dia($db, $fecha);
    $compras = get_compras_del_dia($db, $fecha);
    $movimientosCapital = get_movimientos_capital_del_dia($db, $fecha);

    $capitalFinalEfectivo = $anterior['capital_inicial_efectivo']
        + $movimientosCapital['ingresos_efectivo']
        - $movimientosCapital['egresos_efectivo'];
    $capitalFinalTransferencia = $anterior['capital_inicial_transferencia']
        + $movimientosCapital['ingresos_transferencia']
        - $movimientosCapital['egresos_transferencia'];

    $stmt = $db->query("SELECT COALESCE(SUM(stock * costo), 0) AS valor_inventario FROM productos");
    $inventario = (float) $stmt->fetchColumn();

    return [
        'fecha' => $fecha,
        'capital_inicial_efectivo' => $anterior['capital_inicial_efectivo'],
        'capital_inicial_transferencia' => $anterior['capital_inicial_transferencia'],
        'ventas_efectivo' => $ventas['ventas_efectivo'],
        'ventas_transferencia' => $ventas['ventas_transferencia'],
        'compras_efectivo' => $compras['compras_efectivo'],
        'compras_transferencia' => $compras['compras_transferencia'],
        'ingresos_efectivo' => $movimientosCapital['ingresos_efectivo'],
        'ingresos_transferencia' => $movimientosCapital['ingresos_transferencia'],
        'egresos_efectivo' => $movimientosCapital['egresos_efectivo'],
        'egresos_transferencia' => $movimientosCapital['egresos_transferencia'],
        'capital_final_efectivo' => $capitalFinalEfectivo,
        'capital_final_transferencia' => $capitalFinalTransferencia,
        'capital_productos' => $inventario,
    ];
}

function calcular_resumen_balance_periodo(PDO $db, string $desde, string $hasta): array
{
    if ($desde > $hasta) {
        [$desde, $hasta] = [$hasta, $desde];
    }

    $anterior = get_balance_anterior($db, $desde);

    $stmtVentas = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN metodo_pago = 'Efectivo' AND tipo = 'Venta' THEN importe ELSE 0 END), 0) AS ventas_efectivo,
        COALESCE(SUM(CASE WHEN metodo_pago = 'Transferencia' AND tipo = 'Venta' THEN importe ELSE 0 END), 0) AS ventas_transferencia
        FROM transacciones
        WHERE DATE(fecha) BETWEEN ? AND ?");
    $stmtVentas->execute([$desde, $hasta]);
    $ventasRow = $stmtVentas->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmtCompras = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto ELSE 0 END), 0) AS compras_efectivo,
        COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN monto ELSE 0 END), 0) AS compras_transferencia
        FROM compras_mercaderia
        WHERE fecha BETWEEN ? AND ?");
    $stmtCompras->execute([$desde, $hasta]);
    $comprasRow = $stmtCompras->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmtMovimientos = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN efectivo ELSE 0 END), 0) AS ingresos_efectivo,
        COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN transferencia ELSE 0 END), 0) AS ingresos_transferencia,
        COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN efectivo ELSE 0 END), 0) AS egresos_efectivo,
        COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN transferencia ELSE 0 END), 0) AS egresos_transferencia
        FROM capital_liquido
        WHERE fecha BETWEEN ? AND ?");
    $stmtMovimientos->execute([$desde, $hasta]);
    $movimientosRow = $stmtMovimientos->fetch(PDO::FETCH_ASSOC) ?: [];

    $capitalFinalEfectivo = $anterior['capital_inicial_efectivo']
        + (float) ($movimientosRow['ingresos_efectivo'] ?? 0)
        - (float) ($movimientosRow['egresos_efectivo'] ?? 0);
    $capitalFinalTransferencia = $anterior['capital_inicial_transferencia']
        + (float) ($movimientosRow['ingresos_transferencia'] ?? 0)
        - (float) ($movimientosRow['egresos_transferencia'] ?? 0);

    $stmtInventario = $db->query("SELECT COALESCE(SUM(stock * costo), 0) AS valor_inventario FROM productos");
    $inventario = (float) $stmtInventario->fetchColumn();

    return [
        'fecha' => $desde,
        'desde' => $desde,
        'hasta' => $hasta,
        'capital_inicial_efectivo' => $anterior['capital_inicial_efectivo'],
        'capital_inicial_transferencia' => $anterior['capital_inicial_transferencia'],
        'ventas_efectivo' => (float) ($ventasRow['ventas_efectivo'] ?? 0),
        'ventas_transferencia' => (float) ($ventasRow['ventas_transferencia'] ?? 0),
        'compras_efectivo' => (float) ($comprasRow['compras_efectivo'] ?? 0),
        'compras_transferencia' => (float) ($comprasRow['compras_transferencia'] ?? 0),
        'ingresos_efectivo' => (float) ($movimientosRow['ingresos_efectivo'] ?? 0),
        'ingresos_transferencia' => (float) ($movimientosRow['ingresos_transferencia'] ?? 0),
        'egresos_efectivo' => (float) ($movimientosRow['egresos_efectivo'] ?? 0),
        'egresos_transferencia' => (float) ($movimientosRow['egresos_transferencia'] ?? 0),
        'capital_final_efectivo' => $capitalFinalEfectivo,
        'capital_final_transferencia' => $capitalFinalTransferencia,
        'capital_productos' => $inventario,
    ];
}
