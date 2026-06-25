<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';
$id = intval($_GET['id'] ?? 0);
if (!$id) { echo '<div class="alert alert-danger">Turno no encontrado.</div>'; exit; }

// Obtener datos del turno
$turno = $db->prepare("SELECT t.*, g.nombre AS grupo_nombre
FROM turnos t
LEFT JOIN grupos g ON g.id = t.grupo_id
WHERE t.id = ?");
$turno->execute([$id]);
$turno = $turno->fetch(PDO::FETCH_ASSOC);
if (!$turno) { echo '<div class="alert alert-danger">Turno no encontrado.</div>'; exit; }

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

// Calcular totales y agrupar ventas
$total_efectivo = 0;
$total_transferencia = 0;
$total_ventas = 0;
$productos_vendidos = [];
$ventas_agrupadas = [];
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
        // Agrupar ventas por fecha y método de pago (como ticket)
        $key = $t['fecha'] . '|' . $t['metodo_pago'] . '|' . ($t['cuenta_id'] ?? '0');
        if (!isset($ventas_agrupadas[$key])) {
            $ventas_agrupadas[$key] = [
                'fecha' => $t['fecha'],
                'metodo_pago' => $t['metodo_pago'],
                'cuenta_nombre' => $t['cuenta_nombre'],
                'productos' => [],
                'total' => 0
            ];
        }
        $ventas_agrupadas[$key]['productos'][] = [
            'nombre' => $t['producto_nombre'],
            'cantidad' => $t['cantidad'],
            'importe' => $t['importe'],
            'cuenta_nombre' => $t['cuenta_nombre']
        ];
        $ventas_agrupadas[$key]['total'] += $t['importe'];
    }
}
$diferencia_caja = $turno['saldo_cierre'] - $turno['saldo_inicial'] - $total_efectivo;
$diferenciaClass = abs((float) $diferencia_caja) < 0.00001 ? 'ok' : 'warn';
?>
<div class="detalle-turno">
    <div class="detalle-card">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
                <h5 class="detalle-title mb-1"><i class="fas fa-clock"></i> Turno #<?= (int) $turno['id'] ?></h5>
                <div class="text-muted" style="font-size:0.9rem;">
                    Del <?= date('d/m/Y H:i', strtotime($turno['fecha_apertura'])) ?>
                    al <?= date('d/m/Y H:i', strtotime($turno['fecha_cierre'])) ?>
                </div>
            </div>
            <span class="badge bg-info text-dark"><?= htmlspecialchars($turno['grupo_nombre'] ?: 'Sin grupo', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="meta-row mt-2">
            <span class="meta-chip"><i class="fas fa-receipt"></i> <?= count($ventas_agrupadas) ?> ventas</span>
            <span class="meta-chip"><i class="fas fa-box"></i> <?= count($productos_vendidos) ?> productos</span>
            <span class="meta-chip"><i class="fas fa-dollar-sign"></i> Total ventas $<?= number_format($total_ventas, 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="detalle-card">
        <h6 class="detalle-title"><i class="fas fa-cash-register"></i> Resumen de Caja</h6>
        <div class="resumen-grid">
            <div class="resumen-item">
                <div class="label">Saldo inicial</div>
                <div class="value">$<?= number_format((float) $turno['saldo_inicial'], 0, ',', '.') ?></div>
            </div>
            <div class="resumen-item">
                <div class="label">Ventas efectivo</div>
                <div class="value">$<?= number_format($total_efectivo, 0, ',', '.') ?></div>
            </div>
            <div class="resumen-item">
                <div class="label">Ventas transferencia</div>
                <div class="value">$<?= number_format($total_transferencia, 0, ',', '.') ?></div>
            </div>
            <div class="resumen-item">
                <div class="label">Esperado en caja</div>
                <div class="value">$<?= number_format($turno['saldo_inicial'] + $total_efectivo, 0, ',', '.') ?></div>
            </div>
            <div class="resumen-item">
                <div class="label">Saldo real</div>
                <div class="value">$<?= number_format((float) $turno['saldo_cierre'], 0, ',', '.') ?></div>
            </div>
            <div class="resumen-item <?= $diferenciaClass ?>">
                <div class="label">Diferencia</div>
                <div class="value">$<?= number_format($diferencia_caja, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <div class="detalle-card">
        <h6 class="detalle-title"><i class="fas fa-list"></i> Ventas del Turno</h6>
        <?php if (count($ventas_agrupadas) === 0): ?>
        <div class="alert alert-info mb-0">No hay ventas registradas en este turno.</div>
        <?php else: ?>
        <div class="accordion venta-accordion" id="ventasAccordion">
            <?php $i=1; foreach($ventas_agrupadas as $venta): ?>
            <?php $collapseId = 'collapse_turno_' . (int) $turno['id'] . '_' . $i; ?>
            <?php $headingId = 'heading_turno_' . (int) $turno['id'] . '_' . $i; ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="<?= $headingId ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                        <div class="venta-head">
                            <div class="venta-left">
                                <span>Venta <?= $i ?></span>
                                <span class="badge bg-<?= ($venta['metodo_pago'] === 'Efectivo' ? 'success' : 'info') ?>">
                                    <?= $venta['metodo_pago'] === 'Efectivo' ? 'Efectivo' : 'Transferencia' ?>
                                </span>
                                <span class="badge bg-light text-dark"><?= date('H:i', strtotime($venta['fecha'])) ?></span>
                                <?php if (!empty($venta['cuenta_nombre'])): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($venta['cuenta_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="venta-total">$<?= number_format($venta['total'], 0, ',', '.') ?></div>
                        </div>
                    </button>
                </h2>
                <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="<?= $headingId ?>"
                    data-bs-parent="#ventasAccordion">
                    <div class="accordion-body p-2">
                        <table class="table table-sm table-bordered mb-0 table-detalle-mobile">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Importe</th>
                                    <th>Cuenta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($venta['productos'] as $prod): ?>
                                <tr>
                                    <td data-label="Producto"><?= htmlspecialchars($prod['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Cantidad" class="text-center"><?= (int) $prod['cantidad'] ?></td>
                                    <td data-label="Importe" class="text-end">$<?= number_format((float) $prod['importe'], 0, ',', '.') ?></td>
                                    <td data-label="Cuenta"><?= htmlspecialchars($prod['cuenta_nombre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php $i++; endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="detalle-card">
        <h6 class="detalle-title"><i class="fas fa-box"></i> Productos Vendidos</h6>
        <?php if (count($productos_vendidos) === 0): ?>
        <div class="alert alert-info mb-0">Sin productos vendidos en este turno.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-detalle-mobile mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($productos_vendidos as $producto): ?>
                    <tr>
                        <td data-label="Producto"><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Cantidad" class="text-center"><?= (int) $producto['cantidad'] ?></td>
                        <td data-label="Total" class="text-end">$<?= number_format((float) $producto['total'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
