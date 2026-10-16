<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/../api/ai-lib.php';
require __DIR__ . '/_layout.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'clear_logs') {
        try {
            ai_ensure_logs();
            db()->exec('DELETE FROM chat_logs');
            flash('Chat history cleared.');
        } catch (Throwable $ex) {
            error_log('chatbot clear: ' . $ex->getMessage());
            flash('Could not clear the history.', 'err');
        }
        redirect('chatbot.php');
    }

    [$clean, $errors] = ai_clean($_POST);
    if ($errors) {
        flash(implode(' ', $errors), 'err');
        redirect('chatbot.php');
    }
    try {
        ai_save($clean);
        flash('Chatbot settings saved.');
    } catch (Throwable $ex) {
        error_log('chatbot save: ' . $ex->getMessage());
        flash('Database error. Nothing was saved.', 'err');
    }
    redirect('chatbot.php');
}

$s = ai_defaults();
$logs = [];
$count = 0;
try {
    seo_ensure_table();
    ai_ensure_logs();
    $s = ai_load();
    $count = (int) db()->query('SELECT COUNT(*) FROM chat_logs')->fetchColumn();
    $logs = db()->query('SELECT ip, question, answer, created_at FROM chat_logs ORDER BY id DESC LIMIT 30')->fetchAll();
} catch (Throwable $ex) {
    error_log('chatbot load: ' . $ex->getMessage());
    flash('Could not reach the database. Showing defaults.', 'err');
}

$key = ai_api_key();
$keyStatus = $key === '' ? 'Not set' : 'Set (…' . substr($key, -4) . ')';

admin_header('AI Chatbot', 'chatbot', $user);
?>
<p class="muted">A floating chat assistant on every public page. It answers questions about your services and prices (read live from the Prices page) using the Claude API.</p>

<section class="panel narrow" style="max-width:720px">
    <h2>Settings</h2>
    <p>API key: <strong><?= e($keyStatus) ?></strong><br>
        <small>The key lives in <code>api/config.php</code> (<code>anthropic_api_key</code>) or the <code>ANTHROPIC_API_KEY</code> environment variable. It is never shown here or sent to visitors.</small></p>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <label class="check"><input type="checkbox" name="ai_enabled" value="1" <?= $s['ai_enabled'] === '1' ? 'checked' : '' ?>> Show the chatbot on the website</label>
        <label>Bot name
            <input name="ai_name" value="<?= e($s['ai_name']) ?>" maxlength="40">
        </label>
        <label>Greeting message
            <textarea name="ai_greeting" rows="2" maxlength="300"><?= e($s['ai_greeting']) ?></textarea>
        </label>
        <label>Model
            <input name="ai_model" value="<?= e($s['ai_model']) ?>" maxlength="60">
        </label>
        <label>Extra knowledge (opening hours, process, FAQs, policies…)
            <textarea name="ai_extra" rows="8" maxlength="4000"><?= e($s['ai_extra']) ?></textarea>
        </label>
        <button class="btn" type="submit">Save</button>
    </form>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Recent conversations (<?= $count ?> messages)</h2>
        <?php if ($count > 0): ?>
        <form method="post" onsubmit="return confirm('Delete all chat history?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clear_logs">
            <button class="btn danger" type="submit">Clear history</button>
        </form>
        <?php endif; ?>
    </div>
    <?php if (!$logs): ?>
        <p class="muted">No chats yet.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>When</th><th>Visitor asked</th><th>Bot replied</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $r): ?>
                <tr>
                    <td><?= e($r['created_at']) ?><br><small><?= e($r['ip']) ?></small></td>
                    <td><?= nl2br(e($r['question'])) ?></td>
                    <td><?= nl2br(e($r['answer'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php admin_footer();
