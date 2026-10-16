<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$page = (string) ($_GET['page'] ?? '');
if (!preg_match('/^[a-z0-9-]{1,60}$/', $page)) {
    json_out(['ok' => false, 'examples' => []], 400);
}

try {
    $stmt = db()->prepare(
        'SELECT position, title, url, description, tech, logo_path
         FROM plan_examples WHERE page_slug = ? AND is_published = 1
         ORDER BY position ASC, sort_order ASC, id ASC'
    );
    $stmt->execute([$page]);
    $rows = $stmt->fetchAll();
} catch (Throwable $ex) {
    // Table not created yet: just show no examples.
    json_out(['ok' => true, 'examples' => []]);
}

foreach ($rows as &$r) {
    $r['position'] = (int) $r['position'];
    $r['tech'] = array_values(array_filter(array_map('trim', explode(',', (string) $r['tech']))));
}
unset($r);

json_out(['ok' => true, 'examples' => $rows]);
