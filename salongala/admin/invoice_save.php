<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: invoice.php');
    exit;
}

verify_csrf();

try {
    $descriptions = $_POST['description'] ?? [];
    $types = $_POST['item_type'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['unit_price'] ?? [];
    $durations = $_POST['duration_days'] ?? [];
    $lines = [];
    $subtotal = 0.0;
    $invoiceDate = (string) ($_POST['invoice_date'] ?? date('Y-m-d'));

    foreach ($descriptions as $index => $description) {
        $description = trim((string) $description);
        $qty = max(0.01, (float) ($quantities[$index] ?? 1));
        $price = max(0, (float) ($prices[$index] ?? 0));
        $durationDays = max(0, (int) ($durations[$index] ?? 0));
        $expiresAt = $durationDays > 0 ? (new DateTimeImmutable($invoiceDate))->modify('+' . $durationDays . ' days')->format('Y-m-d') : '';
        if ($description === '') {
            continue;
        }
        $lineTotal = round($qty * $price, 2);
        $subtotal += $lineTotal;
        $lines[] = [
            'type' => in_array(($types[$index] ?? 'service'), ['service', 'product'], true) ? $types[$index] : 'service',
            'description' => $description,
            'quantity' => $qty,
            'unit_price' => $price,
            'line_total' => $lineTotal,
            'duration_days' => $durationDays,
            'expires_at' => $expiresAt,
        ];
    }

    if (!$lines) {
        throw new RuntimeException('Agrega al menos una línea a la factura.');
    }

    $discount = max(0, (float) ($_POST['discount'] ?? 0));
    $taxRate = max(0, (float) ($_POST['tax_rate'] ?? 0));
    $taxable = max(0, $subtotal - $discount);
    $taxTotal = round($taxable * ($taxRate / 100), 2);
    $total = round($taxable + $taxTotal, 2);

    $pdo = db();
    $pdo->beginTransaction();
    $clientName = trim((string) ($_POST['client_name'] ?? ''));
    $clientPhone = trim((string) ($_POST['client_phone'] ?? ''));
    $clientEmail = trim((string) ($_POST['client_email'] ?? ''));
    $clientId = find_or_create_client($clientName, $clientPhone, $clientEmail);
    $stmt = $pdo->prepare("
        INSERT INTO invoices
        (invoice_number, client_id, client_name, client_phone, client_email, invoice_date, payment_method, status, notes, subtotal, discount, tax_rate, tax_total, total, created_by, created_at, updated_at)
        VALUES (:invoice_number, :client_id, :client_name, :client_phone, :client_email, :invoice_date, :payment_method, :status, :notes, :subtotal, :discount, :tax_rate, :tax_total, :total, :created_by, :created_at, :updated_at)
    ");
    $now = date('c');
    $stmt->execute([
        'invoice_number' => next_invoice_number(),
        'client_id' => $clientId,
        'client_name' => $clientName,
        'client_phone' => $clientPhone,
        'client_email' => $clientEmail,
        'invoice_date' => $invoiceDate,
        'payment_method' => (string) ($_POST['payment_method'] ?? 'Efectivo'),
        'status' => (string) ($_POST['status'] ?? 'Pagada'),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
        'subtotal' => $subtotal,
        'discount' => $discount,
        'tax_rate' => $taxRate,
        'tax_total' => $taxTotal,
        'total' => $total,
        'created_by' => $_SESSION['admin_id'] ?? null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $invoiceId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare("
        INSERT INTO invoice_items (invoice_id, item_type, description, quantity, unit_price, line_total, duration_days, expires_at)
        VALUES (:invoice_id, :item_type, :description, :quantity, :unit_price, :line_total, :duration_days, :expires_at)
    ");
    foreach ($lines as $line) {
        $itemStmt->execute([
            'invoice_id' => $invoiceId,
            'item_type' => $line['type'],
            'description' => $line['description'],
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
            'line_total' => $line['line_total'],
            'duration_days' => $line['duration_days'],
            'expires_at' => $line['expires_at'],
        ]);
    }
    refresh_client_stats($clientId);
    $pdo->commit();
    flash('Factura guardada.');
    header('Location: invoice_view.php?id=' . $invoiceId);
    exit;
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash($e->getMessage(), 'error');
    header('Location: invoice.php');
    exit;
}
