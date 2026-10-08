<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    $data = $_POST;
}

// Honeypot: bots fill this hidden field, humans never see it.
if (!empty($data['website'])) {
    json_out(['ok' => true, 'message' => 'Thanks! Your message has been sent.']);
}

$name    = trim((string) ($data['name'] ?? ''));
$email   = trim((string) ($data['email'] ?? ''));
$subject = trim((string) ($data['subject'] ?? ''));
$message = trim((string) ($data['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    json_out(['ok' => false, 'error' => 'Please fill in your name, email, and message.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    json_out(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
}
if (mb_strlen($name) > 120 || mb_strlen($subject) > 190 || mb_strlen($message) > 5000) {
    json_out(['ok' => false, 'error' => 'One of the fields is too long.'], 422);
}

try {
    $ip = client_ip();

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)'
    );
    $stmt->execute([$ip]);
    if ((int) $stmt->fetchColumn() >= (int) cfg('contact_rate_limit', 5)) {
        json_out(['ok' => false, 'error' => 'Too many messages. Please try again later.'], 429);
    }

    $stmt = db()->prepare(
        'INSERT INTO messages (name, email, subject, message, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $name,
        $email,
        $subject,
        $message,
        $ip,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
} catch (Throwable $ex) {
    error_log('contact.php: ' . $ex->getMessage());
    json_out(['ok' => false, 'error' => 'Something went wrong. Please try again later.'], 500);
}

$notify = (string) cfg('notify_email', '');
if ($notify !== '') {
    $clean = static fn(string $s): string => str_replace(["\r", "\n"], ' ', $s);
    $body  = "Name: {$clean($name)}\nEmail: {$clean($email)}\nSubject: {$clean($subject)}\n\n{$message}\n";
    $headers = 'From: ' . $clean((string) cfg('from_email')) . "\r\n"
             . 'Reply-To: ' . $clean($email) . "\r\n"
             . 'Content-Type: text/plain; charset=utf-8';
    @mail($notify, 'New website enquiry: ' . ($subject !== '' ? $clean($subject) : $clean($name)), $body, $headers);
}

json_out(['ok' => true, 'message' => "Thanks! Your message has been sent — we'll be in touch shortly."]);
