<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$types = [
    'service' => 'Servicios',
    'product' => 'Productos',
    'testimonial' => 'Opiniones',
    'gallery' => 'Galería',
];
$stats = invoice_stats();
$recentInvoices = db()->query("SELECT * FROM invoices ORDER BY id DESC LIMIT 8")->fetchAll();
$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard · Galá</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <script defer src="../assets/js/admin.js"></script>
</head>
<body>
  <header class="admin-topbar">
    <div><strong>Galá Admin</strong><span>Web, facturación, ventas e inventario</span></div>
    <nav><a href="invoice.php">Nueva factura</a><a href="clients.php">Clientes</a><a href="reports.php">Reportes</a><a href="../index.php" target="_blank">Ver web</a><a href="logout.php">Salir</a></nav>
  </header>

  <main class="admin-layout">
    <?php if ($flash): ?><div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

    <section class="metric-grid">
      <article class="metric-card"><span>Vendido hoy</span><strong><?= h(money($stats['today'])) ?></strong><p><?= h(date('d/m/Y')) ?></p></article>
      <article class="metric-card"><span>Vendido este mes</span><strong><?= h(money($stats['month'])) ?></strong><p><?= (int) $stats['month_count'] ?> facturas registradas</p></article>
      <article class="metric-card"><span>Acción rápida</span><strong>Clientes</strong><p><a href="invoice.php">Crear factura</a> · <a href="clients.php">Historial</a></p></article>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Ventas</span><h1>Resumen operativo</h1></div>
      <div class="analytics-grid">
        <div>
          <canvas id="salesChart" height="210" data-series='<?= h(json_encode($stats['last7'])) ?>'></canvas>
        </div>
        <div class="invoice-list">
          <h2>Últimas facturas</h2>
          <?php if (!$recentInvoices): ?><p>Aún no hay facturas registradas.</p><?php endif; ?>
          <?php foreach ($recentInvoices as $invoice): ?>
            <a class="invoice-mini" href="invoice_view.php?id=<?= (int) $invoice['id'] ?>">
              <span><?= h($invoice['invoice_number']) ?></span>
              <strong><?= h(money((float) $invoice['total'])) ?></strong>
              <small><?= h($invoice['client_name']) ?> · <?= h($invoice['invoice_date']) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Marca</span><h1>Ajustes generales</h1></div>
      <form class="settings-grid" method="post" action="save.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="settings">
        <?php foreach (['brand' => 'Logo / marca', 'legal_name' => 'Nombre completo', 'tagline' => 'Frase hero', 'intro' => 'Descripción', 'phone' => 'Teléfono', 'whatsapp' => 'WhatsApp con país', 'address' => 'Dirección', 'hours' => 'Horario', 'google_rating' => 'Rating Google', 'google_reviews' => 'Texto opiniones', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'hero_image' => 'Imagen hero URL', 'invoice_footer' => 'Pie de factura', 'invoice_tax_rate' => 'Impuesto %', 'currency' => 'Moneda'] as $key => $label): ?>
          <label><?= h($label) ?><input name="settings[<?= h($key) ?>]" value="<?= h(setting($key)) ?>" maxlength="500"></label>
        <?php endforeach; ?>
        <label>Logo para factura<input name="invoice_logo_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
        <label>Firma para factura<input name="invoice_signature_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
        <button type="submit">Guardar ajustes</button>
      </form>
    </section>

    <section class="panel">
      <div class="panel-heading"><span>Nuevo contenido</span><h2>Agregar producto, servicio, opinión o foto</h2></div>
      <form class="item-form" method="post" action="save.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="item">
        <label>Tipo<select name="type" required><?php foreach ($types as $value => $label): ?><option value="<?= h($value) ?>"><?= h($label) ?></option><?php endforeach; ?></select></label>
        <label>Título / nombre<input name="title" required maxlength="140"></label>
        <label>Subtítulo / categoría<input name="subtitle" maxlength="180"></label>
        <label>Precio<input name="price" maxlength="80" placeholder="Ej. Desde RD$ 1,450"></label>
        <label>Orden<input name="sort_order" type="number" value="10"></label>
        <label>Rating<select name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> estrellas</option><?php endfor; ?></select></label>
        <label class="full">Descripción / reseña<textarea name="body" rows="4" maxlength="900"></textarea></label>
        <label>Imagen por URL<input name="image_url" maxlength="700" placeholder="https://..."></label>
        <label>Subir imagen real<input name="image_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
        <button type="submit">Publicar</button>
      </form>
    </section>

    <?php foreach ($types as $type => $label): ?>
      <section class="panel">
        <div class="panel-heading"><span><?= h($label) ?></span><h2>Administrar <?= h(strtolower($label)) ?></h2></div>
        <div class="content-list">
          <?php foreach (items($type, 100) as $item): ?>
            <article class="content-row">
              <img src="<?= h(image_url($item['image'])) ?>" alt="">
              <div>
                <strong><?= h($item['title']) ?></strong>
                <p><?= h($item['subtitle']) ?> <?= $item['price'] ? '· ' . h($item['price']) : '' ?></p>
                <small><?= h(strlen($item['body']) > 180 ? substr($item['body'], 0, 180) . '...' : $item['body']) ?></small>
              </div>
              <form method="post" action="save.php" onsubmit="return confirm('¿Ocultar este elemento?');">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button class="danger" type="submit">Ocultar</button>
              </form>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </main>
</body>
</html>
