<?php
// newsletter signup, idempotent
require_once __DIR__ . '/../lib/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$in = read_input();

// honeypot
if (!empty($in['botcheck'])) {
    json_response(['success' => true, 'message' => 'Thank you.']);
}

$email = trim($in['email'] ?? '');
$name  = mb_substr(trim($in['name'] ?? ''), 0, 200);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'success' => false,
        'message' => 'Please enter a valid email address.',
        'fields'  => ['email'],
    ], 422);
}
$email = strtolower($email);

$existing = db()->prepare('SELECT id FROM subscribers WHERE email = ?');
$existing->execute([$email]);

if ($existing->fetch()) {
    // reactivate if they had unsubscribed
    $upd = db()->prepare("UPDATE subscribers SET status = 'active' WHERE email = ?");
    $upd->execute([$email]);
} else {
    $stmt = db()->prepare(
        'INSERT INTO subscribers (email, name, source, status, created_at)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$email, $name, 'website', 'active', gmdate('Y-m-d H:i:s')]);
}

json_response([
    'success' => true,
    'message' => "You're on the list. Thank you for subscribing.",
]);
