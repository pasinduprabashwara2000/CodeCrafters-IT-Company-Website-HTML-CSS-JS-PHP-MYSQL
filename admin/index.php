<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_admin();

$stats = ['total' => 0, 'unread' => 0, 'week' => 0, 'projects' => 0, 'published' => 0];
$latest = [];
try {
    $stats['total']     = (int) db()->query('SELECT COUNT(*) FROM messages')->fetchColumn();
    $stats['unread']    = (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
    $stats['week']      = (int) db()->query('SELECT COUNT(*) FROM messages WHERE created_at > (NOW() - INTERVAL 7 DAY)')->fetchColumn();
    $stats['projects']  = (int) db()->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $stats['published'] = (int) db()->query('SELECT COUNT(*) FROM projects WHERE is_published = 1')->fetchColumn();
    $latest = db()->query('SELECT id, name, email, subject, is_read, created_at FROM messages ORDER BY id DESC LIMIT 6')->fetchAll();
} catch (Throwable $ex) {
    error_log('dashboard: ' . $ex->getMessage());
    flash('Could not load stats. Check the database.', 'err');
}

admin_header('Dashboard', 'index', $user);
?>
<div class="stats">
    <div class="stat"><strong><?= $stats['unread'] ?></strong><span>Unread messages</span></div>
    <div class="stat"><strong><?= $stats['week'] ?></strong><span>Messages this week</span></div>
    <div class="stat"><strong><?= $stats['total'] ?></strong><span>Total messages</span></div>
    <div class="stat"><strong><?= $stats['published'] ?>/<?= $stats['projects'] ?></strong><span>Projects published</span></div>
</div>

<section class="panel">
    <div class="panel-head">
        <h2>Latest messages</h2>
        <a href="messages.php">View all →</a>
    </div>
    <?php if (!$latest): ?>
        <p class="muted">No messages yet.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>From</th><th>Subject</th><th>Received</th></tr></thead>
            <tbody>
            <?php foreach ($latest as $m): ?>
                <tr class="<?= $m['is_read'] ? '' : 'unread' ?>">
                    <td><a href="messages.php?id=<?= (int) $m['id'] ?>"><?= e($m['name']) ?></a><br><small><?= e($m['email']) ?></small></td>
                    <td><?= e($m['subject'] !== '' ? $m['subject'] : '(no subject)') ?></td>
                    <td><?= e($m['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php admin_footer();
