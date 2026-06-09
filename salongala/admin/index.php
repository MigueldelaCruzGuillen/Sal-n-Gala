<?php
require __DIR__ . '/../app/bootstrap.php';

if (admin_count() === 0) {
    header('Location: setup.php');
    exit;
}
if (is_admin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = filter_var((string) ($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $stmt = db()->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email ?: '']);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Credenciales inválidas.';
}
$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin · Galá</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-auth">
  <main class="auth-card">
    <span>Panel privado</span>
    <h1>Galá Admin</h1>
    <p>Gestiona productos, servicios, reseñas y fotos reales sin mostrar el panel al público.</p>
    <?php if ($flash): ?><div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <label>Email<input name="email" type="email" required></label>
      <label>Contraseña<input name="password" type="password" required></label>
      <button type="submit">Entrar</button>
    </form>
  </main>
</body>
</html>
