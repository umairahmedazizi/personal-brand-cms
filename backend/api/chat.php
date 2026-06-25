<?php
// chatbot proxy. GET = widget bootstrap, POST = ask. key stays server side.
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/settings.php';

header('Content-Type: application/json; charset=utf-8');

$groq = cfg()['groq'];
$botEnabled = !empty($groq['enabled'])
    && trim($groq['api_key']) !== ''
    && get_setting('bot_enabled', '1') === '1';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    json_response([
        'enabled'  => $botEnabled,
        'greeting' => get_setting('bot_greeting', 'Hi! How can I help?'),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (!$botEnabled) {
    json_response(['success' => false, 'message' => 'The assistant is currently unavailable.'], 503);
}

// rate limit per ip
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!rate_ok($ip, (int) $groq['rate_per_min'], (int) $groq['rate_per_day'])) {
    json_response([
        'success' => false,
        'message' => 'You are sending messages too quickly. Please wait a moment and try again.',
    ], 429);
}

$in = read_input();
$incoming = $in['messages'] ?? [];
if (!is_array($incoming) || !$incoming) {
    json_response(['success' => false, 'message' => 'No message provided.'], 422);
}

// keep last 10 turns, valid roles only
$maxChars = (int) $groq['max_input_chars'];
$history = [];
foreach (array_slice($incoming, -10) as $m) {
    $role = ($m['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
    $content = trim((string) ($m['content'] ?? ''));
    if ($content === '') continue;
    $history[] = ['role' => $role, 'content' => mb_substr($content, 0, $maxChars)];
}
if (!$history) {
    json_response(['success' => false, 'message' => 'No message provided.'], 422);
}

// grounded system prompt
$persona = get_setting('bot_persona', 'You are a helpful assistant for this website.');
$kb1 = get_setting('bot_knowledge_1', '');
$kb2 = get_setting('bot_knowledge_2', '');

$system = $persona . "\n\n"
    . "=== KNOWLEDGE BASE (your only source of truth) ===\n"
    . trim($kb1 . "\n\n" . $kb2) . "\n"
    . "=== END KNOWLEDGE BASE ===\n\n"
    . "RULES:\n"
    . "- Answer using the knowledge base and what is on this website about the books.\n"
    . "- If a question is outside Adrian Cole's books and ideas, or the answer is not "
    . "in the knowledge base, say politely that you can only help with his books and ideas, "
    . "and suggest the Contact page.\n"
    . "- Never invent prices, dates, quotes, or facts. Keep answers concise and friendly.";

$messages = array_merge([['role' => 'system', 'content' => $system]], $history);

[$ok, $result] = call_groq($groq, $messages);
if (!$ok) {
    error_log('Groq call failed: ' . $result);
    json_response([
        'success' => false,
        'message' => 'Sorry, I had trouble answering just now. Please try again shortly.',
    ], 502);
}

json_response(['success' => true, 'reply' => $result]);


// sliding window rate limit
function rate_ok(string $ip, int $perMin, int $perDay): bool
{
    $now = time();
    $pdo = db();
    $pdo->prepare('DELETE FROM chat_throttle WHERE created_at < ?')->execute([$now - 86400]);

    $countSince = function (int $since) use ($pdo, $ip): int {
        $s = $pdo->prepare('SELECT COUNT(*) c FROM chat_throttle WHERE ip = ? AND created_at >= ?');
        $s->execute([$ip, $since]);
        return (int) $s->fetch()['c'];
    };

    if ($countSince($now - 60) >= $perMin)  return false;
    if ($countSince($now - 86400) >= $perDay) return false;

    $pdo->prepare('INSERT INTO chat_throttle (ip, created_at) VALUES (?, ?)')->execute([$ip, $now]);
    return true;
}

// groq is openai-compatible. returns [ok, replyOrError]
function call_groq(array $groq, array $messages): array
{
    $payload = json_encode([
        'model'       => $groq['model'],
        'messages'    => $messages,
        'max_tokens'  => (int) $groq['max_tokens'],
        'temperature' => (float) $groq['temperature'],
        'stream'      => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $groq['api_key'],
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($groq['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        // curl_close() is a deprecated no-op since PHP 8.0; the handle is freed
        // automatically when $ch goes out of scope.
        if ($body === false) {
            return [false, 'cURL error: ' . $err];
        }
    } else {
        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", $headers),
            'content'       => $payload,
            'timeout'       => 45,
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($groq['endpoint'], false, $ctx);
        $code = 200;
        if ($body === false) {
            return [false, 'HTTP request failed (allow_url_fopen / network).'];
        }
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return [false, 'Invalid response: ' . substr((string) $body, 0, 200)];
    }
    if (isset($data['error'])) {
        $msg = is_array($data['error']) ? ($data['error']['message'] ?? 'unknown') : $data['error'];
        return [false, 'API error (' . $code . '): ' . $msg];
    }
    $reply = $data['choices'][0]['message']['content'] ?? '';
    if (trim($reply) === '') {
        return [false, 'Empty reply from model.'];
    }
    return [true, trim($reply)];
}
