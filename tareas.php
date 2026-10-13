<?php

require_once __DIR__ . '/auth.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$error = '';
$petsStatement = $pdo->prepare('SELECT id, name FROM pets WHERE user_id = :user_id ORDER BY name');
$petsStatement->execute(['user_id' => $userId]);
$pets = $petsStatement->fetchAll();

$form = [
    'pet_id' => '',
    'title' => '',
    'description' => '',
    'category' => '',
    'date' => '',
    'time' => '',
    'priority' => 'medium',
    'status' => 'pending',
];
$editingTask = null;

if (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM tasks WHERE id = :id AND user_id = :user_id');
    $statement->execute(['id' => $_GET['edit'], 'user_id' => $userId]);
    $editingTask = $statement->fetch();

    if (!$editingTask) {
        http_response_code(404);
        exit('No se encontró esa tarea.');
    }

    foreach ($form as $key => $value) {
        $form[$key] = $editingTask[$key] ?? '';
    }
    if ($form['time'] !== '') {
        $form['time'] = substr($form['time'], 0, 5);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !verify_csrf_token($token)) {
        $error = 'La sesión del formulario expiró. Recarga la página e intenta de nuevo.';
    } else {
        $action = post_string('action');
        $taskId = post_string('task_id');

        if ($action === 'delete') {
            if ($taskId === '' || !ctype_digit($taskId)) {
                $error = 'No se pudo identificar la tarea.';
            } else {
                $statement = $pdo->prepare('DELETE FROM tasks WHERE id = :id AND user_id = :user_id');
                $statement->execute(['id' => $taskId, 'user_id' => $userId]);
                header('Location: dashboard.php?message=task_deleted');
                exit;
            }
        } elseif ($action === 'save') {
            foreach ($form as $key => $value) {
                $form[$key] = post_string($key);
            }

            $validPriority = in_array($form['priority'], ['low', 'medium', 'high'], true);
            $validStatus = in_array($form['status'], ['pending', 'completed'], true);
            $validTime = $form['time'] === ''
                || (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $form['time']);

            if ($form['pet_id'] === '' || !ctype_digit($form['pet_id'])) {
                $error = 'Selecciona una mascota.';
            } elseif ($form['title'] === '' || strlen($form['title']) > 200) {
                $error = 'El título es obligatorio y no puede superar 200 caracteres.';
            } elseif ($form['category'] === '' || strlen($form['category']) > 80) {
                $error = 'La categoría es obligatoria y no puede superar 80 caracteres.';
            } elseif (!valid_date($form['date'])) {
                $error = 'La fecha de la tarea no es válida.';
            } elseif (!$validTime) {
                $error = 'La hora debe tener un formato válido.';
            } elseif (!$validPriority || !$validStatus) {
                $error = 'La prioridad o el estado seleccionados no son válidos.';
            } else {
                $ownerCheck = $pdo->prepare('SELECT id FROM pets WHERE id = :id AND user_id = :user_id');
                $ownerCheck->execute(['id' => $form['pet_id'], 'user_id' => $userId]);

                if (!$ownerCheck->fetch()) {
                    $error = 'La mascota seleccionada no pertenece a tu cuenta.';
                } else {
                    $values = [
                        'user_id' => $userId,
                        'pet_id' => $form['pet_id'],
                        'title' => $form['title'],
                        'description' => $form['description'] === '' ? null : $form['description'],
                        'category' => $form['category'],
                        'date' => $form['date'],
                        'time' => $form['time'] === '' ? null : $form['time'] . ':00',
                        'priority' => $form['priority'],
                        'status' => $form['status'],
                    ];

                    if ($taskId !== '' && ctype_digit($taskId)) {
                        $statement = $pdo->prepare(
                            'UPDATE tasks SET pet_id = :pet_id, title = :title, description = :description,
                             category = :category, `date` = :date, `time` = :time, priority = :priority,
                             status = :status WHERE id = :id AND user_id = :user_id'
                        );
                        $values['id'] = $taskId;
                        $statement->execute($values);
                    } else {
                        $statement = $pdo->prepare(
                            'INSERT INTO tasks
                             (user_id, pet_id, title, description, category, `date`, `time`, priority, status)
                             VALUES (:user_id, :pet_id, :title, :description, :category, :date, :time, :priority, :status)'
                        );
                        $statement->execute($values);
                    }

                    header('Location: dashboard.php?message=task_saved');
                    exit;
                }
            }

            if ($taskId !== '' && ctype_digit($taskId)) {
                $statement = $pdo->prepare('SELECT id FROM tasks WHERE id = :id AND user_id = :user_id');
                $statement->execute(['id' => $taskId, 'user_id' => $userId]);
                $editingTask = $statement->fetch();
                if (!$editingTask) {
                    http_response_code(404);
                    exit('No se encontró esa tarea.');
                }
            }
        } else {
            $error = 'La acción solicitada no es válida.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $editingTask ? 'Editar tarea' : 'Agregar tarea' ?> | PetCare</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body class="app-body">
    <div class="dashboard-frame">
    <?php require __DIR__ . '/app_sidebar.php'; ?>
    <main class="card form-card">
        <p class="eyebrow">PETCARE</p>
        <h1><?= $editingTask ? 'Editar tarea' : 'Agregar tarea' ?></h1>
        <p class="muted">Programa y actualiza los cuidados de tus mascotas.</p>

        <?php if ($error !== ''): ?>
            <p class="alert" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <?php if ($pets === []): ?>
            <div class="empty-state">
                <p>Primero necesitas registrar una mascota para asignarle tareas.</p>
                <a href="mascotas.php">Registrar mascota</a>
            </div>
        <?php else: ?>
            <form method="post" action="tareas.php<?= $editingTask ? '?edit=' . (int) $editingTask['id'] : '' ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <?php if ($editingTask): ?>
                    <input type="hidden" name="task_id" value="<?= (int) $editingTask['id'] ?>">
                <?php endif; ?>

                <label for="pet_id">Mascota *</label>
                <select id="pet_id" name="pet_id" required>
                    <option value="">Selecciona una mascota</option>
                    <?php foreach ($pets as $pet): ?>
                        <option value="<?= (int) $pet['id'] ?>" <?= (string) $form['pet_id'] === (string) $pet['id'] ? 'selected' : '' ?>>
                            <?= e($pet['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="title">Título *</label>
                <input id="title" name="title" type="text" maxlength="200" value="<?= e($form['title']) ?>" required>

                <label for="description">Descripción</label>
                <textarea id="description" name="description" rows="4"><?= e($form['description']) ?></textarea>

                <label for="category">Categoría *</label>
                <input id="category" name="category" type="text" maxlength="80" placeholder="Veterinario, alimentación..." value="<?= e($form['category']) ?>" required>

                <label for="date">Fecha *</label>
                <input id="date" name="date" type="date" value="<?= e($form['date']) ?>" required>

                <label for="time">Hora</label>
                <input id="time" name="time" type="time" value="<?= e($form['time']) ?>">

                <label for="priority">Prioridad</label>
                <select id="priority" name="priority">
                    <?php foreach (['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $form['priority'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="status">Estado</label>
                <select id="status" name="status">
                    <option value="pending" <?= $form['status'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="completed" <?= $form['status'] === 'completed' ? 'selected' : '' ?>>Completada</option>
                </select>

                <button type="submit"><?= $editingTask ? 'Guardar cambios' : 'Guardar tarea' ?></button>
            </form>

            <?php if ($editingTask): ?>
                <form method="post" action="tareas.php" onsubmit="return confirm('¿Eliminar esta tarea?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="task_id" value="<?= (int) $editingTask['id'] ?>">
                    <button class="danger" type="submit">Eliminar tarea</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <p class="footer-text"><a href="dashboard.php">Volver al panel</a></p>
    </main>
    </div>
</body>
</html>
