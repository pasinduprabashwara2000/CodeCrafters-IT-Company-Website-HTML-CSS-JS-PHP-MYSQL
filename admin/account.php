<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_admin();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = (string) ($_POST['current'] ?? '');
    $new     = (string) ($_POST['new'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    try {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = (string) $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 10) {
            $error = 'New password must be at least 10 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('Password updated.');
            redirect('account.php');
        }
    } catch (Throwable $ex) {
        error_log('account: ' . $ex->getMessage());
        $error = 'Database error.';
    }
}

admin_header('Account', 'account', $user);
?>
<section class="panel narrow">
    <h2>Change password</h2>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <label>Current password<input type="password" name="current" required></label>
        <label>New password (min 10 characters)<input type="password" name="new" required minlength="10"></label>
        <label>Confirm new password<input type="password" name="confirm" required minlength="10"></label>
        <button class="btn" type="submit">Update password</button>
    </form>
</section>
<?php admin_footer();
