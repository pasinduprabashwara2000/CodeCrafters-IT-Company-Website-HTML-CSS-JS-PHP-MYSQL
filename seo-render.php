<?php
declare(strict_types=1);

/**
 * Serves the existing static .html pages unchanged on disk, and injects the
 * Sri Lanka local SEO block into <head> at request time (see .htaccess).
 * If anything fails, the original page is served untouched.
 */

require __DIR__ . '/api/bootstrap.php';
require __DIR__ . '/api/seo-lib.php';

$pages = seo_pages();
$slug  = (string) ($_GET['p'] ?? 'index');
if (!isset($pages[$slug])) {
    http_response_code(404);
    exit('Not found');
}

$file = __DIR__ . '/' . $pages[$slug][0];
if (!is_file($file)) {
    http_response_code(404);
    exit('Not found');
}

$mtime = (int) filemtime($file);
foreach ([seo_cache_path(), __FILE__, __DIR__ . '/api/seo-lib.php'] as $dep) {
    if (is_file($dep)) {
        $mtime = max($mtime, (int) filemtime($dep));
    }
}
$etag = '"' . md5($slug . '|' . $mtime) . '"';

header('Content-Type: text/html; charset=utf-8');
header('Content-Language: en-LK');
header('Cache-Control: public, max-age=300, must-revalidate');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

$inm = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
$ims = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
if (($inm !== '' && trim($inm) === $etag) || ($inm === '' && $ims !== '' && strtotime($ims) >= $mtime)) {
    http_response_code(304);
    exit;
}

$html = (string) file_get_contents($file);

try {
    $s = seo_load();
    if ($s['enabled'] === '1' && $s['site_url'] !== '' && stripos($html, 'local-seo:start') === false) {
        $pos = stripos($html, '</head>');
        if ($pos !== false) {
            $html = substr_replace($html, seo_head_snippet($s, $slug), $pos, 0);
        }
    }
} catch (Throwable $ex) {
    error_log('seo-render: ' . $ex->getMessage());
}

echo $html;
