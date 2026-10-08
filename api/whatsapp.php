<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/wa-lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$s = wa_load();
$digits = preg_replace('/\D/', '', $s['wa_number']) ?? '';

$out = ['ok' => true, 'enabled' => false];
if ($s['wa_enabled'] === '1' && strlen($digits) >= 8) {
    $out = [
        'ok'      => true,
        'enabled' => true,
        'number'  => $s['wa_number'],
        'digits'  => $digits,
        'message' => $s['wa_message'],
    ];
}

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
