<?php

require_once __DIR__ . '/auth.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$userName = isset($_SESSION['user_name']) && is_string($_SESSION['user_name'])
    ? $_SESSION['user_name']
    : 'Usuario';

$petStatement = $pdo->prepare(
    'SELECT id, name, `type`, breed, birth_date, gender, photo, notes
     FROM pets WHERE user_id = :user_id ORDER BY name'
);
$petStatement->execute(['user_id' => $userId]);
$pets = $petStatement->fetchAll();

$taskStatement = $pdo->prepare(
    'SELECT tasks.id, tasks.pet_id, tasks.title, tasks.description, tasks.category,
            tasks.`date`, tasks.`time`, tasks.priority, tasks.status, pets.name AS pet_name
     FROM tasks
     INNER JOIN pets ON pets.id = tasks.pet_id AND pets.user_id = tasks.user_id
     WHERE tasks.user_id = :user_id
     ORDER BY (tasks.status = \'pending\') DESC, tasks.`date`, tasks.`time`, tasks.id DESC'
);
$taskStatement->execute(['user_id' => $userId]);
$tasks = $taskStatement->fetchAll();

$message = isset($_GET['message']) && is_string($_GET['message']) ? $_GET['message'] : '';
$messages = [
    'pet_saved' => 'La mascota se guardó correctamente.',
    'pet_deleted' => 'La mascota y sus tareas se eliminaron.',
    'task_saved' => 'La tarea se guardó correctamente.',
    'task_deleted' => 'La tarea se eliminó.',
    'problem_saved' => 'El reporte de salud se guardó correctamente.',
    'problem_deleted' => 'El reporte de salud se eliminó.',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel | PetCare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
    <main class="app-shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">PETCARE</p>
                <h1>Hola, <?= e($userName) ?></h1>
                <p class="muted">Organiza el cuidado de tus mascotas y sus tareas.</p>
            </div>
            <div class="topbar-actions">
                <a class="button-link" href="problemas.php">Problemas de salud</a>
                <form class="logout-form" method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="secondary" type="submit">Cerrar sesión</button>
                </form>
            </div>
        </header>

        <?php if (isset($messages[$message])): ?>
            <p class="success" role="status"><?= e($messages[$message]) ?></p>
        <?php endif; ?>

        <section class="section">
            <div class="section-heading">
                <div>
                    <h2>Mis mascotas</h2>
                    <p class="muted"><?= count($pets) ?> registrada(s)</p>
                </div>
                <a class="button-link" href="mascotas.php">+ Agregar mascota</a>
            </div>

            <?php if ($pets === []): ?>
                <div class="empty-state">
                    <p>Aún no tienes mascotas registradas.</p>
                    <a href="mascotas.php">Registra la primera</a>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($pets as $pet): ?>
                        <article class="item-card">
                            <?php if (($photoSrc = pet_photo_src($pet['photo'])) !== null): ?>
                                <button
                                    class="photo-trigger"
                                    type="button"
                                    data-photo-src="<?= e($photoSrc) ?>"
                                    data-photo-alt="Foto de <?= e($pet['name']) ?>"
                                    aria-label="Ampliar foto de <?= e($pet['name']) ?>"
                                >
                                    <img class="pet-photo" src="<?= e($photoSrc) ?>" alt="">
                                </button>
                            <?php endif; ?>
                            <div class="item-heading">
                                <div>
                                    <h3><?= e($pet['name']) ?></h3>
                                    <p class="muted">
                                        <?= e($pet['type']) ?>
                                        <?= $pet['breed'] !== null && $pet['breed'] !== '' ? ' · ' . e($pet['breed']) : '' ?>
                                    </p>
                                </div>
                                <a href="mascotas.php?edit=<?= (int) $pet['id'] ?>">Editar</a>
                            </div>
                            <dl class="details">
                                <?php if ($pet['birth_date'] !== null): ?>
                                    <div><dt>Nacimiento</dt><dd><?= e($pet['birth_date']) ?></dd></div>
                                <?php endif; ?>
                                <?php if ($pet['gender'] !== null && $pet['gender'] !== ''): ?>
                                    <div><dt>Sexo</dt><dd><?= e($pet['gender']) ?></dd></div>
                                <?php endif; ?>
                                <?php if ($pet['notes'] !== null && $pet['notes'] !== ''): ?>
                                    <div><dt>Notas</dt><dd><?= e($pet['notes']) ?></dd></div>
                                <?php endif; ?>
                            </dl>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="section">
            <div class="section-heading">
                <div>
                    <h2>Mis tareas</h2>
                    <p class="muted"><?= count($tasks) ?> registrada(s)</p>
                </div>
                <a class="button-link" href="tareas.php">+ Agregar tarea</a>
            </div>

            <?php if ($tasks === []): ?>
                <div class="empty-state">
                    <p>Aún no tienes tareas registradas.</p>
                    <?php if ($pets !== []): ?>
                        <a href="tareas.php">Agrega una tarea</a>
                    <?php else: ?>
                        <a href="mascotas.php">Primero registra una mascota</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tarea</th>
                                <th>Mascota</th>
                                <th>Fecha y hora</th>
                                <th>Prioridad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($task['title']) ?></strong>
                                        <span class="table-subtitle"><?= e($task['category']) ?></span>
                                    </td>
                                    <td><?= e($task['pet_name']) ?></td>
                                    <td>
                                        <?= e($task['date']) ?>
                                        <?= $task['time'] !== null ? ' · ' . e(substr($task['time'], 0, 5)) : '' ?>
                                    </td>
                                    <td><span class="badge priority-<?= e($task['priority']) ?>"><?= e($task['priority']) ?></span></td>
                                    <td><span class="badge status-<?= e($task['status']) ?>"><?= e($task['status'] === 'completed' ? 'Completada' : 'Pendiente') ?></span></td>
                                    <td><a href="tareas.php?edit=<?= (int) $task['id'] ?>">Editar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <dialog class="photo-dialog" aria-label="Foto ampliada">
            <button class="photo-dialog-close" type="button" aria-label="Cerrar foto ampliada">&times;</button>
            <img class="photo-dialog-image" src="" alt="">
        </dialog>
    </main>
    <script>
        const photoDialog = document.querySelector('.photo-dialog');
        const enlargedPhoto = photoDialog.querySelector('.photo-dialog-image');

        document.querySelectorAll('.photo-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                enlargedPhoto.src = trigger.dataset.photoSrc;
                enlargedPhoto.alt = trigger.dataset.photoAlt;
                photoDialog.showModal();
            });
        });

        photoDialog.querySelector('.photo-dialog-close').addEventListener('click', () => {
            photoDialog.close();
        });

        photoDialog.addEventListener('click', (event) => {
            if (event.target === photoDialog) {
                photoDialog.close();
            }
        });

        photoDialog.addEventListener('close', () => {
            enlargedPhoto.removeAttribute('src');
            enlargedPhoto.alt = '';
        });
    </script>
</body>
</html>
