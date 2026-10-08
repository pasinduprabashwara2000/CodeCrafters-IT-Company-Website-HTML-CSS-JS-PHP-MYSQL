<?php
declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'wa-lib.php') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/seo-lib.php'; // shares the seo_settings table and the cache folder

function wa_defaults(): array
{
    return [
        'wa_enabled' => '0',
        'wa_number'  => '',
        'wa_message' => 'Hello CodeCrafters, I would like to know more about your services.',
    ];
}

function wa_cache_path(): string
{
    return __DIR__ . '/cache/whatsapp.json';
}

function wa_cache_write(array $s): bool
{
    $dir = dirname(wa_cache_path());
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    if (!is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }
    return @file_put_contents(wa_cache_path(), json_encode($s, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

/** Cache file first (no DB hit per visit), then DB, then defaults. */
function wa_load(): array
{
    $d = wa_defaults();

    $f = wa_cache_path();
    if (is_file($f)) {
        $j = json_decode((string) @file_get_contents($f), true);
        if (is_array($j)) {
            return array_merge($d, array_intersect_key($j, $d));
        }
    }

    try {
        $st = db()->prepare("SELECT skey, svalue FROM seo_settings WHERE skey LIKE 'wa\\_%'");
        $st->execute();
        $rows = $st->fetchAll();
        if ($rows) {
            foreach ($rows as $r) {
                if (array_key_exists($r['skey'], $d)) {
                    $d[$r['skey']] = (string) $r['svalue'];
                }
            }
            wa_cache_write($d);
        }
    } catch (Throwable $ex) {
        error_log('wa_load: ' . $ex->getMessage());
    }
    return $d;
}

/** @return array{0: array, 1: string[]} */
function wa_clean(array $in): array
{
    $errors = [];
    $s = ['wa_enabled' => !empty($in['wa_enabled']) ? '1' : '0'];

    $num = trim((string) ($in['wa_number'] ?? ''));
    $digits = preg_replace('/\D/', '', $num) ?? '';
    if ($num !== '') {
        if ($num[0] !== '+' || !preg_match('/^\+[0-9\s()\-]{7,25}$/', $num) || strlen($digits) < 8 || strlen($digits) > 15) {
            $errors[] = 'Enter the WhatsApp number with country code, e.g. +94 77 123 4567.';
            $num = '';
        }
    } elseif ($s['wa_enabled'] === '1') {
        $errors[] = 'Enter a WhatsApp number before enabling the popup.';
    }
    $s['wa_number'] = $num;

    $msg = trim((string) ($in['wa_message'] ?? ''));
    $msg = preg_replace('/\s+/u', ' ', $msg) ?? $msg;
    if (mb_strlen($msg) > 200) {
        $errors[] = 'Pre-filled message is too long (max 200 characters).';
        $msg = '';
    }
    $s['wa_message'] = $msg;

    return [$s, $errors];
}

function wa_save(array $s): bool
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
    return wa_cache_write($s);
}
