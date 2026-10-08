<?php
declare(strict_types=1);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_admin();

$PAGES = [
    'software-development' => 'Software Development',
    'web-design'           => 'Web Design & Development',
    'website-maintenance'  => 'Website Maintenance',
    'seo'                  => 'Search Engine Optimization',
    'mobile-app'           => 'Mobile App Development',
    'finance'              => 'Finance',
    'education'            => 'Education',
    'retail'               => 'Retail',
    'logistics'            => 'Logistics',
];

$cat = (string) ($_GET['cat'] ?? '');
if (!isset($PAGES[$cat])) {
    $cat = (string) array_key_first($PAGES); // always show exactly one category
}

function import_default_prices(): int
{
    $defaults = require __DIR__ . '/../api/price-defaults.php';
    $stmt = db()->prepare(
        'INSERT IGNORE INTO pricing_plans (page_slug, position, plan_name, currency, prefix, amount, note, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
    );
    $added = 0;
    foreach ($defaults as $d) {
        $stmt->execute([$d['page'], $d['pos'], $d['name'], $d['currency'], $d['prefix'], $d['amount'], $d['note']]);
        $added += $stmt->rowCount();
    }
    return $added;
}

// ---- POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'import_defaults') {
            $n = import_default_prices();
            flash($n > 0 ? "Imported $n default plans." : 'Nothing to import; all default plans already exist.');
            redirect('prices.php?cat=' . $cat);
        }

        if ($action === 'save_page') {
            $slug = (string) ($_POST['page'] ?? '');
            if (!isset($PAGES[$slug])) {
                flash('Unknown page.', 'err');
                redirect('prices.php');
            }

            $rows = $_POST['plan'] ?? [];
            $errors = [];
            $clean = [];

            foreach ((array) $rows as $id => $r) {
                $id = (int) $id;
                if ($id <= 0 || !is_array($r)) {
                    continue;
                }
                $currency = trim((string) ($r['currency'] ?? ''));
                $prefix   = trim((string) ($r['prefix'] ?? ''));
                $amount   = trim((string) ($r['amount'] ?? ''));
                $note     = trim((string) ($r['note'] ?? ''));
                $active   = isset($r['is_active']) ? 1 : 0;
                $name     = trim((string) ($r['plan_name'] ?? ''));

                if ($amount === '' || mb_strlen($amount) > 40) {
                    $errors[] = ($name ?: "Plan #$id") . ': price text is required (max 40 characters).';
                }
                if (mb_strlen($currency) > 12 || mb_strlen($prefix) > 20 || mb_strlen($note) > 120) {
                    $errors[] = ($name ?: "Plan #$id") . ': currency, prefix or note is too long.';
                }
                $clean[$id] = [$currency, $prefix, $amount, $note, $active];
            }

            if ($errors) {
                flash(implode(' ', $errors), 'err');
                redirect('prices.php?cat=' . $slug);
            }

            $upd = db()->prepare(
                'UPDATE pricing_plans SET currency=?, prefix=?, amount=?, note=?, is_active=?
                 WHERE id=? AND page_slug=?'
            );
            foreach ($clean as $id => [$currency, $prefix, $amount, $note, $active]) {
                $upd->execute([$currency, $prefix, $amount, $note, $active, $id, $slug]);
            }
            flash($PAGES[$slug] . ' prices saved.');
            redirect('prices.php?cat=' . $slug);
        }
    } catch (Throwable $ex) {
        error_log('prices: ' . $ex->getMessage());
        flash('Database error. Nothing was saved. Did you create the pricing_plans table (database/prices.sql)?', 'err');
    }
    redirect('prices.php');
}

$byPage = [];
try {
    $all = db()->query('SELECT * FROM pricing_plans ORDER BY page_slug, position')->fetchAll();
    foreach ($all as $p) {
        $byPage[$p['page_slug']][] = $p;
    }
} catch (Throwable $ex) {
    error_log('prices list: ' . $ex->getMessage());
    flash('Could not load prices. Run database/prices.sql to create the pricing_plans table.', 'err');
}

admin_header('Prices', 'prices', $user);
?>
<div class="toolbar">
    <form method="get" class="filter-form">
        <label for="cat">Category</label>
        <select name="cat" id="cat" onchange="this.form.submit()">
            <?php foreach ($PAGES as $slug => $label): ?>
                <option value="<?= e($slug) ?>" <?= $cat === $slug ? 'selected' : '' ?>><?= e($label) ?><?= !empty($byPage[$slug]) ? ' (' . count($byPage[$slug]) . ')' : '' ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn ghost" type="submit">Filter</button></noscript>
    </form>
    <form method="post" action="prices.php?cat=<?= e($cat) ?>">
        <?= csrf_field() ?>
        <button class="btn ghost" name="action" value="import_defaults">Import default prices</button>
    </form>
</div>
<p class="muted">Changes go live instantly on the service pages. Untick <em>Live</em> to fall back to the price written in the page's HTML.</p>

<?php if (empty($byPage[$cat])): ?>
    <section class="panel"><p class="muted">No plans for this category yet. Click <strong>Import default prices</strong> to load the current site prices.</p></section>
<?php endif; ?>

<?php foreach ($PAGES as $slug => $label):
    if (empty($byPage[$slug]) || $cat !== $slug) {
        continue;
    } ?>
    <section class="panel" id="p-<?= e($slug) ?>">
        <div class="panel-head">
            <h2><?= e($label) ?></h2>
            <a href="../<?= e($slug) ?>.html" target="_blank" rel="noopener">View page ↗</a>
        </div>
        <form method="post" action="prices.php?cat=<?= e($cat) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_page">
            <input type="hidden" name="page" value="<?= e($slug) ?>">
            <table class="price-table">
                <thead>
                <tr>
                    <th>Plan</th><th>Text before</th><th>Currency</th><th>Price</th><th>Note under price</th><th>Live</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($byPage[$slug] as $p): $id = (int) $p['id']; ?>
                    <tr>
                        <td>
                            <strong><?= e($p['plan_name']) ?></strong><br><small>Card #<?= (int) $p['position'] ?></small>
                            <input type="hidden" name="plan[<?= $id ?>][plan_name]" value="<?= e($p['plan_name']) ?>">
                        </td>
                        <td><input name="plan[<?= $id ?>][prefix]" value="<?= e($p['prefix']) ?>" maxlength="20" placeholder="From"></td>
                        <td><input name="plan[<?= $id ?>][currency]" value="<?= e($p['currency']) ?>" maxlength="12" placeholder="USD"></td>
                        <td><input name="plan[<?= $id ?>][amount]" value="<?= e($p['amount']) ?>" maxlength="40" required placeholder="500 or Custom"></td>
                        <td><input name="plan[<?= $id ?>][note]" value="<?= e($p['note']) ?>" maxlength="120" placeholder="starting from"></td>
                        <td><input type="checkbox" class="cb" name="plan[<?= $id ?>][is_active]" value="1" <?= $p['is_active'] ? 'checked' : '' ?>></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="actions" style="margin-top:14px">
                <button class="btn" type="submit">Save <?= e($label) ?></button>
            </div>
        </form>
    </section>
<?php endforeach; ?>

<p class="muted">Tip: leave <em>Currency</em> empty for cards like "Custom" so they keep the text-only styling.</p>
<?php admin_footer();
