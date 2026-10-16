<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/../api/plan-examples-lib.php';
require __DIR__ . '/_layout.php';

$user = require_admin();

$PAGES = [
    'web-design'           => 'Web Design & Development',
    'software-development' => 'Software Development',
    'website-maintenance'  => 'Website Maintenance',
    'seo'                  => 'Search Engine Optimization',
    'mobile-app'           => 'Mobile App Development',
    'finance'              => 'Finance',
    'education'            => 'Education',
    'retail'               => 'Retail',
    'logistics'            => 'Logistics',
];

$cat = (string) ($_GET['cat'] ?? ($_POST['cat'] ?? 'web-design'));
if (!isset($PAGES[$cat])) {
    $cat = 'web-design';
}

// Plan names for the chosen category (live prices table, falling back to built-in defaults).
$plans = [];
try {
    $st = db()->prepare('SELECT position, plan_name FROM pricing_plans WHERE page_slug = ? ORDER BY position');
    $st->execute([$cat]);
    foreach ($st->fetchAll() as $r) {
        $plans[(int) $r['position']] = $r['plan_name'];
    }
} catch (Throwable $ex) {
}
if (!$plans) {
    foreach (require __DIR__ . '/../api/price-defaults.php' as $d) {
        if ($d['page'] === $cat) {
            $plans[(int) $d['pos']] = $d['name'];
        }
    }
}

$errors = [];
$form = ['id' => 0, 'position' => (int) ($_GET['plan'] ?? array_key_first($plans) ?? 1), 'title' => '', 'url' => '',
         'description' => '', 'tech' => '', 'logo_path' => '', 'sort_order' => 0, 'is_published' => 1];

try {
    ensure_plan_examples_table();
} catch (Throwable $ex) {
    error_log('plan_examples table: ' . $ex->getMessage());
    $errors[] = 'Could not create the plan_examples table. Check database permissions.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'delete' && $id > 0) {
            db()->prepare('DELETE FROM plan_examples WHERE id = ? AND page_slug = ?')->execute([$id, $cat]);
            flash('Example deleted.');
            redirect('examples.php?cat=' . $cat);
        }
        if ($action === 'toggle' && $id > 0) {
            db()->prepare('UPDATE plan_examples SET is_published = 1 - is_published WHERE id = ? AND page_slug = ?')->execute([$id, $cat]);
            redirect('examples.php?cat=' . $cat);
        }
        if ($action === 'import_defaults') {
            $has = db()->prepare('SELECT COUNT(*) FROM plan_examples WHERE page_slug = ? AND position = ? AND title = ?');
            $ins = db()->prepare('INSERT INTO plan_examples (page_slug, position, title, url, description, tech, logo_path, sort_order) VALUES (?,?,?,?,?,?,?,?)');
            $n = 0;
            foreach (plan_example_defaults() as $i => [$slug, $pos, $title, $url, $desc, $tech, $logo]) {
                $has->execute([$slug, $pos, $title]);
                if ((int) $has->fetchColumn() === 0) {
                    $ins->execute([$slug, $pos, $title, $url, $desc, $tech, $logo, $i]);
                    $n++;
                }
            }
            flash($n > 0 ? "Imported $n sample examples." : 'Sample examples already imported.');
            redirect('examples.php?cat=' . $cat);
        }
        if ($action === 'save') {
            $form = [
                'id'           => $id,
                'position'     => (int) ($_POST['position'] ?? 0),
                'title'        => trim((string) ($_POST['title'] ?? '')),
                'url'          => trim((string) ($_POST['url'] ?? '')),
                'description'  => trim((string) ($_POST['description'] ?? '')),
                'tech'         => trim((string) ($_POST['tech'] ?? '')),
                'logo_path'    => trim((string) ($_POST['logo_path'] ?? '')),
                'sort_order'   => (int) ($_POST['sort_order'] ?? 0),
                'is_published' => isset($_POST['is_published']) ? 1 : 0,
            ];
            if (!isset($plans[$form['position']])) $errors[] = 'Choose a valid price plan.';
            if ($form['title'] === '' || mb_strlen($form['title']) > 160) $errors[] = 'Title is required (max 160 chars).';
            if (mb_strlen($form['description']) > 300) $errors[] = 'Description: max 300 characters.';
            if (mb_strlen($form['tech']) > 255) $errors[] = 'Tech: max 255 characters.';
            $logo = plan_example_clean_logo($form['logo_path']);
            if ($logo === null || mb_strlen($form['logo_path']) > 255) $errors[] = 'Company logo must be an http(s) URL or a relative path like img/logos/x.png.';
            $url = plan_example_clean_url($form['url']);
            if ($url === null) $errors[] = 'Website URL must start with http:// or https://.';

            if (!$errors) {
                $p = [$form['position'], $form['title'], $url, $form['description'], $form['tech'], $logo, $form['sort_order'], $form['is_published']];
                if ($id > 0) {
                    $p[] = $id;
                    $p[] = $cat;
                    db()->prepare('UPDATE plan_examples SET position=?, title=?, url=?, description=?, tech=?, logo_path=?, sort_order=?, is_published=? WHERE id=? AND page_slug=?')->execute($p);
                    flash('Example updated.');
                } else {
                    array_unshift($p, $cat);
                    db()->prepare('INSERT INTO plan_examples (page_slug, position, title, url, description, tech, logo_path, sort_order, is_published) VALUES (?,?,?,?,?,?,?,?,?)')->execute($p);
                    flash('Example added.');
                }
                redirect('examples.php?cat=' . $cat);
            }
        }
    } catch (Throwable $ex) {
        error_log('examples: ' . $ex->getMessage());
        $errors[] = 'Database error. Nothing was saved.';
    }
}

$editing = isset($_GET['new']) || isset($_GET['edit']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors && ($_POST['action'] ?? '') === 'save');

if (isset($_GET['edit']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $st = db()->prepare('SELECT * FROM plan_examples WHERE id = ? AND page_slug = ?');
    $st->execute([(int) $_GET['edit'], $cat]);
    $row = $st->fetch();
    if (!$row) {
        flash('Example not found.', 'err');
        redirect('examples.php?cat=' . $cat);
    }
    $form = $row;
}

admin_header('Plan Examples', 'examples', $user);

if ($editing): ?>
    <section class="panel">
        <h2><?= $form['id'] ? 'Edit example' : 'New example' ?> · <?= e($PAGES[$cat]) ?></h2>
        <?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post" action="examples.php?cat=<?= e($cat) ?>" class="grid-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="cat" value="<?= e($cat) ?>">
            <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">

            <label>Price plan *
                <select name="position">
                    <?php foreach ($plans as $pos => $name): ?>
                        <option value="<?= $pos ?>" <?= (int) $form['position'] === $pos ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Project title *<input name="title" value="<?= e($form['title']) ?>" required maxlength="160"></label>
            <label class="wide">Website URL (optional)<input name="url" value="<?= e($form['url']) ?>" maxlength="255" placeholder="https://example.com"></label>
            <label class="wide">Company logo (img src: URL or path)<input name="logo_path" value="<?= e($form['logo_path']) ?>" maxlength="255" placeholder="img/logos/company.png"></label>
            <label class="wide">Short description<textarea name="description" rows="3" maxlength="300"><?= e($form['description']) ?></textarea></label>
            <label class="wide">Tech (comma separated)<input name="tech" value="<?= e($form['tech']) ?>" maxlength="255" placeholder="React, Node.js, MongoDB"></label>
            <label>Sort order (lower first)<input type="number" name="sort_order" value="<?= (int) $form['sort_order'] ?>"></label>
            <label class="check"><input type="checkbox" name="is_published" value="1" <?= $form['is_published'] ? 'checked' : '' ?>> Show on the service page</label>

            <div class="actions wide">
                <button class="btn" type="submit">Save example</button>
                <a class="btn ghost" href="examples.php?cat=<?= e($cat) ?>">Cancel</a>
            </div>
        </form>
    </section>
<?php else:
    $byPos = [];
    try {
        $st = db()->prepare('SELECT * FROM plan_examples WHERE page_slug = ? ORDER BY position, sort_order, id');
        $st->execute([$cat]);
        foreach ($st->fetchAll() as $r) {
            $byPos[(int) $r['position']][] = $r;
        }
    } catch (Throwable $ex) {
    }
    ?>
    <?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>
    <div class="toolbar">
        <form method="get" class="filter-form">
            <label for="cat">Category</label>
            <select name="cat" id="cat" onchange="this.form.submit()">
                <?php foreach ($PAGES as $slug => $label): ?>
                    <option value="<?= e($slug) ?>" <?= $cat === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button class="btn ghost" type="submit">Filter</button></noscript>
        </form>
        <form method="post" action="examples.php?cat=<?= e($cat) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="cat" value="<?= e($cat) ?>">
            <button class="btn ghost" name="action" value="import_defaults">Import sample examples</button>
        </form>
        <a class="btn" href="examples.php?cat=<?= e($cat) ?>&new=1">+ New example</a>
    </div>
    <p class="muted">Published examples appear under the matching price card on the <a href="../<?= e($cat) ?>.html" target="_blank" rel="noopener"><?= e($PAGES[$cat]) ?> page ↗</a>.</p>

    <?php foreach ($plans as $pos => $name): $list = $byPos[$pos] ?? []; ?>
        <section class="panel">
            <div class="panel-head">
                <h2><?= e($name) ?> <small>· Card #<?= $pos ?></small></h2>
                <a href="examples.php?cat=<?= e($cat) ?>&new=1&plan=<?= $pos ?>">+ Add example</a>
            </div>
            <?php if (!$list): ?>
                <p class="muted">No examples for this plan yet.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Project</th><th>Tech</th><th>Order</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($list as $x): ?>
                        <tr>
                            <td><?php if ($x['logo_path'] !== ''): ?><img src="<?= e(preg_match('#^https?://#i', $x['logo_path']) ? $x['logo_path'] : '../' . $x['logo_path']) ?>" alt="" style="height:22px;max-width:60px;object-fit:contain;vertical-align:middle;margin-right:8px"><?php endif; ?><strong><?= e($x['title']) ?></strong><br><small><?= e($x['url']) ?></small></td>
                            <td><?= e($x['tech']) ?></td>
                            <td><?= (int) $x['sort_order'] ?></td>
                            <td><span class="badge <?= $x['is_published'] ? 'on' : 'off' ?>"><?= $x['is_published'] ? 'Published' : 'Draft' ?></span></td>
                            <td class="row-actions">
                                <a href="examples.php?cat=<?= e($cat) ?>&edit=<?= (int) $x['id'] ?>">Edit</a>
                                <form method="post" action="examples.php?cat=<?= e($cat) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
                                    <button class="link-btn" name="action" value="toggle"><?= $x['is_published'] ? 'Unpublish' : 'Publish' ?></button>
                                </form>
                                <form method="post" action="examples.php?cat=<?= e($cat) ?>" onsubmit="return confirm('Delete this example permanently?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
                                    <button class="link-btn danger-text" name="action" value="delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
<?php endif;

admin_footer();
