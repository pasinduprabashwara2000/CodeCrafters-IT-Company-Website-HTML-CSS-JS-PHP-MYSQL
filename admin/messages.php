<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_admin();

// ---- Actions (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'toggle_read' && $id > 0) {
            db()->prepare('UPDATE messages SET is_read = 1 - is_read WHERE id = ?')->execute([$id]);
            redirect('messages.php' . (isset($_POST['back_view']) ? '?id=' . $id : ''));
        } elseif ($action === 'delete' && $id > 0) {
            db()->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
            flash('Message deleted.');
            redirect('messages.php');
        } elseif ($action === 'mark_all_read') {
            db()->exec('UPDATE messages SET is_read = 1');
            flash('All messages marked as read.');
            redirect('messages.php');
        }
    } catch (Throwable $ex) {
        error_log('messages: ' . $ex->getMessage());
        flash('Action failed.', 'err');
    }
    redirect('messages.php');
}

// ---- CSV export ----
if (($_GET['export'] ?? '') === 'csv') {
    admin_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="messages-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Name', 'Email', 'Subject', 'Message', 'Read', 'Received']);
    $safe = static fn(string $v): string => preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v; // block formula injection
    foreach (db()->query('SELECT * FROM messages ORDER BY id DESC') as $m) {
        fputcsv($out, [
            $m['id'], $safe($m['name']), $safe($m['email']), $safe($m['subject']),
            $safe($m['message']), $m['is_read'] ? 'yes' : 'no', $m['created_at'],
        ]);
    }
    exit;
}

// ---- Single message view ----
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = db()->prepare('SELECT * FROM messages WHERE id = ?');
    $stmt->execute([$id]);
    $m = $stmt->fetch();

    if (!$m) {
        flash('Message not found.', 'err');
        redirect('messages.php');
    }
    if (!$m['is_read']) {
        db()->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$id]);
        $m['is_read'] = 1;
    }

    admin_header('Message', 'messages', $user);
    $subject = $m['subject'] !== '' ? $m['subject'] : 'Your enquiry';
    ?>
    <section class="panel">
        <dl class="meta">
            <dt>From</dt><dd><?= e($m['name']) ?> &lt;<a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $subject) ?>"><?= e($m['email']) ?></a>&gt;</dd>
            <dt>Subject</dt><dd><?= e($m['subject'] !== '' ? $m['subject'] : '(no subject)') ?></dd>
            <dt>Received</dt><dd><?= e($m['created_at']) ?> · IP <?= e($m['ip']) ?></dd>
        </dl>
        <div class="msg-body"><?= nl2br(e($m['message'])) ?></div>
        <div class="actions">
            <a class="btn" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $subject) ?>">Reply by email</a>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                <input type="hidden" name="back_view" value="1">
                <button class="btn ghost" name="action" value="toggle_read">Mark as unread</button>
            </form>
            <form method="post" onsubmit="return confirm('Delete this message permanently?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                <button class="btn danger" name="action" value="delete">Delete</button>
            </form>
            <a class="btn ghost" href="messages.php">← Back</a>
        </div>
    </section>
    <?php
    admin_footer();
    exit;
}

// ---- List ----
$filter  = ($_GET['filter'] ?? '') === 'unread' ? 'unread' : 'all';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$where   = $filter === 'unread' ? 'WHERE is_read = 0' : '';

$total = (int) db()->query("SELECT COUNT(*) FROM messages $where")->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

$rows = db()->query(
    "SELECT id, name, email, subject, is_read, created_at FROM messages $where
     ORDER BY id DESC LIMIT $perPage OFFSET $offset"
)->fetchAll();

admin_header('Messages', 'messages', $user);
?>
<div class="toolbar">
    <div class="tabs">
        <a href="messages.php" class="<?= $filter === 'all' ? 'active' : '' ?>">All</a>
        <a href="messages.php?filter=unread" class="<?= $filter === 'unread' ? 'active' : '' ?>">Unread</a>
    </div>
    <div class="actions">
        <form method="post">
            <?= csrf_field() ?>
            <button class="btn ghost" name="action" value="mark_all_read">Mark all read</button>
        </form>
        <a class="btn ghost" href="messages.php?export=csv">Export CSV</a>
    </div>
</div>

<section class="panel">
    <?php if (!$rows): ?>
        <p class="muted">No messages.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $m): ?>
                <tr class="<?= $m['is_read'] ? '' : 'unread' ?>">
                    <td><a href="messages.php?id=<?= (int) $m['id'] ?>"><?= e($m['name']) ?></a><br><small><?= e($m['email']) ?></small></td>
                    <td><?= e($m['subject'] !== '' ? $m['subject'] : '(no subject)') ?></td>
                    <td><?= e($m['created_at']) ?></td>
                    <td class="row-actions">
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <button class="link-btn" name="action" value="toggle_read"><?= $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button>
                        </form>
                        <form method="post" onsubmit="return confirm('Delete this message permanently?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <button class="link-btn danger-text" name="action" value="delete">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($pages > 1): ?>
            <div class="pager">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a class="<?= $i === $page ? 'active' : '' ?>" href="messages.php?<?= $filter === 'unread' ? 'filter=unread&' : '' ?>page=<?= $i ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php admin_footer();
