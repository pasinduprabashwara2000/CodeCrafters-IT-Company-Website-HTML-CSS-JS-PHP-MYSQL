<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/../api/wa-lib.php';
require __DIR__ . '/_layout.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$clean, $errors] = wa_clean($_POST);

    if ($errors) {
        flash(implode(' ', $errors), 'err');
        redirect('whatsapp.php');
    }

    try {
        $cached = wa_save($clean);
        flash($cached
            ? 'WhatsApp popup saved. It is live on the home page now.'
            : 'Saved to the database, but api/cache/ is not writable, so the page reads the database on every visit. Make api/cache/ writable.',
            $cached ? 'ok' : 'err');
    } catch (Throwable $ex) {
        error_log('whatsapp save: ' . $ex->getMessage());
        flash('Database error. Nothing was saved.', 'err');
    }
    redirect('whatsapp.php');
}

$s = wa_defaults();
try {
    seo_ensure_table();
    $s = wa_load();
} catch (Throwable $ex) {
    error_log('whatsapp load: ' . $ex->getMessage());
    flash('Could not reach the database. Showing defaults.', 'err');
}

admin_header('WhatsApp', 'whatsapp', $user);
?>
<p class="muted">Shows a floating WhatsApp button on the home page. Clicking it opens a popup with your Business number and a "Start chat" button.</p>

<section class="panel narrow" style="max-width:640px">
    <h2>WhatsApp Business popup</h2>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <label class="check"><input type="checkbox" name="wa_enabled" value="1" <?= $s['wa_enabled'] === '1' ? 'checked' : '' ?>> Show the popup on the home page</label>
        <label>WhatsApp Business number (with country code)
            <input name="wa_number" value="<?= e($s['wa_number']) ?>" maxlength="30" placeholder="+94 77 123 4567">
        </label>
        <label>Pre-filled chat message
            <textarea name="wa_message" rows="3" maxlength="200"><?= e($s['wa_message']) ?></textarea>
        </label>
        <button class="btn" type="submit">Save</button>
    </form>
</section>
<?php admin_footer();
