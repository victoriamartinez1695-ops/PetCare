<?php

require_once __DIR__ . '/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !verify_csrf_token($submittedToken)) {
        $error = 'La sesión del formulario expiró. Recarga la página e intenta de nuevo.';
    } else {
        $email = isset($_POST['email']) && is_string($_POST['email'])
            ? trim($_POST['email'])
            : '';
        $password = isset($_POST['password']) && is_string($_POST['password'])
            ? $_POST['password']
            : '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $password === '') {
            $error = 'Correo o contraseña incorrectos.';
        } else {
            $statement = $pdo->prepare(
                'SELECT id, name, email, `password` FROM users WHERE email = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Correo o contraseña incorrectos.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                header('Location: dashboard.php');
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | PetCare</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body class="auth-page">
    <main class="auth-layout">
        <figure class="auth-cover">
            <img src="assets/petcare-auth-cover.png" alt="Ilustración de gatos y perros">
        </figure>
        <section class="card">
        <p class="eyebrow">PETCARE</p>
        <h1>Bienvenido de nuevo</h1>
        <p class="muted">Inicia sesión para continuar.</p>

        <?php if ($error !== ''): ?>
            <p class="alert" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="index.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="email">Correo electrónico</label>
            <input
                id="email"
                name="email"
                type="email"
                maxlength="254"
                autocomplete="email"
                value="<?= e($email) ?>"
                required
            >

            <label for="password">Contraseña</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Iniciar sesión</button>
        </form>

        <p class="footer-text">¿No tienes una cuenta? <a href="registro.php">Crear cuenta</a></p>
        </section>
    </main>
</body>
</html>
