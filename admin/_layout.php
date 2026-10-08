<?php
declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === '_layout.php') {
    http_response_code(403);
    exit;
}

function admin_header(string $title, string $active, array $user): void
{
    admin_headers();
    $unread = 0;
    try {
        $unread = (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
    } catch (Throwable $ex) {
    }
    $nav = [
        'index'    => ['index.php', 'Dashboard'],
        'messages' => ['messages.php', 'Messages'],
        'projects' => ['projects.php', 'Projects'],
        'prices'   => ['prices.php', 'Prices'],
        'localseo' => ['local-seo.php', 'Local SEO'],
        'whatsapp' => ['whatsapp.php', 'WhatsApp'],
        'account'  => ['account.php', 'Account'],
    ];
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> · CodeCrafters Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon-32.png">
    <link rel="shortcut icon" href="../img/favicon.ico">
    <link rel="apple-touch-icon" href="../img/favicon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand">Code<span>Crafters</span></div>
    <nav>
        <?php foreach ($nav as $key => [$href, $label]): ?>
            <a href="<?= e($href) ?>" class="<?= $key === $active ? 'active' : '' ?>">
                <?= e($label) ?>
                <?php if ($key === 'messages' && $unread > 0): ?><em class="pill"><?= $unread ?></em><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="side-foot">
        <div class="who">Signed in as <strong><?= e($user['username']) ?></strong></div>
        <a href="../index.html" target="_blank" rel="noopener">View site ↗</a>
        <form method="post" action="logout.php">
            <?= csrf_field() ?>
            <button type="submit" class="link-btn">Sign out</button>
        </form>
    </div>
</aside>
<main class="content">
    <h1><?= e($title) ?></h1>
    <?php if ($f = flash()): ?>
        <div class="alert <?= $f['type'] === 'err' ? 'err' : 'ok' ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
</main>
</body>
</html>
<?php
}