<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM invoices WHERE id = :id");
$stmt->execute(['id' => $id]);
$invoice = $stmt->fetch();
if (!$invoice) {
    http_response_code(404);
    exit('Factura no encontrada.');
}
$itemsStmt = db()->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id ORDER BY id ASC");
$itemsStmt->execute(['id' => $id]);
$lines = $itemsStmt->fetchAll();
$phone = whatsapp_number($invoice['client_phone'], setting('whatsapp', '18098666064'));
$publicUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . '/invoice_view.php?id=' . $id;
$waText = rawurlencode("Hola {$invoice['client_name']}, te compartimos tu factura {$invoice['invoice_number']} de Galá por " . money((float) $invoice['total']) . ". Puedes guardarla como PDF desde la opción Imprimir.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($invoice['invoice_number']) ?> · Galá</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="invoice-page">
  <header class="admin-topbar no-print">
    <div><strong><?= h($invoice['invoice_number']) ?></strong><span><?= h($invoice['status']) ?> · <?= h($invoice['invoice_date']) ?></span></div>
    <nav>
      <a href="invoice.php">Nueva factura</a>
      <a href="dashboard.php">Dashboard</a>
      <button type="button" onclick="window.print()">Imprimir / guardar PDF</button>
      <a href="https://wa.me/<?= h($phone) ?>?text=<?= h($waText) ?>" target="_blank" rel="noreferrer">Enviar WhatsApp</a>
    </nav>
  </header>

  <main class="invoice-sheet">
    <section class="invoice-head">
      <div>
        <?php if (setting('invoice_logo')): ?><img class="invoice-logo" src="../<?= h(image_url(setting('invoice_logo'))) ?>" alt="Logo Galá"><?php else: ?><div class="invoice-logo-fallback">G</div><?php endif; ?>
        <h1><?= h(setting('legal_name')) ?></h1>
        <p><?= h(setting('address')) ?><br><?= h(setting('phone')) ?></p>
      </div>
      <div class="invoice-number">
        <span>Factura</span>
        <strong><?= h($invoice['invoice_number']) ?></strong>
        <p>Fecha: <?= h($invoice['invoice_date']) ?><br>Estado: <?= h($invoice['status']) ?></p>
      </div>
    </section>

    <section class="invoice-client">
      <div><span>Cliente</span><strong><?= h($invoice['client_name']) ?></strong><p><?= h($invoice['client_phone']) ?><br><?= h($invoice['client_email']) ?></p></div>
      <div><span>Método de pago</span><strong><?= h($invoice['payment_method']) ?></strong></div>
    </section>

    <table class="invoice-table">
      <thead><tr><th>Descripción</th><th>Tipo</th><th>Cant.</th><th>Precio</th><th>Vigencia</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($lines as $line): ?>
          <tr>
            <td><?= h($line['description']) ?></td>
            <td><?= $line['item_type'] === 'product' ? 'Producto' : 'Servicio' ?></td>
            <td><?= h(number_format((float) $line['quantity'], 2)) ?></td>
            <td><?= h(money((float) $line['unit_price'])) ?></td>
            <td>
              <?php if ((int) ($line['duration_days'] ?? 0) > 0): ?>
                <?= (int) $line['duration_days'] ?> días<br><small>Vence: <?= h($line['expires_at']) ?></small>
              <?php else: ?>
                Sin vencimiento
              <?php endif; ?>
            </td>
            <td><?= h(money((float) $line['line_total'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <section class="invoice-summary">
      <div class="invoice-notes">
        <span>Notas</span>
        <p><?= nl2br(h($invoice['notes'] ?: setting('invoice_footer'))) ?></p>
      </div>
      <dl>
        <div><dt>Subtotal</dt><dd><?= h(money((float) $invoice['subtotal'])) ?></dd></div>
        <div><dt>Descuento</dt><dd><?= h(money((float) $invoice['discount'])) ?></dd></div>
        <div><dt>Impuesto <?= h((string) $invoice['tax_rate']) ?>%</dt><dd><?= h(money((float) $invoice['tax_total'])) ?></dd></div>
        <div class="total"><dt>Total</dt><dd><?= h(money((float) $invoice['total'])) ?></dd></div>
      </dl>
    </section>

    <section class="invoice-signature">
      <div>
        <?php if (setting('invoice_signature')): ?><img src="../<?= h(image_url(setting('invoice_signature'))) ?>" alt="Firma autorizada"><?php endif; ?>
        <span>Firma autorizada</span>
      </div>
      <p><?= h(setting('invoice_footer')) ?></p>
    </section>
  </main>
</body>
</html>
