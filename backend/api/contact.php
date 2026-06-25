<?php
// contact form: store, email me, auto-reply
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$in = read_input();

// honeypot, bots fill this
if (!empty($in['botcheck'])) {
    json_response(['success' => true, 'message' => 'Thank you.']);
}

$name    = trim($in['name'] ?? '');
$email   = trim($in['email'] ?? '');
$subject = trim($in['subject'] ?? 'Website enquiry');
$message = trim($in['message'] ?? '');

$errors = [];
if ($name === '')   $errors[] = 'name';
if ($message === '') $errors[] = 'message';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';

if ($errors) {
    json_response([
        'success' => false,
        'message' => 'Please fill in your name, a valid email, and a message.',
        'fields'  => $errors,
    ], 422);
}

// cap lengths
$name    = mb_substr($name, 0, 200);
$subject = mb_substr($subject, 0, 200);
$message = mb_substr($message, 0, 5000);

$stmt = db()->prepare(
    'INSERT INTO contacts (name, email, subject, message, status, ip, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $name, $email, $subject, $message, 'new',
    $_SERVER['REMOTE_ADDR'] ?? '',
    gmdate('Y-m-d H:i:s'),
]);

// notify owner
$ownerBody =
    '<h2 style="font-family:Georgia,serif">New message from the website</h2>' .
    '<p><strong>Name:</strong> ' . e($name) . '</p>' .
    '<p><strong>Email:</strong> ' . e($email) . '</p>' .
    '<p><strong>Subject:</strong> ' . e($subject) . '</p>' .
    '<p><strong>Message:</strong></p><p>' . nl2br(e($message)) . '</p>';
send_mail(cfg()['mail']['admin_to'], 'Website enquiry: ' . $subject, $ownerBody, $email);

// auto-reply
if (!empty(cfg()['mail']['auto_reply'])) {
    $replyBody =
        '<p>Dear ' . e($name) . ',</p>' .
        '<p>Thank you for reaching out. Your message has reached Adrian Cole, ' .
        'and you can expect a considered reply soon.</p>' .
        '<p>— The office of Adrian Cole</p>';
    send_mail($email, 'Thank you for your message', $replyBody);
}

json_response([
    'success' => true,
    'message' => "Thanks — your message has been sent. I'll be in touch soon.",
]);
