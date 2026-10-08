<?php
declare(strict_types=1);

/** Dynamic sitemap with en-LK / x-default hreflang. Served at /sitemap.xml via .htaccess. */

require __DIR__ . '/api/bootstrap.php';
require __DIR__ . '/api/seo-lib.php';

$s = ['enabled' => '0'] + seo_defaults();
try {
    $s = seo_load();
} catch (Throwable $ex) {
    error_log('sitemap: ' . $ex->getMessage());
}
$base = rtrim($s['site_url'] !== '' ? $s['site_url'] : seo_defaults()['site_url'], '/');

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach (seo_pages() as $slug => [$file, $priority, $freq]):
    $loc = seo_esc($slug === 'index' ? $base . '/' : $base . '/' . $slug . '.html');
    $path = __DIR__ . '/' . $file;
    $last = is_file($path) ? gmdate('Y-m-d', (int) filemtime($path)) : gmdate('Y-m-d');
    ?>
  <url>
    <loc><?= $loc ?></loc>
<?php if ($s['enabled'] === '1'): ?>
    <xhtml:link rel="alternate" hreflang="en-LK" href="<?= $loc ?>"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= $loc ?>"/>
<?php endif; ?>
    <lastmod><?= $last ?></lastmod>
    <changefreq><?= $freq ?></changefreq>
    <priority><?= $priority ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
