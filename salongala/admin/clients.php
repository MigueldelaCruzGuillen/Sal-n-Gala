<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();
backfill_clients_from_invoices();

$q = trim((string) ($_GET['q'] ?? ''));
$leaders = client_leaders();

if ($q !== '') {
    $stmt = db()->prepare("
        SELECT * FROM clients
        WHERE name LIKE :q OR phone LIKE :q OR email LIKE :q
        ORDER BY total_spent DESC, visit_count DESC, name ASC
        LIMIT 80
    ");
    $stmt->execute(['q' => '%' . $q . '%']);
    $clients = $stmt->fetchAll();
} else {
    $clients = db()->query("SELECT * FROM clients ORDER BY updated_at DESC, total_spent DESC LIMIT 80")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clientes · Galá</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
  <header class="admin-topbar">
    <div><strong>Clientes</strong><span>Historial, vigencias y fidelidad</span></div>
    <nav><a href="invoice.php">Nueva factura</a><a href="reports.php">Reportes</a><a href="dashboard.php">Dashboard</a><a href="logout.php">Salir</a></nav>
  </header>

  <main class="admin-layout">
    <section class="metric-grid">
      <article class="metric-card">
        <span>Cliente que más ha gastado</span>
        <?php if ($leaders['top_spent']): ?>
          <strong><?= h($leaders['top_spent']['name']) ?></strong>
          <p><?= h(money((float) $leaders['top_spent']['total_spent'])) ?> acumulado</p>
        <?php else: ?><strong>Sin datos</strong><p>Aún no hay ventas.</p><?php endif; ?>
      </article>
      <article class="metric-card">
        <span>Cliente más fiel</span>
        <?php if ($leaders['most_faithful']): ?>
          <strong><?= h($leaders['most_faithful']['name']) ?></strong>
          <p><?= (int) $leaders['most_faithful']['visit_count'] ?> visitas registradas</p>
        <?php else: ?><strong>Sin datos</strong><p>Aún no hay visitas.</p><?php endif; ?>
      </article>
      <article class="metric-card">
        <span>Total clientes</span>
        <strong><?= (int) db()->query("SELECT COUNT(*) FROM clients")->fetchColumn() ?></strong>
        <p>Fichas creadas desde facturación</p>
      </article>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Búsqueda</span><h1>Buscar historial por cliente</h1></div>
      <form class="filters" method="get">
        <label>Nombre, teléfono o email<input name="q" value="<?= h($q) ?>" placeholder="Ej. María, 809..."></label>
        <button type="submit">Buscar</button>
        <a class="secondary-action" href="clients.php">Limpiar</a>
      </form>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Clientes</span><h2>Resultados</h2></div>
      <div class="report-table">
        <div class="report-row head"><span>Cliente</span><span>Contacto</span><span>Visitas</span><span>Total gastado</span></div>
        <?php foreach ($clients as $client): ?>
          <a class="report-row" href="client_view.php?id=<?= (int) $client['id'] ?>">
            <span><?= h($client['name']) ?><small>Última visita: <?= h($client['last_visit'] ?: 'Sin fecha') ?></small></span>
            <span><?= h($client['phone'] ?: $client['email'] ?: 'Sin contacto') ?></span>
            <span><?= (int) $client['visit_count'] ?></span>
            <strong><?= h(money((float) $client['total_spent'])) ?></strong>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
