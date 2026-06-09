<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

verify_csrf();
$action = (string) ($_POST['action'] ?? '');

try {
    if ($action === 'settings') {
        $allowed = ['brand', 'legal_name', 'tagline', 'intro', 'phone', 'whatsapp', 'address', 'hours', 'google_rating', 'google_reviews', 'instagram', 'tiktok', 'hero_image', 'invoice_footer', 'invoice_tax_rate', 'currency'];
        $stmt = db()->prepare("INSERT INTO settings (key, value) VALUES (:key, :value) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
        foreach ($allowed as $key) {
            $stmt->execute(['key' => $key, 'value' => trim((string) ($_POST['settings'][$key] ?? ''))]);
        }
        foreach (['invoice_logo_file' => 'invoice_logo', 'invoice_signature_file' => 'invoice_signature'] as $field => $key) {
            $uploaded = isset($_FILES[$field]) ? save_upload($_FILES[$field]) : null;
            if ($uploaded) {
                $stmt->execute(['key' => $key, 'value' => $uploaded]);
            }
        }
        flash('Ajustes actualizados.');
    }

    if ($action === 'item') {
        $type = (string) ($_POST['type'] ?? '');
        if (!in_array($type, ['service', 'product', 'testimonial', 'gallery'], true)) {
            throw new RuntimeException('Tipo inválido.');
        }
        $uploaded = isset($_FILES['image_file']) ? save_upload($_FILES['image_file']) : null;
        $image = $uploaded ?: trim((string) ($_POST['image_url'] ?? ''));
        if ($image === '') {
            throw new RuntimeException('Agrega una imagen por URL o subida.');
        }

        $stmt = db()->prepare("
            INSERT INTO content_items
            (type, title, subtitle, body, image, price, rating, sort_order, created_at, updated_at)
            VALUES (:type, :title, :subtitle, :body, :image, :price, :rating, :sort_order, :created_at, :updated_at)
        ");
        $stmt->execute([
            'type' => $type,
            'title' => trim((string) ($_POST['title'] ?? '')),
            'subtitle' => trim((string) ($_POST['subtitle'] ?? '')),
            'body' => trim((string) ($_POST['body'] ?? '')),
            'image' => $image,
            'price' => trim((string) ($_POST['price'] ?? '')),
            'rating' => max(1, min(5, (int) ($_POST['rating'] ?? 5))),
            'sort_order' => (int) ($_POST['sort_order'] ?? 10),
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ]);
        flash('Contenido publicado.');
    }

    if ($action === 'delete') {
        $stmt = db()->prepare("UPDATE content_items SET is_active = 0, updated_at = :updated WHERE id = :id");
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0), 'updated' => date('c')]);
        flash('Elemento ocultado.');
    }
} catch (Throwable $e) {
    flash($e->getMessage(), 'error');
}

header('Location: dashboard.php');
exit;
