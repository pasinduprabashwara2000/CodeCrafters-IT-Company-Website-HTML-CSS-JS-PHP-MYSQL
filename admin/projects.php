<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_admin();

function clean_url(string $u, bool $allowRelative = false): ?string
{
    $u = trim($u);
    if ($u === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $u) && filter_var($u, FILTER_VALIDATE_URL)) {
        return $u;
    }
    // Relative image path like img/logos/name.png (no traversal, no scheme)
    if ($allowRelative && preg_match('#^[A-Za-z0-9_./-]+$#', $u) && strpos($u, '..') === false) {
        return $u;
    }
    return null;
}

$errors = [];
$form   = [
    'id' => 0, 'client' => '', 'domain' => '', 'url' => '', 'tag' => '', 'title' => '',
    'description' => '', 'tech' => '', 'thumb_style' => 'a', 'thumb_icon' => '', 'logo_path' => '',
    'sort_order' => 0, 'is_published' => 1,
];

// ---- POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'delete' && $id > 0) {
            db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
            flash('Project deleted.');
            redirect('projects.php');
        }

        if ($action === 'toggle' && $id > 0) {
            db()->prepare('UPDATE projects SET is_published = 1 - is_published WHERE id = ?')->execute([$id]);
            redirect('projects.php');
        }

        if ($action === 'save') {
            $form = [
                'id'           => $id,
                'client'       => trim((string) ($_POST['client'] ?? '')),
                'domain'       => trim((string) ($_POST['domain'] ?? '')),
                'url'          => trim((string) ($_POST['url'] ?? '')),
                'tag'          => trim((string) ($_POST['tag'] ?? '')),
                'title'        => trim((string) ($_POST['title'] ?? '')),
                'description'  => trim((string) ($_POST['description'] ?? '')),
                'tech'         => trim((string) ($_POST['tech'] ?? '')),
                'thumb_style'  => (string) ($_POST['thumb_style'] ?? 'a'),
                'thumb_icon'   => trim((string) ($_POST['thumb_icon'] ?? '')),
                'logo_path'    => trim((string) ($_POST['logo_path'] ?? '')),
                'sort_order'   => (int) ($_POST['sort_order'] ?? 0),
                'is_published' => isset($_POST['is_published']) ? 1 : 0,
            ];

            if ($form['client'] === '' || mb_strlen($form['client']) > 120) $errors[] = 'Client is required (max 120 chars).';
            if ($form['title'] === '' || mb_strlen($form['title']) > 190)   $errors[] = 'Title is required (max 190 chars).';
            if ($form['description'] === '')                                 $errors[] = 'Description is required.';
            if (mb_strlen($form['domain']) > 120 || mb_strlen($form['tag']) > 60 || mb_strlen($form['tech']) > 255) {
                $errors[] = 'Domain, tag or tech is too long.';
            }
            if (mb_strlen($form['thumb_icon']) > 8) $errors[] = 'Icon: max 8 characters.';
            if (!in_array($form['thumb_style'], ['a', 'b', 'c', 'd', 'e', 'f'], true)) $form['thumb_style'] = 'a';

            $url  = clean_url($form['url']);
            $logo = clean_url($form['logo_path'], true);
            if ($url === null)  $errors[] = 'Website URL must start with http:// or https://.';
            if ($logo === null) $errors[] = 'Logo must be an http(s) URL or a relative path like img/logos/x.png.';

            if (!$errors) {
                $params = [
                    $form['client'], $form['domain'], $url, $form['tag'], $form['title'], $form['description'],
                    $form['tech'], $form['thumb_style'], $form['thumb_icon'], $logo,
                    $form['sort_order'], $form['is_published'],
                ];
                if ($id > 0) {
                    $params[] = $id;
                    db()->prepare(
                        'UPDATE projects SET client=?, domain=?, url=?, tag=?, title=?, description=?, tech=?,
                         thumb_style=?, thumb_icon=?, logo_path=?, sort_order=?, is_published=? WHERE id=?'
                    )->execute($params);
                    flash('Project updated.');
                } else {
                    db()->prepare(
                        'INSERT INTO projects (client, domain, url, tag, title, description, tech,
                         thumb_style, thumb_icon, logo_path, sort_order, is_published)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                    )->execute($params);
                    flash('Project created.');
                }
                redirect('projects.php');
            }
        }
    } catch (Throwable $ex) {
        error_log('projects: ' . $ex->getMessage());
        $errors[] = 'Database error. Nothing was saved.';
    }
}

$editing = isset($_GET['new']) || isset($_GET['edit']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors);

if (isset($_GET['edit']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = db()->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('Project not found.', 'err');
        redirect('projects.php');
    }
    $form = $row;
}

admin_header('Projects', 'projects', $user);

if ($editing): ?>
    <section class="panel">
        <h2><?= $form['id'] ? 'Edit project' : 'New project' ?></h2>
        <?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post" class="grid-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">

            <label>Client *<input name="client" value="<?= e($form['client']) ?>" required maxlength="120"></label>
            <label>Domain (display)<input name="domain" value="<?= e($form['domain']) ?>" maxlength="120" placeholder="example.com"></label>
            <label>Website URL<input name="url" value="<?= e($form['url']) ?>" maxlength="255" placeholder="https://example.com"></label>
            <label>Tag<input name="tag" value="<?= e($form['tag']) ?>" maxlength="60" placeholder="E-Commerce"></label>
            <label class="wide">Title *<input name="title" value="<?= e($form['title']) ?>" required maxlength="190"></label>
            <label class="wide">Description *<textarea name="description" rows="4" required><?= e($form['description']) ?></textarea></label>
            <label class="wide">Tech (comma separated)<input name="tech" value="<?= e($form['tech']) ?>" maxlength="255" placeholder="Next.js, TypeScript, Stripe"></label>
            <label>Thumbnail colour
                <select name="thumb_style">
                    <?php foreach (['a' => 'Blue / violet', 'b' => 'Orange / pink', 'c' => 'Cyan / blue', 'd' => 'Green / cyan', 'e' => 'Violet / magenta', 'f' => 'Pink / amber'] as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= $form['thumb_style'] === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Thumbnail icon (emoji/symbol)<input name="thumb_icon" value="<?= e($form['thumb_icon']) ?>" maxlength="8"></label>
            <label class="wide">Logo image (optional, overrides icon)<input name="logo_path" value="<?= e($form['logo_path']) ?>" maxlength="255" placeholder="img/logos/client.png"></label>
            <label>Sort order (lower first)<input type="number" name="sort_order" value="<?= (int) $form['sort_order'] ?>"></label>
            <label class="check"><input type="checkbox" name="is_published" value="1" <?= $form['is_published'] ? 'checked' : '' ?>> Published on Our Work page</label>

            <div class="actions wide">
                <button class="btn" type="submit">Save project</button>
                <a class="btn ghost" href="projects.php">Cancel</a>
            </div>
        </form>
    </section>
<?php else:
    $rows = db()->query('SELECT id, client, domain, tag, title, sort_order, is_published FROM projects ORDER BY sort_order ASC, id DESC')->fetchAll();
    ?>
    <div class="toolbar">
        <p class="muted">Published projects replace the static cards on the Our Work page.</p>
        <a class="btn" href="projects.php?new=1">+ New project</a>
    </div>
    <section class="panel">
        <?php if (!$rows): ?>
            <p class="muted">No projects yet. The Our Work page shows its built-in cards until you publish one.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Client</th><th>Title</th><th>Tag</th><th>Order</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $p): ?>
                    <tr>
                        <td><strong><?= e($p['client']) ?></strong><br><small><?= e($p['domain']) ?></small></td>
                        <td><?= e($p['title']) ?></td>
                        <td><?= e($p['tag']) ?></td>
                        <td><?= (int) $p['sort_order'] ?></td>
                        <td><span class="badge <?= $p['is_published'] ? 'on' : 'off' ?>"><?= $p['is_published'] ? 'Published' : 'Draft' ?></span></td>
                        <td class="row-actions">
                            <a href="projects.php?edit=<?= (int) $p['id'] ?>">Edit</a>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button class="link-btn" name="action" value="toggle"><?= $p['is_published'] ? 'Unpublish' : 'Publish' ?></button>
                            </form>
                            <form method="post" onsubmit="return confirm('Delete this project permanently?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button class="link-btn danger-text" name="action" value="delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php endif;

admin_footer();
