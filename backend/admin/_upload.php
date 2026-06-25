<?php
// image upload, returns [ok, path-or-error]
require_once __DIR__ . '/../lib/helpers.php';

function handle_upload(string $field): array
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return [false, 'No file uploaded.'];
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Upload failed (code ' . $f['error'] . ').'];
    }
    if ($f['size'] > 6 * 1024 * 1024) {
        return [false, 'Image is too large (max 6 MB).'];
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!isset($allowed[$mime])) {
        return [false, 'Only JPG, PNG, WEBP or GIF images are allowed.'];
    }

    $dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        // fallback for the php built-in server
        if (!rename($f['tmp_name'], $dest)) {
            return [false, 'Could not save the uploaded file.'];
        }
    }
    return [true, '/uploads/' . $name];
}
