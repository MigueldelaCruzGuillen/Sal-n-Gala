<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? '')) ? $_GET['month'] : date('Y-m');
$day = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['day'] ?? '')) ? $_GET['day'] : date('Y-m-d');

$dayStmt = db()->prepare("SELECT COALESCE(SUM(total), 0) AS total, COUNT(*) AS count FROM invoices WHERE invoice_date = :day AND status != 'Anulada'");
$dayStmt->execute(['day' => $day]);
$dayTotals = $dayStmt->fetch();

$monthStmt = db()->prepare("SELECT COALESCE(SUM(total), 0) AS total, COUNT(*) AS count FROM invoices WHERE substr(invoice_date, 1, 7) = :month AND status != 'Anulada'");
$monthStmt->execute(['month' => $month]);
$monthTotals = $monthStmt->fetch();

$topStmt = db()->prepare("
    SELECT ii.description, ii.item_type, SUM(ii.quantity) AS quantity, SUM(ii.line_total) AS total
    FROM invoice_items ii
    JOIN invoices i ON i.id = ii.invoice_id
    WHERE substr(i.invoice_date, 1, 7) = :month AND i.status != 'Anulada'
    GROUP BY ii.description, ii.item_type
    ORDER BY total DESC
    LIMIT 12
");
$topStmt->execute(['month' => $month]);
$topItems = $topStmt->fetchAll();

$dailyStmt = db()->prepare("
    SELECT invoice_date, COALESCE(SUM(total), 0) AS total
    FROM invoices
    WHERE substr(invoice_date, 1, 7) = :month AND status != 'Anulada'
    GROUP BY invoice_date
    ORDER BY invoice_date ASC
");
$dailyStmt->execute(['month' => $month]);
$dailyRows = $dailyStmt->fetchAll();

$invoicesStmt = db()->prepare("SELECT * FROM invoices WHERE substr(invoice_date, 1, 7) = :month ORDER BY invoice_date DESC, id DESC");
$invoicesStmt->execute(['month' => $month]);
$invoices = $invoicesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reportes · Galá</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <script defer src="../assets/js/admin.js"></script>
</head>
<body>
  <header class="admin-topbar">
    <div><strong>Reportes</strong><span>Ventas, servicios realizados e inventario vendido</span></div>
    <nav><a href="invoice.php">Nueva factura</a><a href="dashboard.php">Dashboard</a><a href="logout.php">Salir</a></nav>
  </header>
  <main class="admin-layout">
    <section class="panel">
      <form class="filters" method="get">
        <label>Día<input type="date" name="day" value="<?= h($day) ?>"></label>
        <label>Mes<input type="month" name="month" value="<?= h($month) ?>"></label>
        <button type="submit">Filtrar</button>
      </form>
    </section>

    <section class="metric-grid">
      <article class="metric-card"><span>Total del día</span><strong><?= h(money((float) $dayTotals['total'])) ?></strong><p><?= (int) $dayTotals['count'] ?> facturas</p></article>
      <article class="metric-card"><span>Total del mes</span><strong><?= h(money((float) $monthTotals['total'])) ?></strong><p><?= (int) $monthTotals['count'] ?> facturas</p></article>
      <article class="metric-card"><span>Promedio mensual</span><strong><?= h(money(((int) $monthTotals['count']) ? (float) $monthTotals['total'] / (int) $monthTotals['count'] : 0)) ?></strong><p>Ticket promedio</p></article>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Gráfico</span><h1>Total vendido por día</h1></div>
      <canvas id="salesChart" height="240" data-series='<?= h(json_encode($dailyRows)) ?>'></canvas>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Inventario vendido</span><h2>Servicios y productos más vendidos del mes</h2></div>
      <div class="report-table">
        <div class="report-row head"><span>Descripción</span><span>Tipo</span><span>Cantidad</span><span>Total</span></div>
        <?php foreach ($topItems as $item): ?>
          <div class="report-row"><span><?= h($item['description']) ?></span><span><?= $item['item_type'] === 'product' ? 'Producto' : 'Servicio' ?></span><span><?= h(number_format((float) $item['quantity'], 2)) ?></span><strong><?= h(money((float) $item['total'])) ?></strong></div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Facturas</span><h2>Facturas del mes</h2></div>
      <div class="report-table">
        <div class="report-row head"><span>Número</span><span>Cliente</span><span>Fecha</span><span>Total</span></div>
        <?php foreach ($invoices as $invoice): ?>
          <a class="report-row" href="invoice_view.php?id=<?= (int) $invoice['id'] ?>"><span><?= h($invoice['invoice_number']) ?></span><span><?= h($invoice['client_name']) ?></span><span><?= h($invoice['invoice_date']) ?></span><strong><?= h(money((float) $invoice['total'])) ?></strong></a>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
