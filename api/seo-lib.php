<?php
declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'seo-lib.php') {
    http_response_code(403);
    exit;
}

/**
 * Sri Lanka local SEO (backend only).
 * Nothing here is written into the .html files. seo-render.php injects the
 * output into the <head> at request time, and sitemap.php adds hreflang.
 */

/** slug => [html file, sitemap priority, sitemap changefreq] */
function seo_pages(): array
{
    return [
        'index'                => ['index.html',               '1.0', 'weekly'],
        'software-development' => ['software-development.html', '0.9', 'monthly'],
        'web-design'           => ['web-design.html',           '0.9', 'monthly'],
        'website-maintenance'  => ['website-maintenance.html',  '0.8', 'monthly'],
        'seo'                  => ['seo.html',                  '0.9', 'monthly'],
        'mobile-app'           => ['mobile-app.html',           '0.9', 'monthly'],
        'finance'              => ['finance.html',              '0.7', 'monthly'],
        'education'            => ['education.html',            '0.7', 'monthly'],
        'retail'               => ['retail.html',               '0.7', 'monthly'],
        'logistics'            => ['logistics.html',            '0.7', 'monthly'],
        'work'                 => ['work.html',                 '0.6', 'monthly'],
    ];
}

function seo_defaults(): array
{
    return [
        'enabled'       => '1',
        'site_url'      => 'https://codecrafters.com.lk',
        'business_name' => 'CodeCrafters',
        'description'   => 'CodeCrafters is a software development, web design, mobile app development and SEO company serving businesses in Sri Lanka and worldwide.',
        'telephone'     => '',
        'email'         => '',
        'street'        => '',
        'locality'      => '',
        'region'        => '',
        'postal_code'   => '',
        'lat'           => '',
        'lng'           => '',
        'service_areas' => "Colombo\nKandy\nGalle\nNegombo\nJaffna\nKurunegala",
        'same_as'       => '',
    ];
}

function seo_cache_path(): string
{
    return __DIR__ . '/cache/local-seo.json';
}

function seo_ensure_table(): void
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS seo_settings (
            skey       VARCHAR(60) NOT NULL PRIMARY KEY,
            svalue     TEXT        NOT NULL,
            updated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function seo_cache_write(array $s): bool
{
    $dir = dirname(seo_cache_path());
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    if (!is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }
    return @file_put_contents(seo_cache_path(), json_encode($s, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

/** Cache file first (no DB hit per page view), then DB, then defaults. */
function seo_load(): array
{
    $d = seo_defaults();

    $f = seo_cache_path();
    if (is_file($f)) {
        $j = json_decode((string) @file_get_contents($f), true);
        if (is_array($j)) {
            return array_merge($d, array_intersect_key($j, $d));
        }
    }

    try {
        $rows = db()->query('SELECT skey, svalue FROM seo_settings')->fetchAll();
        if ($rows) {
            foreach ($rows as $r) {
                if (array_key_exists($r['skey'], $d)) {
                    $d[$r['skey']] = (string) $r['svalue'];
                }
            }
            seo_cache_write($d);
        }
    } catch (Throwable $ex) {
        error_log('seo_load: ' . $ex->getMessage());
    }
    return $d;
}

/** @return array{0: array, 1: string[]} [clean settings, errors] */
function seo_clean(array $in): array
{
    $errors = [];
    $s = [];

    $t = function (string $key, int $max, string $label) use ($in, &$errors): string {
        $v = trim((string) ($in[$key] ?? ''));
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;
        if (mb_strlen($v) > $max) {
            $errors[] = "$label is too long (max $max characters).";
            $v = '';
        }
        return $v;
    };

    $s['enabled']       = !empty($in['enabled']) ? '1' : '0';
    $s['business_name'] = $t('business_name', 120, 'Business name');
    $s['description']   = $t('description', 300, 'Description');
    $s['street']        = $t('street', 150, 'Street address');
    $s['locality']      = $t('locality', 80, 'City / town');
    $s['region']        = $t('region', 80, 'Province');
    $s['postal_code']   = $t('postal_code', 12, 'Postal code');

    if ($s['business_name'] === '') {
        $errors[] = 'Business name is required.';
    }

    $url = rtrim(trim((string) ($in['site_url'] ?? '')), '/');
    $parts = parse_url($url);
    if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
        $errors[] = 'Site URL must be a full URL such as https://codecrafters.com.lk';
        $url = '';
    }
    $s['site_url'] = $url;

    $tel = trim((string) ($in['telephone'] ?? ''));
    if (!preg_match('/^[0-9+()\-\s]{0,30}$/', $tel)) {
        $errors[] = 'Phone may only contain digits, spaces, + ( ) and -.';
        $tel = '';
    }
    $s['telephone'] = $tel;

    $email = trim((string) ($in['email'] ?? ''));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is not valid.';
        $email = '';
    }
    $s['email'] = $email;

    $lat = trim((string) ($in['lat'] ?? ''));
    $lng = trim((string) ($in['lng'] ?? ''));
    if ($lat !== '' || $lng !== '') {
        if (!is_numeric($lat) || !is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
            $errors[] = 'Latitude and longitude must both be valid numbers (e.g. 6.9271 and 79.8612).';
            $lat = $lng = '';
        }
    }
    $s['lat'] = $lat;
    $s['lng'] = $lng;

    $areas = [];
    foreach (preg_split('/\R/u', (string) ($in['service_areas'] ?? '')) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (mb_strlen($line) > 60) {
            $errors[] = 'Each service area must be under 60 characters.';
            continue;
        }
        $areas[mb_strtolower($line)] = $line;
    }
    if (count($areas) > 25) {
        $errors[] = 'Maximum 25 service areas.';
    }
    $s['service_areas'] = implode("\n", array_slice(array_values($areas), 0, 25));

    $links = [];
    foreach (preg_split('/\R/u', (string) ($in['same_as'] ?? '')) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $p = parse_url($line);
        if (!filter_var($line, FILTER_VALIDATE_URL) || !in_array($p['scheme'] ?? '', ['http', 'https'], true)) {
            $errors[] = "Profile link is not a valid URL: $line";
            continue;
        }
        $links[$line] = $line;
    }
    if (count($links) > 10) {
        $errors[] = 'Maximum 10 profile links.';
    }
    $s['same_as'] = implode("\n", array_slice(array_values($links), 0, 10));

    return [$s, $errors];
}

function seo_save(array $s): bool
{
    seo_ensure_table();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'INSERT INTO seo_settings (skey, svalue) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
        );
        foreach ($s as $k => $v) {
            $st->execute([$k, (string) $v]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return seo_cache_write($s);
}

function seo_page_url(array $s, string $slug): string
{
    $base = rtrim($s['site_url'], '/');
    return $slug === 'index' ? $base . '/' : $base . '/' . $slug . '.html';
}

function seo_lines(string $v): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', $v) ?: []), 'strlen'));
}

function seo_jsonld(array $s): array
{
    $base = rtrim($s['site_url'], '/');

    $area = [['@type' => 'Country', 'name' => 'Sri Lanka']];
    foreach (seo_lines($s['service_areas']) as $city) {
        $area[] = ['@type' => 'City', 'name' => $city];
    }

    $address = ['@type' => 'PostalAddress', 'addressCountry' => 'LK'];
    foreach (['street' => 'streetAddress', 'locality' => 'addressLocality', 'region' => 'addressRegion', 'postal_code' => 'postalCode'] as $k => $prop) {
        if ($s[$k] !== '') {
            $address[$prop] = $s[$k];
        }
    }

    $ld = [
        '@context'    => 'https://schema.org',
        '@type'       => 'ProfessionalService',
        '@id'         => $base . '/#local-sri-lanka',
        'name'        => $s['business_name'],
        'url'         => $base . '/',
        'description' => $s['description'],
        'logo'        => $base . '/img/logo.png',
        'image'       => $base . '/img/logo.png',
        'address'     => $address,
        'areaServed'  => $area,
    ];
    if ($s['telephone'] !== '') {
        $ld['telephone'] = $s['telephone'];
    }
    if ($s['email'] !== '') {
        $ld['email'] = $s['email'];
    }
    if ($s['lat'] !== '' && $s['lng'] !== '') {
        $ld['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $s['lat'], 'longitude' => (float) $s['lng']];
    }
    $same = seo_lines($s['same_as']);
    if ($same) {
        $ld['sameAs'] = $same;
    }
    return $ld;
}

function seo_esc(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** The block injected before </head>. */
function seo_head_snippet(array $s, string $slug): string
{
    $url = seo_esc(seo_page_url($s, $slug));
    $place = $s['locality'] !== '' ? $s['locality'] : 'Sri Lanka';

    $o   = [];
    $o[] = '<!-- local-seo:start -->';
    $o[] = '<meta name="geo.region" content="LK">';
    $o[] = '<meta name="geo.placename" content="' . seo_esc($place) . '">';
    if ($s['lat'] !== '' && $s['lng'] !== '') {
        $o[] = '<meta name="geo.position" content="' . seo_esc($s['lat'] . ';' . $s['lng']) . '">';
        $o[] = '<meta name="ICBM" content="' . seo_esc($s['lat'] . ', ' . $s['lng']) . '">';
    }
    $o[] = '<meta property="og:locale:alternate" content="en_LK">';
    $o[] = '<link rel="alternate" hreflang="en-LK" href="' . $url . '">';
    $o[] = '<link rel="alternate" hreflang="x-default" href="' . $url . '">';
    $o[] = '<script type="application/ld+json">' . "\n"
        . json_encode(seo_jsonld($s), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP)
        . "\n    </script>";
    $o[] = '<!-- local-seo:end -->';

    return '    ' . implode("\n    ", $o) . "\n";
}
