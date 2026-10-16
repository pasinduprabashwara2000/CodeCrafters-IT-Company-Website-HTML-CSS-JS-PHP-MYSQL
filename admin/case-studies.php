<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/../api/case-studies-lib.php';
require __DIR__ . '/_layout.php';

$user = require_admin();

$errors = [];
$form = ['id' => 0, 'label' => '', 'title' => '', 'description' => '', 'tech' => '', 'metrics' => '',
         'quote' => '', 'quote_author' => '', 'image_src' => '', 'sort_order' => 0, 'is_published' => 1];

try {
    ensure_case_studies_table();
} catch (Throwable $ex) {
    error_log('case_studies table: ' . $ex->getMessage());
    $errors[] = 'Could not create the case_studies table. Check database permissions.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'delete' && $id > 0) {
            db()->prepare('DELETE FROM case_studies WHERE id = ?')->execute([$id]);
            flash('Case study deleted.');
            redirect('case-studies.php');
        }
        if ($action === 'toggle' && $id > 0) {
            db()->prepare('UPDATE case_studies SET is_published = 1 - is_published WHERE id = ?')->execute([$id]);
            redirect('case-studies.php');
        }
        if ($action === 'save') {
            $form = [
                'id'           => $id,
                'label'        => trim((string) ($_POST['label'] ?? '')),
                'title'        => trim((string) ($_POST['title'] ?? '')),
                'description'  => trim((string) ($_POST['description'] ?? '')),
                'tech'         => trim((string) ($_POST['tech'] ?? '')),
                'metrics'      => trim((string) ($_POST['metrics'] ?? '')),
                'quote'        => trim((string) ($_POST['quote'] ?? '')),
                'quote_author' => trim((string) ($_POST['quote_author'] ?? '')),
                'image_src'    => trim((string) ($_POST['image_src'] ?? '')),
                'sort_order'   => (int) ($_POST['sort_order'] ?? 0),
                'is_published' => isset($_POST['is_published']) ? 1 : 0,
            ];
            if ($form['label'] === '' || mb_strlen($form['label']) > 120) $errors[] = 'Label is required (max 120 chars).';
            if ($form['title'] === '' || mb_strlen($form['title']) > 190) $errors[] = 'Title is required (max 190 chars).';
            if ($form['description'] === '') $errors[] = 'Description is required.';
            if (mb_strlen($form['tech']) > 255) $errors[] = 'Tech: max 255 characters.';
            if (mb_strlen($form['quote_author']) > 160) $errors[] = 'Quote author: max 160 characters.';
            $img = case_study_clean_image($form['image_src']);
            if ($img === null || mb_strlen($form['image_src']) > 255) $errors[] = 'Image must be an http(s) URL or a relative path like img/case-studies/x.png.';
            if (mb_strlen($form['metrics']) > 1000 || mb_strlen($form['quote']) > 1000) $errors[] = 'Metrics or quote is too long.';

            if (!$errors) {
                $p = [$form['label'], $form['title'], $form['description'], $form['tech'], $form['metrics'],
                      $form['quote'], $form['quote_author'], $img, $form['sort_order'], $form['is_published']];
                if ($id > 0) {
                    $p[] = $id;
                    db()->prepare(
                        'UPDATE case_studies SET label=?, title=?, description=?, tech=?, metrics=?, quote=?, quote_author=?,
                         image_src=?, sort_order=?, is_published=? WHERE id=?'
                    )->execute($p);
                    flash('Case study updated.');
                } else {
                    db()->prepare(
                        'INSERT INTO case_studies (label, title, description, tech, metrics, quote, quote_author, image_src, sort_order, is_published)
                         VALUES (?,?,?,?,?,?,?,?,?,?)'
                    )->execute($p);
                    flash('Case study created.');
                }
                redirect('case-studies.php');
            }
        }
    } catch (Throwable $ex) {
        error_log('case-studies: ' . $ex->getMessage());
        $errors[] = 'Database error. Nothing was saved.';
    }
}

$editing = isset($_GET['new']) || isset($_GET['edit']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors && ($_POST['action'] ?? '') === 'save');

if (isset($_GET['edit']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $st = db()->prepare('SELECT * FROM case_studies WHERE id = ?');
    $st->execute([(int) $_GET['edit']]);
    $row = $st->fetch();
    if (!$row) {
        flash('Case study not found.', 'err');
        redirect('case-studies.php');
    }
    $form = $row;
}

admin_header('Case Studies', 'cases', $user);

if ($editing): ?>
    <section class="panel">
        <h2><?= $form['id'] ? 'Edit case study' : 'New case study' ?></h2>
        <?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post" action="case-studies.php" class="grid-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">

            <label>Label (category · client) *<input name="label" value="<?= e($form['label']) ?>" required maxlength="120" placeholder="Mobile · FitPulse"></label>
            <label>Image (img src: URL or path)<input name="image_src" value="<?= e($form['image_src']) ?>" maxlength="255" placeholder="img/case-studies/fitpulse.png"></label>
            <label class="wide">Title *<input name="title" value="<?= e($form['title']) ?>" required maxlength="190"></label>
            <label class="wide">Description *<textarea name="description" rows="5" required><?= e($form['description']) ?></textarea></label>
            <label class="wide">Tech (comma separated)<input name="tech" value="<?= e($form['tech']) ?>" maxlength="255" placeholder="Flutter, Firebase, Node.js"></label>
            <label class="wide">Metrics (one per line: value | label)
                <textarea name="metrics" rows="3" placeholder="50,000 | downloads in month one"><?= e($form['metrics']) ?></textarea>
            </label>
            <label class="wide">Client quote<textarea name="quote" rows="3"><?= e($form['quote']) ?></textarea></label>
            <label class="wide">Quote author<input name="quote_author" value="<?= e($form['quote_author']) ?>" maxlength="160" placeholder="Sarah Chen, Founder of FitPulse"></label>
            <label>Sort order (lower first)<input type="number" name="sort_order" value="<?= (int) $form['sort_order'] ?>"></label>
            <label class="check"><input type="checkbox" name="is_published" value="1" <?= $form['is_published'] ? 'checked' : '' ?>> Published on Our Work page</label>

            <div class="actions wide">
                <button class="btn" type="submit">Save case study</button>
                <a class="btn ghost" href="case-studies.php">Cancel</a>
            </div>
        </form>
    </section>
<?php else:
    $rows = [];
    try {
        $rows = db()->query('SELECT id, label, title, sort_order, is_published FROM case_studies ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (Throwable $ex) {
    }
    ?>
    <?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>
    <div class="toolbar">
        <p class="muted">Published case studies make up the “Featured Case Studies” section on the <a href="../work.html" target="_blank" rel="noopener">Our Work page ↗</a>. The section is hidden while nothing is published, and cases alternate left/right automatically.</p>
        <a class="btn" href="case-studies.php?new=1">+ New case study</a>
    </div>
    <section class="panel">
        <?php if (!$rows): ?>
            <p class="muted">No case studies yet. The section is hidden on the Our Work page until you add and publish one.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Label</th><th>Title</th><th>Order</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $c): ?>
                    <tr>
                        <td><strong><?= e($c['label']) ?></strong></td>
                        <td><?= e($c['title']) ?></td>
                        <td><?= (int) $c['sort_order'] ?></td>
                        <td><span class="badge <?= $c['is_published'] ? 'on' : 'off' ?>"><?= $c['is_published'] ? 'Published' : 'Draft' ?></span></td>
                        <td class="row-actions">
                            <a href="case-studies.php?edit=<?= (int) $c['id'] ?>">Edit</a>
                            <form method="post" action="case-studies.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button class="link-btn" name="action" value="toggle"><?= $c['is_published'] ? 'Unpublish' : 'Publish' ?></button>
                            </form>
                            <form method="post" action="case-studies.php" onsubmit="return confirm('Delete this case study permanently?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
