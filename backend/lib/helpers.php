<?php
require_once __DIR__ . '/db.php';

if (cfg()['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

// unique slug within a table, adds -2, -3 ... if taken
function unique_slug(string $base, string $table, ?int $ignoreId = null): string
{
    $base = slugify($base);
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = "SELECT id FROM $table WHERE slug = ?";
        $params = [$slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function readable_date(string $ts): string
{
    if ($ts === '') return '';
    $t = strtotime($ts);
    return $t ? date('F j, Y', $t) : '';
}

function iso_date(string $ts): string
{
    if ($ts === '') return '';
    $t = strtotime($ts);
    return $t ? date('Y-m-d', $t) : '';
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// POST body as array, handles json or form encoded
function read_input(): array
{
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

function site_url(): string
{
    return rtrim(cfg()['site']['url'], '/');
}
