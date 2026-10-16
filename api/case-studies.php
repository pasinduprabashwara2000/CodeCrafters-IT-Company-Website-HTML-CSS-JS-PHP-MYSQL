<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

try {
    $rows = db()->query(
        'SELECT id, label, title, description, tech, metrics, quote, quote_author, image_src
         FROM case_studies WHERE is_published = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll();
} catch (Throwable $ex) {
    // Table missing: the page keeps its built-in case studies.
    json_out(['ok' => true, 'case_studies' => []]);
}

foreach ($rows as &$r) {
    $r['tech'] = array_values(array_filter(array_map('trim', explode(',', (string) $r['tech']))));
    $m = [];
    foreach (preg_split('/\R/', (string) $r['metrics']) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line, 2));
        $m[] = ['value' => $parts[0], 'label' => $parts[1] ?? ''];
    }
    $r['metrics'] = $m;
    unset($r['id']);
}
unset($r);

json_out(['ok' => true, 'case_studies' => $rows]);
