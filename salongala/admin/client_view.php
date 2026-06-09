<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();
backfill_clients_from_invoices();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM clients WHERE id = :id");
$stmt->execute(['id' => $id]);
$client = $stmt->fetch();
if (!$client) {
    http_response_code(404);
    exit('Cliente no encontrado.');
}
refresh_client_stats($id);
$stmt->execute(['id' => $id]);
$client = $stmt->fetch();

$invoiceStmt = db()->prepare("SELECT * FROM invoices WHERE client_id = :id ORDER BY invoice_date DESC, id DESC");
$invoiceStmt->execute(['id' => $id]);
$invoices = $invoiceStmt->fetchAll();

$serviceStmt = db()->prepare("
    SELECT ii.*, i.invoice_number, i.invoice_date, i.id AS invoice_id
    FROM invoice_items ii
    JOIN invoices i ON i.id = ii.invoice_id
    WHERE i.client_id = :id AND ii.duration_days > 0 AND i.status != 'Anulada'
    ORDER BY ii.expires_at DESC, i.invoice_date DESC
");
$serviceStmt->execute(['id' => $id]);
$services = $serviceStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($client['name']) ?> · Galá</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
  <header class="admin-topbar">
    <div><strong><?= h($client['name']) ?></strong><span>Ficha de cliente</span></div>
    <nav><a href="invoice.php">Nueva factura</a><a href="clients.php">Clientes</a><a href="dashboard.php">Dashboard</a><a href="logout.php">Salir</a></nav>
  </header>

  <main class="admin-layout">
    <section class="metric-grid">
      <article class="metric-card"><span>Total gastado</span><strong><?= h(money((float) $client['total_spent'])) ?></strong><p><?= (int) $client['visit_count'] ?> visitas registradas</p></article>
      <article class="metric-card"><span>Primera visita</span><strong><?= h($client['first_visit'] ?: 'N/A') ?></strong><p>Última visita: <?= h($client['last_visit'] ?: 'N/A') ?></p></article>
      <article class="metric-card"><span>Contacto</span><strong><?= h($client['phone'] ?: 'Sin teléfono') ?></strong><p><?= h($client['email'] ?: 'Sin email') ?></p></article>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Servicios con duración</span><h1>Días restantes y vencimientos</h1></div>
      <div class="report-table">
        <div class="report-row head"><span>Servicio</span><span>Factura</span><span>Vence</span><span>Estado</span></div>
        <?php if (!$services): ?><p>No hay servicios con duración registrada.</p><?php endif; ?>
        <?php foreach ($services as $service): ?>
          <?php $remaining = days_remaining($service['expires_at']); ?>
          <a class="report-row" href="invoice_view.php?id=<?= (int) $service['invoice_id'] ?>">
            <span><?= h($service['description']) ?><small><?= (int) $service['duration_days'] ?> días desde <?= h($service['invoice_date']) ?></small></span>
            <span><?= h($service['invoice_number']) ?></span>
            <span><?= h($service['expires_at']) ?></span>
            <strong class="<?= $remaining !== null && $remaining < 0 ? 'status-expired' : 'status-active' ?>">
              <?php if ($remaining === null): ?>Sin vencimiento<?php elseif ($remaining < 0): ?>Vencido hace <?= abs($remaining) ?> días<?php elseif ($remaining === 0): ?>Vence hoy<?php else: ?>Quedan <?= $remaining ?> días<?php endif; ?>
            </strong>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Historial</span><h2>Facturas y servicios realizados</h2></div>
      <div class="report-table">
        <div class="report-row head"><span>Factura</span><span>Fecha</span><span>Estado</span><span>Total</span></div>
        <?php foreach ($invoices as $invoice): ?>
          <a class="report-row" href="invoice_view.php?id=<?= (int) $invoice['id'] ?>">
            <span><?= h($invoice['invoice_number']) ?></span>
            <span><?= h($invoice['invoice_date']) ?></span>
            <span><?= h($invoice['status']) ?></span>
            <strong><?= h(money((float) $invoice['total'])) ?></strong>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
