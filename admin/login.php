<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
admin_headers();
start_session();

if (admin_user()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ip = client_ip();

    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
        );
        $stmt->execute([$ip]);

        if ((int) $stmt->fetchColumn() >= 5) {
            $error = 'Too many failed attempts. Try again in 15 minutes.';
        } else {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Verify against a dummy hash when the user is unknown to keep timing similar.
            $hash = $user['password_hash'] ?? '$2y$10$xMWC.0vy3BHXS03NzSgVm.y2Ri0Qa1rJRotX5UroRiaclG2B42DDi';
            $ok   = password_verify($password, $hash) && $user;

            if ($ok) {
                db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
                session_regenerate_id(true);
                $_SESSION['admin_id']   = (int) $user['id'];
                $_SESSION['admin_name'] = $user['username'];
                $_SESSION['csrf']       = bin2hex(random_bytes(32));
                redirect('index.php');
            }

            db()->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);
            $error = 'Invalid username or password.';
        }
    } catch (Throwable $ex) {
        error_log('login.php: ' . $ex->getMessage());
        $error = 'Server error. Check the database configuration.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · CodeCrafters Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon-32.png">
    <link rel="shortcut icon" href="../img/favicon.ico">
    <link rel="apple-touch-icon" href="../img/favicon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="auth-body">
<main class="auth-card">
    <h1>CodeCrafters <span>Admin</span></h1>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <label>Username
            <input type="text" name="username" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button class="btn" type="submit">Sign in</button>
    </form>
</main>
</body>
</html>