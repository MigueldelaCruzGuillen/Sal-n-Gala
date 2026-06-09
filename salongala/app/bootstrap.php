<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'storage');
define('DB_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'gala.sqlite');
define('SESSION_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'sessions');
define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL', 'assets/uploads');

foreach ([STORAGE_PATH, SESSION_PATH, UPLOAD_PATH] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . DIRECTORY_SEPARATOR . 'php-error.log');
session_save_path(SESSION_PATH);
date_default_timezone_set('America/Santo_Domingo');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    migrate($pdo);
    seed($pdo);

    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS content_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL,
            title TEXT NOT NULL,
            subtitle TEXT DEFAULT '',
            body TEXT DEFAULT '',
            image TEXT DEFAULT '',
            price TEXT DEFAULT '',
            rating INTEGER DEFAULT 5,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT DEFAULT '',
            email TEXT DEFAULT '',
            notes TEXT DEFAULT '',
            first_visit TEXT DEFAULT '',
            last_visit TEXT DEFAULT '',
            total_spent REAL NOT NULL DEFAULT 0,
            visit_count INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_number TEXT NOT NULL UNIQUE,
            client_id INTEGER,
            client_name TEXT NOT NULL,
            client_phone TEXT DEFAULT '',
            client_email TEXT DEFAULT '',
            invoice_date TEXT NOT NULL,
            payment_method TEXT DEFAULT 'Efectivo',
            status TEXT DEFAULT 'Pagada',
            notes TEXT DEFAULT '',
            subtotal REAL NOT NULL DEFAULT 0,
            discount REAL NOT NULL DEFAULT 0,
            tax_rate REAL NOT NULL DEFAULT 0,
            tax_total REAL NOT NULL DEFAULT 0,
            total REAL NOT NULL DEFAULT 0,
            created_by INTEGER,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY(client_id) REFERENCES clients(id),
            FOREIGN KEY(created_by) REFERENCES admins(id)
        );

        CREATE TABLE IF NOT EXISTS invoice_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_id INTEGER NOT NULL,
            item_type TEXT NOT NULL DEFAULT 'service',
            description TEXT NOT NULL,
            quantity REAL NOT NULL DEFAULT 1,
            unit_price REAL NOT NULL DEFAULT 0,
            line_total REAL NOT NULL DEFAULT 0,
            duration_days INTEGER DEFAULT 0,
            expires_at TEXT DEFAULT '',
            FOREIGN KEY(invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        );
    ");

    ensure_column($pdo, 'invoices', 'client_id', 'INTEGER');
    ensure_column($pdo, 'invoice_items', 'duration_days', 'INTEGER DEFAULT 0');
    ensure_column($pdo, 'invoice_items', 'expires_at', "TEXT DEFAULT ''");
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $columns = $pdo->query("PRAGMA table_info($table)")->fetchAll();
    foreach ($columns as $existing) {
        if (($existing['name'] ?? '') === $column) {
            return;
        }
    }
    $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
}

function ensure_runtime_defaults(PDO $pdo): void
{
    $defaults = [
        'invoice_logo' => '',
        'invoice_signature' => '',
        'invoice_footer' => 'Gracias por confiar en Galá. Tu cabello merece cuidado profesional.',
        'invoice_tax_rate' => '0',
        'currency' => 'RD$',
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)");
    foreach ($defaults as $key => $value) {
        $stmt->execute(['key' => $key, 'value' => $value]);
    }
}

function seed(PDO $pdo): void
{
    $count = (int) $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    if ($count > 0) {
        return;
    }

    $settings = [
        'brand' => 'Galá',
        'legal_name' => 'Galá Centro de Salud Capilar Integrativa',
        'tagline' => 'Transformamos tu cabello, elevamos tu confianza.',
        'intro' => 'Salón de belleza y tricología aplicada a la cosmética capilar en Santo Domingo.',
        'phone' => '(809) 866-6064',
        'whatsapp' => '18098666064',
        'address' => 'C. Gardenia 3, Santo Domingo 10601, Dominican Republic',
        'hours' => 'Lunes a sábado · hasta las 7:00 p.m.',
        'google_rating' => '5.0',
        'google_reviews' => '53 opiniones en Google',
        'instagram' => '#',
        'tiktok' => '#',
        'hero_image' => 'https://images.unsplash.com/photo-1600948836101-f9ffda59d250?auto=format&fit=crop&w=2200&q=90',
    ];

    $stmt = $pdo->prepare("INSERT INTO settings (key, value) VALUES (:key, :value)");
    foreach ($settings as $key => $value) {
        $stmt->execute(['key' => $key, 'value' => $value]);
    }

    $items = [
        ['service', 'Salud capilar', 'Diagnóstico y protocolos integrativos', 'Fortalecemos fibra, cuero cabelludo y brillo natural con atención personalizada.', 'https://images.unsplash.com/photo-1522337660859-02fbefca4702?auto=format&fit=crop&w=900&q=85', '', 5, 10],
        ['service', 'Tratamientos hidratantes', 'Nutrición profunda', 'Hidratación, reparación y sellado para un acabado suave y saludable.', 'https://images.unsplash.com/photo-1621607512214-68297480165e?auto=format&fit=crop&w=900&q=85', '', 5, 20],
        ['service', 'Coloración', 'Tonos modernos y corrección', 'Rubios elegantes, matices, color global y cuidado de la fibra durante el proceso.', 'https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?auto=format&fit=crop&w=900&q=85', '', 5, 30],
        ['service', 'Alisados', 'Control de frizz', 'Disciplina, brillo y movimiento sedoso con protocolos a medida.', 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=900&q=85', '', 5, 40],
        ['service', 'Corte y styling', 'Acabado profesional', 'Diseños favorecedores, blowout pulido y peinados para ocasiones especiales.', 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=900&q=85', '', 5, 50],
        ['service', 'Evaluación capilar', 'Plan personalizado', 'Revisión profesional para elegir el tratamiento ideal según tu necesidad real.', 'https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?auto=format&fit=crop&w=900&q=85', '', 5, 60],
        ['product', 'Clean Balance', 'Shampoo premium', 'Limpieza suave para cuero cabelludo sensible.', 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=700&q=85', 'Desde RD$ 1,450', 5, 10],
        ['product', 'Silk Repair', 'Mascarilla capilar', 'Nutrición cremosa para brillo, suavidad y elasticidad.', 'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&w=700&q=85', 'Desde RD$ 1,900', 5, 20],
        ['product', 'Gloss Elixir', 'Aceite capilar', 'Sellado liviano con acabado luminoso.', 'https://images.unsplash.com/photo-1619451334792-150fd785ee74?auto=format&fit=crop&w=700&q=85', 'Desde RD$ 1,250', 5, 30],
        ['product', 'Root Therapy', 'Tratamiento profesional', 'Cuidado focalizado para fuerza desde la raíz.', 'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&w=700&q=85', 'Desde RD$ 2,200', 5, 40],
        ['testimonial', 'Clienta Galá', 'Resumen de Google', 'Atención personalizada, ambiente acogedor y resultados profesionales.', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=300&q=85', '', 5, 10],
        ['testimonial', 'Clienta Galá', 'Resumen de Google', 'Excelente experiencia para cuidado capilar, coloración y belleza integral.', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=85', '', 5, 20],
        ['testimonial', 'Clienta Galá', 'Resumen de Google', 'Servicio profesional y trato cálido desde la llegada hasta el acabado final.', 'https://images.unsplash.com/photo-1524250502761-1ac6f2e30d43?auto=format&fit=crop&w=300&q=85', '', 5, 30],
        ['gallery', 'Cabello saludable', 'Resultado premium', '', 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?auto=format&fit=crop&w=800&q=85', '', 5, 10],
        ['gallery', 'Interior del salón', 'Ambiente moderno', '', 'https://images.unsplash.com/photo-1600948836101-f9ffda59d250?auto=format&fit=crop&w=800&q=85', '', 5, 20],
        ['gallery', 'Beauty spa', 'Cuidado femenino', '', 'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=1000&q=85', '', 5, 30],
        ['gallery', 'Antes y después', 'Transformación capilar', '', 'https://images.unsplash.com/photo-1559599101-f09722fb4948?auto=format&fit=crop&w=800&q=85', '', 5, 40],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO content_items
        (type, title, subtitle, body, image, price, rating, sort_order, created_at, updated_at)
        VALUES (:type, :title, :subtitle, :body, :image, :price, :rating, :sort_order, :created_at, :updated_at)
    ");
    $now = date('c');
    foreach ($items as $item) {
        $stmt->execute([
            'type' => $item[0],
            'title' => $item[1],
            'subtitle' => $item[2],
            'body' => $item[3],
            'image' => $item[4],
            'price' => $item[5],
            'rating' => $item[6],
            'sort_order' => $item[7],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setting(string $key, string $default = ''): string
{
    $stmt = db()->prepare("SELECT value FROM settings WHERE key = :key");
    $stmt->execute(['key' => $key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string) $value;
}

function settings_all(): array
{
    $rows = db()->query("SELECT key, value FROM settings")->fetchAll();
    return array_column($rows, 'value', 'key');
}

function items(string $type, int $limit = 24): array
{
    $stmt = db()->prepare("
        SELECT * FROM content_items
        WHERE type = :type AND is_active = 1
        ORDER BY sort_order ASC, id DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':type', $type);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function catalog_items(): array
{
    return db()->query("
        SELECT id, type, title, subtitle, price
        FROM content_items
        WHERE is_active = 1 AND type IN ('service', 'product')
        ORDER BY type ASC, sort_order ASC, title ASC
    ")->fetchAll();
}

function money(float $amount): string
{
    return setting('currency', 'RD$') . ' ' . number_format($amount, 2, '.', ',');
}

function whatsapp_number(string $phone, string $fallback = ''): string
{
    $number = preg_replace('/\D+/', '', $phone) ?: preg_replace('/\D+/', '', $fallback);
    if (strlen($number) === 10 && in_array(substr($number, 0, 3), ['809', '829', '849'], true)) {
        return '1' . $number;
    }
    return $number;
}

function next_invoice_number(): string
{
    $prefix = 'FAC-' . date('Ymd') . '-';
    $stmt = db()->prepare("SELECT COUNT(*) FROM invoices WHERE invoice_number LIKE :prefix");
    $stmt->execute(['prefix' => $prefix . '%']);
    return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
}

function invoice_stats(): array
{
    $pdo = db();
    $today = date('Y-m-d');
    $month = date('Y-m');

    $daily = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE invoice_date = :day AND status != 'Anulada'");
    $daily->execute(['day' => $today]);
    $monthly = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE substr(invoice_date, 1, 7) = :month AND status != 'Anulada'");
    $monthly->execute(['month' => $month]);
    $countMonth = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE substr(invoice_date, 1, 7) = :month AND status != 'Anulada'");
    $countMonth->execute(['month' => $month]);

    $last7 = $pdo->query("
        SELECT invoice_date, COALESCE(SUM(total), 0) AS total
        FROM invoices
        WHERE invoice_date >= date('now', '-6 day') AND status != 'Anulada'
        GROUP BY invoice_date
        ORDER BY invoice_date ASC
    ")->fetchAll();

    return [
        'today' => (float) $daily->fetchColumn(),
        'month' => (float) $monthly->fetchColumn(),
        'month_count' => (int) $countMonth->fetchColumn(),
        'last7' => $last7,
    ];
}

function normalize_phone(string $phone): string
{
    return preg_replace('/\D+/', '', $phone);
}

function find_or_create_client(string $name, string $phone = '', string $email = ''): int
{
    $pdo = db();
    $name = trim($name);
    $phone = trim($phone);
    $email = trim($email);
    $normalized = normalize_phone($phone);

    if ($normalized !== '') {
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '') LIKE :phone LIMIT 1");
        $stmt->execute(['phone' => '%' . $normalized . '%']);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
    }

    if ($email !== '') {
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE lower(email) = lower(:email) LIMIT 1");
        $stmt->execute(['email' => $email]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
    }

    $stmt = $pdo->prepare("SELECT id FROM clients WHERE lower(name) = lower(:name) LIMIT 1");
    $stmt->execute(['name' => $name]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $now = date('c');
    $stmt = $pdo->prepare("
        INSERT INTO clients (name, phone, email, created_at, updated_at)
        VALUES (:name, :phone, :email, :created_at, :updated_at)
    ");
    $stmt->execute([
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    return (int) $pdo->lastInsertId();
}

function refresh_client_stats(int $clientId): void
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(total), 0) AS total_spent,
            COUNT(*) AS visit_count,
            MIN(invoice_date) AS first_visit,
            MAX(invoice_date) AS last_visit
        FROM invoices
        WHERE client_id = :client_id AND status != 'Anulada'
    ");
    $stmt->execute(['client_id' => $clientId]);
    $stats = $stmt->fetch();

    $update = $pdo->prepare("
        UPDATE clients
        SET total_spent = :total_spent, visit_count = :visit_count, first_visit = :first_visit, last_visit = :last_visit, updated_at = :updated_at
        WHERE id = :id
    ");
    $update->execute([
        'total_spent' => (float) ($stats['total_spent'] ?? 0),
        'visit_count' => (int) ($stats['visit_count'] ?? 0),
        'first_visit' => (string) ($stats['first_visit'] ?? ''),
        'last_visit' => (string) ($stats['last_visit'] ?? ''),
        'updated_at' => date('c'),
        'id' => $clientId,
    ]);
}

function client_leaders(): array
{
    $pdo = db();
    $topSpent = $pdo->query("SELECT * FROM clients ORDER BY total_spent DESC, visit_count DESC LIMIT 1")->fetch();
    $mostFaithful = $pdo->query("SELECT * FROM clients ORDER BY visit_count DESC, total_spent DESC LIMIT 1")->fetch();
    return ['top_spent' => $topSpent ?: null, 'most_faithful' => $mostFaithful ?: null];
}

function backfill_clients_from_invoices(): void
{
    $stmt = db()->query("SELECT * FROM invoices WHERE client_id IS NULL OR client_id = 0");
    foreach ($stmt->fetchAll() as $invoice) {
        $clientId = find_or_create_client($invoice['client_name'], $invoice['client_phone'], $invoice['client_email']);
        $update = db()->prepare("UPDATE invoices SET client_id = :client_id WHERE id = :id");
        $update->execute(['client_id' => $clientId, 'id' => $invoice['id']]);
        refresh_client_stats($clientId);
    }
}

function days_remaining(?string $expiresAt): ?int
{
    if (!$expiresAt) {
        return null;
    }
    $today = new DateTimeImmutable(date('Y-m-d'));
    $expires = new DateTimeImmutable($expiresAt);
    return (int) $today->diff($expires)->format('%r%a');
}

function admin_count(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM admins")->fetchColumn();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Solicitud inválida.');
    }
}

function is_admin(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function consume_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function stars(int $rating): string
{
    return str_repeat('★', max(1, min(5, $rating)));
}

function image_url(string $path): string
{
    if (preg_match('/^https?:\/\//', $path)) {
        return $path;
    }
    return UPLOAD_URL . '/' . ltrim($path, '/');
}

function save_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException('La imagen no pudo subirse o supera 4MB.');
    }

    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new RuntimeException('Solo se permiten imágenes JPG, PNG o WEBP.');
    }

    $ext = match ($info['mime']) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = UPLOAD_PATH . DIRECTORY_SEPARATOR . $name;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    chmod($target, 0644);
    return $name;
}

db();
ensure_runtime_defaults(db());
