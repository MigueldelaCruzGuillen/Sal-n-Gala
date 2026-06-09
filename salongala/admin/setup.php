<?php
require __DIR__ . '/../app/bootstrap.php';

if (admin_count() > 0) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = filter_var((string) ($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || !$email || strlen($password) < 12) {
        $error = 'Completa los datos. La contraseña debe tener al menos 12 caracteres.';
    } else {
        $stmt = db()->prepare("INSERT INTO admins (name, email, password_hash, created_at) VALUES (:name, :email, :hash, :created)");
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'created' => date('c'),
        ]);
        flash('Administrador creado. Ya puedes iniciar sesión.');
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear administrador · Galá</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-auth">
  <main class="auth-card">
    <span>Primer acceso</span>
    <h1>Crear administrador</h1>
    <p>Este paso solo aparece si no existe un usuario administrador.</p>
    <?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <label>Nombre<input name="name" required maxlength="80"></label>
      <label>Email<input name="email" type="email" required maxlength="120"></label>
      <label>Contraseña<input name="password" type="password" required minlength="12"></label>
      <button type="submit">Crear acceso seguro</button>
    </form>
  </main>
</body>
</html>
