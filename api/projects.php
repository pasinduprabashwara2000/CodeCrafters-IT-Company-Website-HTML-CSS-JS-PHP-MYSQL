<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

try {
    $rows = db()->query(
        'SELECT id, client, domain, url, tag, title, description, tech, thumb_style, thumb_icon, logo_path
         FROM projects WHERE is_published = 1 ORDER BY sort_order ASC, id DESC'
    )->fetchAll();
} catch (Throwable $ex) {
    error_log('projects.php: ' . $ex->getMessage());
    json_out(['ok' => false, 'projects' => []], 500);
}

foreach ($rows as &$r) {
    $r['tech'] = array_values(array_filter(array_map('trim', explode(',', (string) $r['tech']))));
}
unset($r);

json_out(['ok' => true, 'projects' => $rows]);
