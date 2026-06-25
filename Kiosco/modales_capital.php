<?php $cuentasTransferencia = $cuentasTransferencia ?? []; ?>

<!-- Modal Ingreso de Capital -->
<div class="modal fade" id="modalIngreso" tabindex="-1" aria-labelledby="modalIngresoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="procesar_capital.php" method="POST" enctype="multipart/form-data" id="formIngreso">
        <input type="hidden" name="accion" value="ingreso">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="modalIngresoLabel"><i class="fas fa-plus me-2"></i>Ingreso de Capital</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="monto_ingreso" class="form-label">Monto</label>
            <input type="number" class="form-control" id="monto_ingreso" name="monto" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="metodo_ingreso">Método de pago</label>
            <select class="form-select" name="metodo_pago" id="metodo_ingreso" required>
              <option value="efectivo">Efectivo</option>
              <option value="transferencia">Transferencia</option>
            </select>
          </div>
          <div class="mb-3" id="cuenta_ingreso_wrap" style="display:none;">
            <label class="form-label" for="cuenta_ingreso">Cuenta de transferencia</label>
            <select class="form-select" name="cuenta_id" id="cuenta_ingreso">
              <option value="">Seleccionar cuenta</option>
              <?php foreach ($cuentasTransferencia as $cuenta): ?>
              <option value="<?= (int) $cuenta['id'] ?>"><?= htmlspecialchars($cuenta['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="descripcion_ingreso" class="form-label">Descripción</label>
            <input type="text" class="form-control" id="descripcion_ingreso" name="descripcion" maxlength="100" required>
          </div>
          <div class="mb-3">
            <label for="comprobante_ingreso" class="form-label">Comprobante (opcional)</label>
            <input type="file" class="form-control" id="comprobante_ingreso" name="comprobante" accept="image/*,.pdf">
            <small class="text-muted">Si subes imagen se guarda en WebP. Max 8MB.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Registrar Ingreso</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Egreso de Capital -->
<div class="modal fade" id="modalEgreso" tabindex="-1" aria-labelledby="modalEgresoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="procesar_capital.php" method="POST" enctype="multipart/form-data" id="formEgreso">
        <input type="hidden" name="accion" value="egreso">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title" id="modalEgresoLabel"><i class="fas fa-minus me-2"></i>Egreso de Capital</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="monto_egreso" class="form-label">Monto</label>
            <input type="number" class="form-control" id="monto_egreso" name="monto" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="metodo_egreso">Método de pago</label>
            <select class="form-select" name="metodo_pago" id="metodo_egreso" required>
              <option value="efectivo">Efectivo</option>
              <option value="transferencia">Transferencia</option>
            </select>
          </div>
          <div class="mb-3" id="cuenta_egreso_wrap" style="display:none;">
            <label class="form-label" for="cuenta_egreso">Cuenta de transferencia</label>
            <select class="form-select" name="cuenta_id" id="cuenta_egreso">
              <option value="">Seleccionar cuenta</option>
              <?php foreach ($cuentasTransferencia as $cuenta): ?>
              <option value="<?= (int) $cuenta['id'] ?>"><?= htmlspecialchars($cuenta['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="descripcion_egreso" class="form-label">Descripción</label>
            <input type="text" class="form-control" id="descripcion_egreso" name="descripcion" maxlength="100" required>
          </div>
          <div class="mb-3">
            <label for="comprobante_egreso" class="form-label">Comprobante (obligatorio)</label>
            <input type="file" class="form-control" id="comprobante_egreso" name="comprobante" accept="image/*,.pdf" required>
            <small class="text-muted">Acepta imagen o PDF. Si es imagen, se guarda en WebP. Max 8MB.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning">Registrar Egreso</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Registrar Compra -->
<div class="modal fade" id="modalCompra" tabindex="-1" aria-labelledby="modalCompraLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="procesar_capital.php" method="POST" enctype="multipart/form-data" id="formCompra">
        <input type="hidden" name="accion" value="compra">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="modalCompraLabel"><i class="fas fa-shopping-cart me-2"></i>Registrar Compra</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="monto_compra" class="form-label">Monto</label>
            <input type="number" class="form-control" id="monto_compra" name="monto" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="metodo_compra">Método de pago</label>
            <select class="form-select" name="metodo_pago" id="metodo_compra" required>
              <option value="efectivo">Efectivo</option>
              <option value="transferencia">Transferencia</option>
            </select>
          </div>
          <div class="mb-3" id="cuenta_compra_wrap" style="display:none;">
            <label class="form-label" for="cuenta_compra">Cuenta de transferencia</label>
            <select class="form-select" name="cuenta_id" id="cuenta_compra">
              <option value="">Seleccionar cuenta</option>
              <?php foreach ($cuentasTransferencia as $cuenta): ?>
              <option value="<?= (int) $cuenta['id'] ?>"><?= htmlspecialchars($cuenta['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="descripcion_compra" class="form-label">Descripción</label>
            <input type="text" class="form-control" id="descripcion_compra" name="descripcion" maxlength="100" required>
          </div>
          <div class="mb-3">
            <label for="comprobante_compra" class="form-label">Comprobante (obligatorio)</label>
            <input type="file" class="form-control" id="comprobante_compra" name="comprobante" accept="image/*,.pdf" required>
            <small class="text-muted">Acepta imagen o PDF. Si es imagen, se guarda en WebP. Max 8MB.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info">Registrar Compra</button>
        </div>
      </form>
    </div>
  </div>
</div>
