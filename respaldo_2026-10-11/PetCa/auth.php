<?php

require_once __DIR__ . '/conexion/conexion.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function valid_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();

    return $date !== false
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
        && $date->format('Y-m-d') === $value;
}

function post_string(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? trim($_POST[$key])
        : '';
}

function save_pet_photo_upload(array $file): string
{
    if (!isset($file['error']) || !is_int($file['error'])) {
        throw new RuntimeException('No se pudo recibir el archivo de imagen.');
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el límite de tamaño permitido por PHP.',
            UPLOAD_ERR_PARTIAL => 'La imagen se recibió incompleta. Intenta seleccionarla de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR => 'PHP no tiene configurada una carpeta temporal para las subidas.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo guardar temporalmente la imagen.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida de la imagen.',
            default => 'No se pudo subir la imagen.',
        };

        throw new RuntimeException($message);
    }

    $temporaryPath = $file['tmp_name'] ?? '';
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('El archivo recibido no es una subida válida.');
    }

    $maximumSize = 5 * 1024 * 1024;
    $fileSize = filesize($temporaryPath);
    if ($fileSize === false || $fileSize > $maximumSize) {
        throw new RuntimeException('La imagen debe pesar como máximo 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($temporaryPath);
    $imageInfo = getimagesize($temporaryPath);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!is_string($mimeType) || !isset($extensions[$mimeType]) || $imageInfo === false) {
        throw new RuntimeException('Formato no admitido. Usa una imagen JPG, PNG o WebP.');
    }

    $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pets';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('No se pudo crear la carpeta para guardar las fotos.');
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }

    return 'uploads/pets/' . $fileName;
}

function pet_photo_src(mixed $photo): ?string
{
    if (!is_string($photo) || $photo === '') {
        return null;
    }

    if (preg_match('~\Auploads/pets/[a-f0-9]{32}\.(?:jpg|png|webp)\z~D', $photo) === 1) {
        return $photo;
    }

    if (filter_var($photo, FILTER_VALIDATE_URL) !== false) {
        $scheme = parse_url($photo, PHP_URL_SCHEME);
        if (in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return $photo;
        }
    }

    return null;
}

function remove_pet_photo(mixed $photo): void
{
    if (!is_string($photo)
        || preg_match('~\Auploads/pets/[a-f0-9]{32}\.(?:jpg|png|webp)\z~D', $photo) !== 1
    ) {
        return;
    }

    $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photo);
    if (is_file($path) && !unlink($path)) {
        error_log('PetCare: no se pudo eliminar la foto ' . $path);
    }
}
