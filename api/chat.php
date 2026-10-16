<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/ai-lib.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: public widget settings (never exposes the API key).
if ($method === 'GET') {
    $s = ai_load();
    $on = $s['ai_enabled'] === '1' && ai_api_key() !== '';
    header('Cache-Control: public, max-age=60');
    $out = ['ok' => true, 'enabled' => $on];
    if ($on) {
        $out['name']     = $s['ai_name'];
        $out['greeting'] = $s['ai_greeting'];
    }
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$raw  = file_get_contents('php://input', false, null, 0, 20000);
$data = json_decode($raw ?: '', true);
if (!is_array($data) || !isset($data['messages']) || !is_array($data['messages'])) {
    json_out(['ok' => false, 'error' => 'Bad request.'], 400);
}

// Honeypot
if (!empty($data['website'])) {
    json_out(['ok' => true, 'reply' => 'Thanks!']);
}

$s = ai_load();
if ($s['ai_enabled'] !== '1' || ai_api_key() === '') {
    json_out(['ok' => false, 'error' => 'The assistant is offline right now.'], 503);
}

// Sanitize history: alternating roles, must start and end with the visitor.
$clean = [];
foreach (array_slice($data['messages'], -AI_MAX_MESSAGES) as $m) {
    if (!is_array($m)) {
        continue;
    }
    $role = ($m['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
    $text = trim((string) ($m['content'] ?? ''));
    if ($text === '') {
        continue;
    }
    $text = mb_substr($text, 0, AI_MAX_CHARS);
    if ($clean && $clean[count($clean) - 1]['role'] === $role) {
        $clean[count($clean) - 1]['content'] .= "\n" . $text;
    } else {
        $clean[] = ['role' => $role, 'content' => $text];
    }
}
while ($clean && $clean[0]['role'] !== 'user') {
    array_shift($clean);
}
if (!$clean || $clean[count($clean) - 1]['role'] !== 'user') {
    json_out(['ok' => false, 'error' => 'Please type a message.'], 422);
}
$question = $clean[count($clean) - 1]['content'];

try {
    ai_ensure_logs();
    $ip = client_ip();
    $limit = (int) cfg('ai_rate_limit', 30);

    $st = db()->prepare('SELECT COUNT(*) FROM chat_logs WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
    $st->execute([$ip]);
    if ((int) $st->fetchColumn() >= $limit) {
        json_out(['ok' => false, 'error' => 'You have sent a lot of messages. Please try again in a while or use the contact form.'], 429);
    }

    $daily = (int) cfg('ai_daily_limit', 500);
    if ($daily > 0 && (int) db()->query('SELECT COUNT(*) FROM chat_logs WHERE created_at > (NOW() - INTERVAL 1 DAY)')->fetchColumn() >= $daily) {
        json_out(['ok' => false, 'error' => 'The assistant is very busy today. Please use the contact form and we will reply soon.'], 429);
    }
} catch (Throwable $ex) {
    error_log('chat.php limits: ' . $ex->getMessage());
    json_out(['ok' => false, 'error' => 'Something went wrong. Please try again later.'], 500);
}

[$reply, $err] = ai_call($s['ai_model'], ai_system_prompt($s), $clean);
if ($reply === null) {
    json_out(['ok' => false, 'error' => "Sorry, I can't answer right now. Please use the contact form or email hello@codecrafters.com.lk."], 502);
}

try {
    $st = db()->prepare('INSERT INTO chat_logs (ip, question, answer) VALUES (?, ?, ?)');
    $st->execute([$ip, $question, $reply]);
} catch (Throwable $ex) {
    error_log('chat.php log: ' . $ex->getMessage());
}

json_out(['ok' => true, 'reply' => $reply]);
