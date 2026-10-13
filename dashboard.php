<?php

require_once __DIR__ . '/auth.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$userName = isset($_SESSION['user_name']) && is_string($_SESSION['user_name'])
    ? $_SESSION['user_name']
    : 'Usuario';

$petCountStatement = $pdo->prepare('SELECT COUNT(*) FROM pets WHERE user_id = :user_id');
$petCountStatement->execute(['user_id' => $userId]);
$petCount = (int) $petCountStatement->fetchColumn();
$petsPerPage = 6;
$petPageCount = max(1, (int) ceil($petCount / $petsPerPage));
$requestedPetPage = isset($_GET['pet_page']) && is_string($_GET['pet_page'])
    && ctype_digit($_GET['pet_page'])
    ? (int) $_GET['pet_page']
    : 1;
$petPage = min(max(1, $requestedPetPage), $petPageCount);
$petOffset = ($petPage - 1) * $petsPerPage;

$petStatement = $pdo->prepare(
    'SELECT id, name, `type`, breed, birth_date, gender, photo, notes
     FROM pets WHERE user_id = :user_id ORDER BY name LIMIT :limit OFFSET :offset'
);
$petStatement->bindValue(':user_id', $userId, PDO::PARAM_INT);
$petStatement->bindValue(':limit', $petsPerPage, PDO::PARAM_INT);
$petStatement->bindValue(':offset', $petOffset, PDO::PARAM_INT);
$petStatement->execute();
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
$taskCount = count($tasks);
$upcomingTasks = array_slice(
    array_values(array_filter(
        $tasks,
        static fn (array $task): bool => $task['status'] === 'pending'
    )),
    0,
    5
);

$problemCountStatement = $pdo->prepare('SELECT COUNT(*) FROM health_problems WHERE user_id = :user_id');
$problemCountStatement->execute(['user_id' => $userId]);
$problemCount = (int) $problemCountStatement->fetchColumn();

$recentProblemStatement = $pdo->prepare(
    'SELECT health_problems.id, health_problems.description, health_problems.status,
            health_problems.reported_at, pets.name AS pet_name
     FROM health_problems
     INNER JOIN pets ON pets.id = health_problems.pet_id
                   AND pets.user_id = health_problems.user_id
     WHERE health_problems.user_id = :user_id
     ORDER BY health_problems.reported_at DESC, health_problems.id DESC
     LIMIT 3'
);
$recentProblemStatement->execute(['user_id' => $userId]);
$recentProblems = $recentProblemStatement->fetchAll();

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
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body class="app-body">
    <div class="dashboard-frame">
        <?php require __DIR__ . '/app_sidebar.php'; ?>

        <main class="app-shell">
        <header class="topbar">
            <div>
                <h1>Hola, <?= e($userName) ?></h1>
                <p class="muted">Aquí puedes administrar tus mascotas, tareas y reportes de salud.</p>
            </div>
        </header>

        <?php if (isset($messages[$message])): ?>
            <p class="success" role="status"><?= e($messages[$message]) ?></p>
        <?php endif; ?>

        <section class="summary-grid" aria-label="Resumen de tu cuenta">
            <a class="summary-card summary-pets" href="mascotas.php">
                <span class="summary-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48"><path d="M16.1 21.2c-3.6 0-6.3-3.2-6.3-7.2 0-3.2 1.8-5.6 4.3-5.6 3.5 0 6.4 4.7 6.4 8 0 2.7-1.8 4.8-4.4 4.8Zm15.8 0c-2.6 0-4.4-2.1-4.4-4.8 0-3.3 2.9-8 6.4-8 2.5 0 4.3 2.4 4.3 5.6 0 4-2.7 7.2-6.3 7.2ZM8.7 29.1c-2.5 0-4.2-2-4.2-4.8 0-3.4 2.6-7.1 5.6-7.1 2.1 0 3.6 1.9 3.6 4.4 0 3.7-2.3 7.5-5 7.5Zm30.6 0c-2.7 0-5-3.8-5-7.5 0-2.5 1.5-4.4 3.6-4.4 3 0 5.6 3.7 5.6 7.1 0 2.8-1.7 4.8-4.2 4.8ZM24 43c-5.2 0-12-3.4-12-9.2 0-5.2 5.1-10.5 12-10.5s12 5.3 12 10.5C36 39.6 29.2 43 24 43Z"/></svg>
                </span>
                <span class="summary-copy"><strong><?= $petCount ?></strong><span>Mascotas</span></span>
            </a>
            <a class="summary-card summary-tasks" href="tareas.php">
                <span class="summary-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M8 3h8v2h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h3V3Zm2 2h4V5h-4Zm-.4 11.2-2.1-2.1-1.4 1.4 3.5 3.5 6.3-6.3-1.4-1.4-4.9 4.9Z"/></svg>
                </span>
                <span class="summary-copy"><strong><?= $taskCount ?></strong><span>Tareas</span></span>
            </a>
            <a class="summary-card summary-reports" href="problemas.php">
                <span class="summary-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 21s-8-4.6-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.4-8 11-8 11Z"/></svg>
                </span>
                <span class="summary-copy"><strong><?= $problemCount ?></strong><span>Reportes de salud</span></span>
            </a>
        </section>

        <div class="dashboard-layout">
            <div class="dashboard-main">
                <section class="section" id="mascotas">
                    <div class="section-heading">
                        <div>
                            <h2>Mis mascotas</h2>
                            <p class="muted"><?= $petCount ?> registrada(s)</p>
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
                                    <?php
                                    $petAgeLabel = 'Edad no registrada';
                                    if ($pet['birth_date'] !== null && $pet['birth_date'] !== '') {
                                        $birthDate = new DateTimeImmutable($pet['birth_date']);
                                        $today = new DateTimeImmutable('today');
                                        if ($birthDate <= $today) {
                                            $age = $birthDate->diff($today);
                                            $petAgeLabel = $age->y > 0
                                                ? $age->y . ' ' . ($age->y === 1 ? 'año' : 'años')
                                                : ($age->m > 0
                                                    ? $age->m . ' ' . ($age->m === 1 ? 'mes' : 'meses')
                                                    : 'Menos de 1 mes');
                                        } else {
                                            $petAgeLabel = 'Fecha por revisar';
                                        }
                                    }
                                    ?>
                                    <?php if (($photoSrc = pet_photo_src($pet['photo'])) !== null): ?>
                                        <button
                                            class="photo-trigger pet-card-photo"
                                            type="button"
                                            data-photo-src="<?= e($photoSrc) ?>"
                                            data-photo-alt="Foto de <?= e($pet['name']) ?>"
                                            aria-label="Ampliar foto de <?= e($pet['name']) ?>"
                                        >
                                            <img class="pet-photo" src="<?= e($photoSrc) ?>" alt="">
                                        </button>
                                    <?php else: ?>
                                        <div class="pet-photo-placeholder" role="img" aria-label="Sin fotografía registrada">
                                            <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M16.1 21.2c-3.6 0-6.3-3.2-6.3-7.2 0-3.2 1.8-5.6 4.3-5.6 3.5 0 6.4 4.7 6.4 8 0 2.7-1.8 4.8-4.4 4.8Zm15.8 0c-2.6 0-4.4-2.1-4.4-4.8 0-3.3 2.9-8 6.4-8 2.5 0 4.3 2.4 4.3 5.6 0 4-2.7 7.2-6.3 7.2ZM8.7 29.1c-2.5 0-4.2-2-4.2-4.8 0-3.4 2.6-7.1 5.6-7.1 2.1 0 3.6 1.9 3.6 4.4 0 3.7-2.3 7.5-5 7.5Zm30.6 0c-2.7 0-5-3.8-5-7.5 0-2.5 1.5-4.4 3.6-4.4 3 0 5.6 3.7 5.6 7.1 0 2.8-1.7 4.8-4.2 4.8ZM24 43c-5.2 0-12-3.4-12-9.2 0-5.2 5.1-10.5 12-10.5s12 5.3 12 10.5C36 39.6 29.2 43 24 43Z"/></svg>
                                            <span>Sin foto</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-heading">
                                        <div>
                                            <h3><?= e($pet['name']) ?></h3>
                                            <p class="muted">
                                                <?= e($pet['type']) ?>
                                                <?= $pet['breed'] !== null && $pet['breed'] !== '' ? ' · ' . e($pet['breed']) : '' ?>
                                            </p>
                                            <p class="pet-age"><strong>Edad:</strong> <?= e($petAgeLabel) ?></p>
                                        </div>
                                        <a href="mascotas.php?edit=<?= (int) $pet['id'] ?>">Editar</a>
                                    </div>
                                    <details class="pet-details">
                                        <summary class="pet-card-action">Ver detalles</summary>
                                        <dl class="details">
                                            <div>
                                                <dt>Nacimiento</dt>
                                                <dd><?= $pet['birth_date'] !== null ? e($pet['birth_date']) : 'No registrado' ?></dd>
                                            </div>
                                            <?php if ($pet['gender'] !== null && $pet['gender'] !== ''): ?>
                                                <div><dt>Sexo</dt><dd><?= e($pet['gender']) ?></dd></div>
                                            <?php endif; ?>
                                            <?php if ($pet['notes'] !== null && $pet['notes'] !== ''): ?>
                                                <div><dt>Notas</dt><dd><?= e($pet['notes']) ?></dd></div>
                                            <?php endif; ?>
                                        </dl>
                                    </details>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($petPageCount > 1): ?>
                            <nav class="pagination" aria-label="Paginación de mascotas">
                                <p class="pagination-summary">
                                    Mostrando <?= $petOffset + 1 ?>–<?= $petOffset + count($pets) ?> de <?= $petCount ?> mascotas
                                </p>
                                <div class="pagination-controls">
                                    <?php if ($petPage > 1): ?>
                                        <a class="pagination-link" href="dashboard.php?pet_page=<?= $petPage - 1 ?>" rel="prev">Anterior</a>
                                    <?php endif; ?>

                                    <?php for ($pageNumber = 1; $pageNumber <= $petPageCount; $pageNumber++): ?>
                                        <?php if ($pageNumber === $petPage): ?>
                                            <span class="pagination-current" aria-current="page"><?= $pageNumber ?></span>
                                        <?php else: ?>
                                            <a class="pagination-link pagination-number" href="dashboard.php?pet_page=<?= $pageNumber ?>" aria-label="Página <?= $pageNumber ?>">
                                                <?= $pageNumber ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($petPage < $petPageCount): ?>
                                        <a class="pagination-link" href="dashboard.php?pet_page=<?= $petPage + 1 ?>" rel="next">Siguiente</a>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="dashboard-sidebar" aria-label="Resumen y accesos rápidos">
                <section class="upcoming-panel" aria-labelledby="upcoming-title">
                    <div class="sidebar-heading">
                        <div>
                            <p class="eyebrow">ORGANIZACIÓN</p>
                            <h2 id="upcoming-title">Próximas tareas</h2>
                        </div>
                        <a href="tareas.php">Ver todas</a>
                    </div>

                    <?php if ($upcomingTasks === []): ?>
                        <p class="sidebar-empty">No tienes tareas pendientes por ahora.</p>
                    <?php else: ?>
                        <ul class="upcoming-list">
                            <?php foreach ($upcomingTasks as $task): ?>
                                <li>
                                    <?php
                                    $categoryText = strtolower($task['category']);
                                    $taskIcon = str_contains($categoryText, 'vacun')
                                        ? '💉'
                                        : (str_contains($categoryText, 'baño') || str_contains($categoryText, 'bano')
                                            ? '🧼'
                                            : (str_contains($categoryText, 'aliment')
                                                ? '🍽'
                                                : (str_contains($categoryText, 'medic')
                                                    ? '💊'
                                                    : '🐾')));
                                    ?>
                                    <a class="upcoming-task" href="tareas.php?edit=<?= (int) $task['id'] ?>">
                                        <span class="upcoming-task-marker" aria-hidden="true"><?= e($taskIcon) ?></span>
                                        <span class="upcoming-task-copy">
                                            <strong><?= e($task['title']) ?></strong>
                                            <span><?= e($task['pet_name']) ?> · <?= e($task['category']) ?></span>
                                        </span>
                                        <time datetime="<?= e($task['date']) ?>"><?= e(date('d/m/Y', strtotime($task['date']))) ?></time>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <a class="button-link sidebar-action" href="tareas.php">+ Agregar tarea</a>
                </section>

                <section class="upcoming-panel health-summary" aria-labelledby="health-summary-title">
                    <div class="sidebar-heading">
                        <div>
                            <p class="eyebrow">SEGUIMIENTO</p>
                            <h2 id="health-summary-title">Reportes de salud</h2>
                        </div>
                        <a href="problemas.php">Ver todos</a>
                    </div>
                    <?php if ($recentProblems === []): ?>
                        <p class="sidebar-empty">Aún no hay reportes de salud registrados.</p>
                    <?php else: ?>
                        <ul class="health-list">
                            <?php foreach ($recentProblems as $problem): ?>
                                <li>
                                    <a class="health-item" href="problemas.php?edit=<?= (int) $problem['id'] ?>">
                                        <span class="health-status-dot status-dot-<?= e($problem['status']) ?>" aria-hidden="true"></span>
                                        <span class="health-item-copy">
                                            <strong><?= e($problem['pet_name']) ?></strong>
                                            <span><?= e(mb_strimwidth($problem['description'], 0, 72, '…', 'UTF-8')) ?></span>
                                        </span>
                                        <span class="badge status-<?= e($problem['status']) ?>">
                                            <?= e($problem['status'] === 'resolved' ? 'Resuelto' : ($problem['status'] === 'in_review' ? 'En revisión' : 'Pendiente')) ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <a class="button-link sidebar-action" href="problemas.php">+ Nuevo reporte</a>
                </section>

            </aside>
        </div>

        <section class="section task-table-section">
            <div class="section-heading">
                <div>
                    <h2>Mis tareas</h2>
                    <p class="muted"><?= $taskCount ?> registrada(s)</p>
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
        </main>
    </div>
</body>
</html>
