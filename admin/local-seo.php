<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/../api/seo-lib.php';
require __DIR__ . '/_layout.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$clean, $errors] = seo_clean($_POST);

    if ($errors) {
        flash(implode(' ', $errors), 'err');
        redirect('local-seo.php');
    }

    try {
        $cached = seo_save($clean);
        flash($cached
            ? 'Local SEO saved. It is live on all pages now.'
            : 'Saved to the database, but api/cache/ is not writable, so pages read the database on every visit. Make api/cache/ writable.',
            $cached ? 'ok' : 'err');
    } catch (Throwable $ex) {
        error_log('local-seo save: ' . $ex->getMessage());
        flash('Database error. Nothing was saved.', 'err');
    }
    redirect('local-seo.php');
}

$s = seo_defaults();
try {
    seo_ensure_table();
    $s = seo_load();
} catch (Throwable $ex) {
    error_log('local-seo load: ' . $ex->getMessage());
    flash('Could not reach the database. Showing defaults.', 'err');
}

$preview = $s['site_url'] !== ''
    ? seo_head_snippet($s, 'index')
    : 'Set a valid Site URL to see the preview.';

admin_header('Local SEO (Sri Lanka)', 'localseo', $user);
?>
<p class="muted">Added to every page's <code>&lt;head&gt;</code> by the server at request time. The HTML files are not edited. Leave address, phone and map fields empty if you don't want them published.</p>

<section class="panel">
    <h2>Settings</h2>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <div class="grid-form">
            <label class="check wide"><input type="checkbox" name="enabled" value="1" <?= $s['enabled'] === '1' ? 'checked' : '' ?>> Enable Sri Lanka local SEO (head tags, structured data and sitemap hreflang)</label>

            <label>Business name<input name="business_name" value="<?= e($s['business_name']) ?>" maxlength="120" required></label>
            <label>Site URL<input name="site_url" value="<?= e($s['site_url']) ?>" maxlength="190" required placeholder="https://codecrafters.com.lk"></label>

            <label class="wide">Description<textarea name="description" rows="2" maxlength="300"><?= e($s['description']) ?></textarea></label>

            <label>Phone<input name="telephone" value="<?= e($s['telephone']) ?>" maxlength="30" placeholder="+94 11 234 5678"></label>
            <label>Email<input name="email" type="email" value="<?= e($s['email']) ?>" maxlength="190"></label>

            <label class="wide">Street address<input name="street" value="<?= e($s['street']) ?>" maxlength="150"></label>
            <label>City / town<input name="locality" value="<?= e($s['locality']) ?>" maxlength="80" placeholder="Colombo"></label>
            <label>Province<input name="region" value="<?= e($s['region']) ?>" maxlength="80" placeholder="Western Province"></label>
            <label>Postal code<input name="postal_code" value="<?= e($s['postal_code']) ?>" maxlength="12"></label>
            <span></span>

            <label>Latitude<input name="lat" value="<?= e($s['lat']) ?>" maxlength="20" placeholder="6.9271"></label>
            <label>Longitude<input name="lng" value="<?= e($s['lng']) ?>" maxlength="20" placeholder="79.8612"></label>

            <label class="wide">Service areas (one per line; Sri Lanka is always included)
                <textarea name="service_areas" rows="6"><?= e($s['service_areas']) ?></textarea>
            </label>
            <label class="wide">Profile links (one per line: Google Business Profile, Facebook, LinkedIn…)
                <textarea name="same_as" rows="3" placeholder="https://"><?= e($s['same_as']) ?></textarea>
            </label>
        </div>
        <div class="actions"><button class="btn" type="submit">Save Local SEO</button></div>
    </form>
</section>

<section class="panel">
    <h2>Preview (home page head block)</h2>
    <textarea readonly rows="22" style="font-family:ui-monospace,Consolas,monospace;font-size:13px"><?= e($preview) ?></textarea>
    <p class="muted">Check it with Google's Rich Results Test after saving.</p>
</section>
<?php admin_footer();
