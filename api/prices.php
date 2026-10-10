<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$page = (string) ($_GET['page'] ?? '');
if (!preg_match('/^[a-z0-9-]{1,60}$/', $page)) {
    json_out(['ok' => false, 'plans' => []], 400);
}

try {
    $stmt = db()->prepare(
        'SELECT position, currency, prefix, amount, note
         FROM pricing_plans WHERE page_slug = ? AND is_active = 1 ORDER BY position ASC'
    );
    $stmt->execute([$page]);
    $plans = $stmt->fetchAll();
} catch (Throwable $ex) {
    error_log('prices.php: ' . $ex->getMessage());
    json_out(['ok' => false, 'plans' => []], 500);
}

// LKR per 1 USD, editable in the admin panel (Prices page). Falls back to 300.
$rate = 300.0;
try {
    $st = db()->prepare('SELECT svalue FROM seo_settings WHERE skey = ?');
    $st->execute(['usd_lkr_rate']);
    $v = $st->fetchColumn();
    if ($v !== false && is_numeric($v) && (float) $v > 0) {
        $rate = (float) $v;
    }
} catch (Throwable $ex) {
    error_log('prices.php rate: ' . $ex->getMessage());
}

json_out(['ok' => true, 'plans' => $plans, 'rate' => $rate]);