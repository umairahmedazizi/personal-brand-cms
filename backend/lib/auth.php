<?php
// admin login sessions + csrf
require_once __DIR__ . '/helpers.php';

function auth_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function auth_login(string $email, string $password): bool
{
    auth_start();
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $user['id'];
        $_SESSION['uname'] = $user['name'];
        return true;
    }
    return false;
}

function auth_logout(): void
{
    auth_start();
    $_SESSION = [];
    session_destroy();
}

function current_user(): ?array
{
    auth_start();
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, email, name FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['uid']]);
    return $stmt->fetch() ?: null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('/admin/login.php');
    }
    return $user;
}

function csrf_token(): string
{
    auth_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    auth_start();
    $sent = $_POST['csrf'] ?? '';
    if (!$sent || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Session expired. Please go back and try again.');
    }
}
