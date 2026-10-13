<?php

require_once __DIR__ . '/auth.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$error = '';
$editingPet = null;
$form = [
    'name' => '',
    'type' => '',
    'breed' => '',
    'birth_date' => '',
    'gender' => '',
    'notes' => '',
];

if (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM pets WHERE id = :id AND user_id = :user_id');
    $statement->execute(['id' => $_GET['edit'], 'user_id' => $userId]);
    $editingPet = $statement->fetch();

    if (!$editingPet) {
        http_response_code(404);
        exit('No se encontró esa mascota.');
    }

    foreach ($form as $key => $value) {
        $form[$key] = $editingPet[$key] ?? '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !verify_csrf_token($token)) {
        $error = 'La sesión del formulario expiró. Recarga la página e intenta de nuevo.';
    } else {
        $action = post_string('action');
        $petId = post_string('pet_id');

        if ($action === 'delete') {
            if ($petId === '' || !ctype_digit($petId)) {
                $error = 'No se pudo identificar la mascota.';
            } else {
                $photoStatement = $pdo->prepare('SELECT photo FROM pets WHERE id = :id AND user_id = :user_id');
                $photoStatement->execute(['id' => $petId, 'user_id' => $userId]);
                $petPhoto = $photoStatement->fetchColumn();

                $statement = $pdo->prepare('DELETE FROM pets WHERE id = :id AND user_id = :user_id');
                $statement->execute(['id' => $petId, 'user_id' => $userId]);
                if ($statement->rowCount() > 0) {
                    remove_pet_photo($petPhoto);
                }
                header('Location: dashboard.php?message=pet_deleted');
                exit;
            }
        } elseif ($action === 'save') {
            foreach ($form as $key => $value) {
                $form[$key] = post_string($key);
            }

            if ($form['name'] === '' || strlen($form['name']) > 150) {
                $error = 'El nombre es obligatorio y no puede superar 150 caracteres.';
            } elseif ($form['type'] === '' || strlen($form['type']) > 80) {
                $error = 'El tipo de mascota es obligatorio y no puede superar 80 caracteres.';
            } elseif (strlen($form['breed']) > 120 || strlen($form['gender']) > 30) {
                $error = 'Revisa la longitud de raza o sexo.';
            } elseif ($form['birth_date'] !== '' && !valid_date($form['birth_date'])) {
                $error = 'La fecha de nacimiento no es válida.';
            } else {
                $photoFile = $_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE];
                if (!is_array($photoFile)) {
                    $error = 'El archivo de imagen no es válido.';
                } else {
                    try {
                        $newPhoto = save_pet_photo_upload($photoFile);
                    } catch (RuntimeException $exception) {
                        $error = $exception->getMessage();
                    }
                }

                if ($error === '') {
                    $oldPhoto = $editingPet['photo'] ?? null;
                    $removePhoto = isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1';
                    $photo = $newPhoto !== ''
                        ? $newPhoto
                        : ($removePhoto ? null : $oldPhoto);

                    $values = [
                        'user_id' => $userId,
                        'name' => $form['name'],
                        'type' => $form['type'],
                        'breed' => $form['breed'] === '' ? null : $form['breed'],
                        'birth_date' => $form['birth_date'] === '' ? null : $form['birth_date'],
                        'gender' => $form['gender'] === '' ? null : $form['gender'],
                        'photo' => $photo,
                        'notes' => $form['notes'] === '' ? null : $form['notes'],
                    ];

                    try {
                        if ($petId !== '' && ctype_digit($petId)) {
                            $statement = $pdo->prepare(
                                'UPDATE pets SET name = :name, `type` = :type, breed = :breed,
                                 birth_date = :birth_date, gender = :gender, photo = :photo, notes = :notes
                                 WHERE id = :id AND user_id = :user_id'
                            );
                            $values['id'] = $petId;
                            $statement->execute($values);
                        } else {
                            $statement = $pdo->prepare(
                                'INSERT INTO pets (user_id, name, `type`, breed, birth_date, gender, photo, notes)
                                 VALUES (:user_id, :name, :type, :breed, :birth_date, :gender, :photo, :notes)'
                            );
                            $statement->execute($values);
                        }
                    } catch (Throwable $exception) {
                        if ($newPhoto !== '') {
                            remove_pet_photo($newPhoto);
                        }
                        throw $exception;
                    }

                    if ($newPhoto !== '' || $removePhoto) {
                        remove_pet_photo($oldPhoto);
                    }

                    header('Location: dashboard.php?message=pet_saved');
                    exit;
                }
            }

            if ($petId !== '' && ctype_digit($petId)) {
                $statement = $pdo->prepare('SELECT id FROM pets WHERE id = :id AND user_id = :user_id');
                $statement->execute(['id' => $petId, 'user_id' => $userId]);
                $editingPet = $statement->fetch();
                if (!$editingPet) {
                    http_response_code(404);
                    exit('No se encontró esa mascota.');
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
    <title><?= $editingPet ? 'Editar mascota' : 'Agregar mascota' ?> | PetCare</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body class="app-body">
    <div class="dashboard-frame">
    <?php require __DIR__ . '/app_sidebar.php'; ?>
    <main class="card form-card">
        <p class="eyebrow">PETCARE</p>
        <h1><?= $editingPet ? 'Editar mascota' : 'Agregar mascota' ?></h1>
        <p class="muted">Registra los datos de tu mascota.</p>

        <?php if ($error !== ''): ?>
            <p class="alert" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" action="mascotas.php<?= $editingPet ? '?edit=' . (int) $editingPet['id'] : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
            <?php if ($editingPet): ?>
                <input type="hidden" name="pet_id" value="<?= (int) $editingPet['id'] ?>">
            <?php endif; ?>

            <label for="name">Nombre *</label>
            <input id="name" name="name" type="text" maxlength="150" value="<?= e($form['name']) ?>" required>

            <label for="type">Tipo (perro, gato, ave...) *</label>
            <input id="type" name="type" type="text" maxlength="80" value="<?= e($form['type']) ?>" required>

            <label for="breed">Raza</label>
            <input id="breed" name="breed" type="text" maxlength="120" value="<?= e($form['breed']) ?>">

            <label for="birth_date">Fecha de nacimiento</label>
            <input id="birth_date" name="birth_date" type="date" value="<?= e($form['birth_date']) ?>">

            <label for="gender">Sexo</label>
            <select id="gender" name="gender">
                <option value="">Sin especificar</option>
                <?php foreach (['Hembra', 'Macho', 'Otro'] as $gender): ?>
                    <option value="<?= e($gender) ?>" <?= $form['gender'] === $gender ? 'selected' : '' ?>><?= e($gender) ?></option>
                <?php endforeach; ?>
                <?php if ($form['gender'] !== '' && !in_array($form['gender'], ['Hembra', 'Macho', 'Otro'], true)): ?>
                    <option value="<?= e($form['gender']) ?>" selected><?= e($form['gender']) ?></option>
                <?php endif; ?>
            </select>

            <label for="photo">Foto de la mascota (JPG, PNG o WebP; máximo 5 MB)</label>
            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">

            <?php if ($editingPet && ($currentPhoto = pet_photo_src($editingPet['photo'] ?? null)) !== null): ?>
                <div class="current-photo">
                    <img class="pet-photo-preview" src="<?= e($currentPhoto) ?>" alt="Foto actual de <?= e($form['name']) ?>">
                    <label class="checkbox-label" for="remove_photo">
                        <input id="remove_photo" name="remove_photo" type="checkbox" value="1">
                        Quitar la foto actual
                    </label>
                </div>
            <?php endif; ?>

            <label for="notes">Notas</label>
            <textarea id="notes" name="notes" rows="4"><?= e($form['notes']) ?></textarea>

            <button type="submit"><?= $editingPet ? 'Guardar cambios' : 'Guardar mascota' ?></button>
        </form>

        <?php if ($editingPet): ?>
            <form method="post" action="mascotas.php" onsubmit="return confirm('¿Eliminar esta mascota y todas sus tareas?');">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="pet_id" value="<?= (int) $editingPet['id'] ?>">
                <button class="danger" type="submit">Eliminar mascota</button>
            </form>
        <?php endif; ?>

        <p class="footer-text"><a href="dashboard.php">Volver al panel</a></p>
    </main>
    </div>
</body>
</html>
