<?php

require_once __DIR__ . '/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !verify_csrf_token($submittedToken)) {
        $error = 'La sesión del formulario expiró. Recarga la página e intenta de nuevo.';
    } else {
        $name = isset($_POST['name']) && is_string($_POST['name'])
            ? trim($_POST['name'])
            : '';
        $email = isset($_POST['email']) && is_string($_POST['email'])
            ? trim($_POST['email'])
            : '';
        $password = isset($_POST['password']) && is_string($_POST['password'])
            ? $_POST['password']
            : '';

        if ($name === '' || strlen($name) > 150) {
            $error = 'Escribe tu nombre (máximo 150 caracteres).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $error = 'Escribe un correo electrónico válido.';
        } elseif (strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO users (name, email, `password`) VALUES (:name, :email, :password)'
            );

            try {
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
            } catch (PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                    throw $exception;
                }

                $error = 'Ya existe una cuenta con ese correo electrónico.';
            }

            if ($error === '') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;

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
    <title>Crear cuenta | PetCare</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body class="auth-page">
    <main class="auth-layout">
        <figure class="auth-cover">
            <img src="assets/petcare-auth-cover.png" alt="Ilustración de gatos y perros">
        </figure>
        <section class="card">
        <p class="eyebrow">PETCARE</p>
        <h1>Crear cuenta</h1>
        <p class="muted">Regístrate para comenzar a organizar el cuidado de tus mascotas.</p>

        <?php if ($error !== ''): ?>
            <p class="alert" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="registro.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="name">Nombre</label>
            <input
                id="name"
                name="name"
                type="text"
                maxlength="150"
                autocomplete="name"
                value="<?= e($name) ?>"
                required
            >

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

            <label for="password">Contraseña (mínimo 8 caracteres)</label>
            <input
                id="password"
                name="password"
                type="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button type="submit">Crear cuenta</button>
        </form>

        <p class="footer-text">¿Ya tienes cuenta? <a href="index.php">Inicia sesión</a></p>
        </section>
    </main>
</body>
</html>
