<?php

require_once __DIR__ . '/auth.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$error = '';
$form = [
    'pet_id' => '',
    'reported_at' => date('Y-m-d'),
    'description' => '',
    'solution' => '',
    'status' => 'pending',
];

$petsStatement = $pdo->prepare('SELECT id, name FROM pets WHERE user_id = :user_id ORDER BY name');
$petsStatement->execute(['user_id' => $userId]);
$pets = $petsStatement->fetchAll();

$editingProblem = null;
if (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM health_problems WHERE id = :id AND user_id = :user_id');
    $statement->execute(['id' => $_GET['edit'], 'user_id' => $userId]);
    $editingProblem = $statement->fetch();

    if (!$editingProblem) {
        http_response_code(404);
        exit('No se encontró ese reporte de salud.');
    }

    foreach ($form as $key => $value) {
        $form[$key] = $editingProblem[$key] ?? '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !verify_csrf_token($token)) {
        $error = 'La sesión del formulario expiró. Recarga la página e intenta de nuevo.';
    } else {
        $action = post_string('action');
        $problemId = post_string('problem_id');

        if ($action === 'delete') {
            if ($problemId === '' || !ctype_digit($problemId)) {
                $error = 'No se pudo identificar el reporte.';
            } else {
                $statement = $pdo->prepare(
                    'DELETE FROM health_problems WHERE id = :id AND user_id = :user_id'
                );
                $statement->execute(['id' => $problemId, 'user_id' => $userId]);
                header('Location: dashboard.php?message=problem_deleted');
                exit;
            }
        } elseif ($action === 'save') {
            foreach ($form as $key => $value) {
                $form[$key] = post_string($key);
            }

            $validStatuses = ['pending', 'in_review', 'resolved'];

            if ($form['pet_id'] === '' || !ctype_digit($form['pet_id'])) {
                $error = 'Selecciona una mascota.';
            } elseif (!valid_date($form['reported_at'])) {
                $error = 'La fecha del reporte no es válida.';
            } elseif ($form['description'] === '' || strlen($form['description']) > 10000) {
                $error = 'La descripción es obligatoria y no puede superar 10 000 caracteres.';
            } elseif (strlen($form['solution']) > 10000) {
                $error = 'La solución no puede superar 10 000 caracteres.';
            } elseif (!in_array($form['status'], $validStatuses, true)) {
                $error = 'Selecciona un estado válido.';
            } elseif ($form['status'] === 'resolved' && $form['solution'] === '') {
                $error = 'Registra la solución indicada antes de marcar el problema como resuelto.';
            } else {
                $ownerCheck = $pdo->prepare('SELECT id FROM pets WHERE id = :id AND user_id = :user_id');
                $ownerCheck->execute(['id' => $form['pet_id'], 'user_id' => $userId]);

                if (!$ownerCheck->fetch()) {
                    $error = 'La mascota seleccionada no pertenece a tu cuenta.';
                } else {
                    $values = [
                        'user_id' => $userId,
                        'pet_id' => $form['pet_id'],
                        'reported_at' => $form['reported_at'],
                        'description' => $form['description'],
                        'solution' => $form['solution'] === '' ? null : $form['solution'],
                        'status' => $form['status'],
                    ];

                    if ($problemId !== '' && ctype_digit($problemId)) {
                        $statement = $pdo->prepare(
                            'UPDATE health_problems
                             SET pet_id = :pet_id, reported_at = :reported_at,
                                 description = :description, solution = :solution, status = :status
                             WHERE id = :id AND user_id = :user_id'
                        );
                        $values['id'] = $problemId;
                        $statement->execute($values);
                    } else {
                        $statement = $pdo->prepare(
                            'INSERT INTO health_problems
                             (user_id, pet_id, reported_at, description, solution, status)
                             VALUES (:user_id, :pet_id, :reported_at, :description, :solution, :status)'
                        );
                        $statement->execute($values);
                    }

                    header('Location: dashboard.php?message=problem_saved');
                    exit;
                }
            }

            if ($problemId !== '' && ctype_digit($problemId)) {
                $statement = $pdo->prepare(
                    'SELECT id FROM health_problems WHERE id = :id AND user_id = :user_id'
                );
                $statement->execute(['id' => $problemId, 'user_id' => $userId]);
                $editingProblem = $statement->fetch();
                if (!$editingProblem) {
                    http_response_code(404);
                    exit('No se encontró ese reporte de salud.');
                }
            }
        } else {
            $error = 'La acción solicitada no es válida.';
        }
    }
}

$problemStatement = $pdo->prepare(
    'SELECT health_problems.id, health_problems.pet_id, health_problems.reported_at,
            health_problems.description, health_problems.solution,
            health_problems.status, pets.name AS pet_name
     FROM health_problems
     INNER JOIN pets ON pets.id = health_problems.pet_id
                   AND pets.user_id = health_problems.user_id
     WHERE health_problems.user_id = :user_id
     ORDER BY health_problems.reported_at DESC, health_problems.id DESC'
);
$problemStatement->execute(['user_id' => $userId]);
$problems = $problemStatement->fetchAll();

$statusLabels = [
    'pending' => 'Pendiente',
    'in_review' => 'En revisión',
    'resolved' => 'Resuelto',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Problemas de salud | PetCare</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body class="app-body">
    <div class="dashboard-frame">
    <?php require __DIR__ . '/app_sidebar.php'; ?>
    <main class="app-shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">PETCARE</p>
                <h1>Problemas de salud</h1>
                <p class="muted">Registra síntomas o problemas observados para darles seguimiento.</p>
            </div>
            <a class="button-link" href="dashboard.php">Volver al panel</a>
        </header>

        <section class="section">
            <div class="section-heading">
                <div>
                    <h2><?= $editingProblem ? 'Editar reporte' : 'Nuevo reporte' ?></h2>
                    <p class="muted">Los reportes quedan asociados a una mascota de tu cuenta.</p>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <p class="alert" role="alert"><?= e($error) ?></p>
            <?php endif; ?>

            <?php if ($pets === []): ?>
                <div class="empty-state">
                    <p>Primero registra una mascota para poder asociar un reporte de salud.</p>
                    <a href="mascotas.php">Registrar mascota</a>
                </div>
            <?php else: ?>
                <form class="report-form" method="post" action="problemas.php<?= $editingProblem ? '?edit=' . (int) $editingProblem['id'] : '' ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save">
                    <?php if ($editingProblem): ?>
                        <input type="hidden" name="problem_id" value="<?= (int) $editingProblem['id'] ?>">
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

                    <label for="reported_at">Fecha del reporte *</label>
                    <input id="reported_at" name="reported_at" type="date" value="<?= e($form['reported_at']) ?>" required>

                    <label for="description">Descripción del problema *</label>
                    <textarea id="description" name="description" rows="4" maxlength="10000" required><?= e($form['description']) ?></textarea>

                    <label for="solution">Solución o tratamiento indicado</label>
                    <textarea id="solution" name="solution" rows="4" maxlength="10000" placeholder="Escribe la solución o el tratamiento indicado. Puedes completarlo al editar el reporte."><?= e($form['solution']) ?></textarea>

                    <label for="status">Estado</label>
                    <select id="status" name="status">
                        <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $form['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit"><?= $editingProblem ? 'Guardar cambios' : 'Guardar reporte' ?></button>
                    <?php if ($editingProblem): ?>
                        <a class="button-link button-link-secondary" href="problemas.php">Cancelar edición</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </section>

        <section class="section">
            <div class="section-heading">
                <div>
                    <h2>Reportes registrados</h2>
                    <p class="muted"><?= count($problems) ?> reporte(s)</p>
                </div>
            </div>

            <?php if ($problems === []): ?>
                <div class="empty-state">
                    <p>No hay reportes de salud registrados.</p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($problems as $problem): ?>
                        <article class="item-card">
                            <div class="item-heading">
                                <div>
                                    <h3><?= e($problem['pet_name']) ?></h3>
                                    <p class="muted">Reportado el <?= e($problem['reported_at']) ?></p>
                                </div>
                                <span class="badge status-<?= e($problem['status']) ?>">
                                    <?= e($statusLabels[$problem['status']] ?? $problem['status']) ?>
                                </span>
                            </div>
                            <p class="report-description"><?= nl2br(e($problem['description'])) ?></p>
                            <div class="solution-block">
                                <h4>Solución / tratamiento</h4>
                                <?php if ($problem['solution'] !== null && $problem['solution'] !== ''): ?>
                                    <p><?= nl2br(e($problem['solution'])) ?></p>
                                <?php else: ?>
                                    <p class="muted">Aún no se ha registrado una solución.</p>
                                <?php endif; ?>
                            </div>
                            <div class="report-actions">
                                <a href="problemas.php?edit=<?= (int) $problem['id'] ?>">Editar</a>
                                <form method="post" action="problemas.php" onsubmit="return confirm('¿Eliminar este reporte de salud?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="problem_id" value="<?= (int) $problem['id'] ?>">
                                    <button class="danger" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    </div>
</body>
</html>
