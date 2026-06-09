<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$catalog = catalog_items();
$defaultTax = (float) setting('invoice_tax_rate', '0');
$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva factura · Galá</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <script defer src="../assets/js/admin.js"></script>
</head>
<body>
  <header class="admin-topbar">
    <div><strong>Nueva factura</strong><span><?= h(next_invoice_number()) ?></span></div>
    <nav><a href="dashboard.php">Dashboard</a><a href="clients.php">Clientes</a><a href="reports.php">Reportes</a><a href="logout.php">Salir</a></nav>
  </header>

  <main class="admin-layout">
    <?php if ($flash): ?><div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

    <form class="panel invoice-builder" method="post" action="invoice_save.php" data-invoice-form>
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <div class="panel-heading"><span>Facturación</span><h1>Registrar venta o servicio realizado</h1></div>

      <div class="settings-grid">
        <label>Cliente<input name="client_name" required maxlength="140" placeholder="Nombre de la clienta"></label>
        <label>Teléfono<input name="client_phone" maxlength="40" placeholder="809..."></label>
        <label>Email<input name="client_email" type="email" maxlength="140"></label>
        <label>Fecha<input name="invoice_date" type="date" value="<?= h(date('Y-m-d')) ?>" required></label>
        <label>Método de pago<select name="payment_method"><option>Efectivo</option><option>Tarjeta</option><option>Transferencia</option><option>Mixto</option></select></label>
        <label>Estado<select name="status"><option>Pagada</option><option>Pendiente</option><option>Anulada</option></select></label>
      </div>

      <datalist id="catalogOptions">
        <?php foreach ($catalog as $item): ?>
          <option value="<?= h($item['title']) ?>" data-type="<?= h($item['type']) ?>"></option>
        <?php endforeach; ?>
      </datalist>

      <div class="invoice-lines" data-lines>
        <div class="invoice-line head"><span>Tipo</span><span>Servicio / producto</span><span>Cant.</span><span>Precio</span><span>Duración</span><span>Total</span><span></span></div>
        <div class="invoice-line" data-line>
          <select name="item_type[]"><option value="service">Servicio</option><option value="product">Producto</option></select>
          <input name="description[]" list="catalogOptions" required maxlength="180" placeholder="Ej. Salud capilar">
          <input name="quantity[]" type="number" step="0.01" min="0.01" value="1" required data-qty>
          <input name="unit_price[]" type="number" step="0.01" min="0" value="0" required data-price>
          <input name="duration_days[]" type="number" step="1" min="0" value="0" placeholder="Días">
          <output data-line-total>RD$ 0.00</output>
          <button type="button" class="ghost" data-remove-line>Quitar</button>
        </div>
      </div>

      <button type="button" class="secondary-action" data-add-line>Agregar línea</button>

      <div class="invoice-bottom">
        <label>Notas<textarea name="notes" rows="5" maxlength="900" placeholder="Observaciones, garantía del servicio o indicaciones para la clienta"></textarea></label>
        <div class="totals-card">
          <label>Descuento RD$<input name="discount" type="number" step="0.01" min="0" value="0" data-discount></label>
          <label>Impuesto %<input name="tax_rate" type="number" step="0.01" min="0" value="<?= h((string) $defaultTax) ?>" data-tax></label>
          <dl>
            <div><dt>Subtotal</dt><dd data-subtotal>RD$ 0.00</dd></div>
            <div><dt>Impuesto</dt><dd data-tax-total>RD$ 0.00</dd></div>
            <div class="total"><dt>Total</dt><dd data-total>RD$ 0.00</dd></div>
          </dl>
          <button type="submit">Guardar factura</button>
        </div>
      </div>
    </form>
  </main>
</body>
</html>
