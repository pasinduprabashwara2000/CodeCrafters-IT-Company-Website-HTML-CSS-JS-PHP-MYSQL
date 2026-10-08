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

json_out(['ok' => true, 'plans' => $plans]);
