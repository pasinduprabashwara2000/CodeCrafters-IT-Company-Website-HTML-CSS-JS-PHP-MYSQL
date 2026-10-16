<?php
declare(strict_types=1);
// Shared helpers for the "example projects per price plan" feature.

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'plan-examples-lib.php') {
    http_response_code(403);
    exit;
}

function ensure_plan_examples_table(): void
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS plan_examples (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_slug    VARCHAR(60)  NOT NULL,
            position     TINYINT UNSIGNED NOT NULL,
            title        VARCHAR(160) NOT NULL,
            url          VARCHAR(255) NOT NULL DEFAULT \'\',
            description  VARCHAR(300) NOT NULL DEFAULT \'\',
            tech         VARCHAR(255) NOT NULL DEFAULT \'\',
            logo_path    VARCHAR(255) NOT NULL DEFAULT \'\',
            sort_order   INT          NOT NULL DEFAULT 0,
            is_published TINYINT(1)   NOT NULL DEFAULT 1,
            created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_page_pos (page_slug, position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    // Upgrade older installs: add the company logo column.
    if (!db()->query("SHOW COLUMNS FROM plan_examples LIKE 'logo_path'")->fetchAll()) {
        db()->exec("ALTER TABLE plan_examples ADD COLUMN logo_path VARCHAR(255) NOT NULL DEFAULT '' AFTER tech");
        $map = ['Sheiba Arcade' => 'sheiba', 'Pulsewave' => 'pulsewave', 'Lumina Systems' => 'lumina-systems',
                'Zenith Corp' => 'zenith-corp', 'Zenith Corp Portal' => 'zenith-corp', 'Cubework' => 'cubework', 'Nexora' => 'nexora'];
        $up = db()->prepare('UPDATE plan_examples SET logo_path = ? WHERE title = ?');
        foreach ($map as $title => $file) {
            $up->execute(['img/logos/' . $file . '.png', $title]);
        }
    }
}

/** Default example projects (shown after "Import sample examples" in the admin). */
function plan_example_defaults(): array
{
    return [
        ['web-design', 1, 'Sheiba Arcade', 'https://sheiba.lk/', 'Retail site for computers, gaming gear and accessories.', 'HTML5, CSS, PHP, MySQL', 'img/logos/sheiba.png'],
        ['web-design', 2, 'Pulsewave', 'https://www.pulsewave.co', 'Marketing site rebuild with technical SEO.', 'React, Next.js, Node.js', 'img/logos/pulsewave.png'],
        ['web-design', 3, 'Lumina Systems', 'https://www.luminasystems.ai', 'Self-serve knowledge base and help center.', 'Next.js, MongoDB, Node.js', 'img/logos/lumina-systems.png'],
        ['web-design', 3, 'Zenith Corp', 'https://www.zenithcorp.io', 'Client portal redesign with faster load times.', 'React, Node.js, PostgreSQL', 'img/logos/zenith-corp.png'],
        ['web-design', 4, 'Cubework', 'https://www.cubework.io', 'Coworking booking and payments platform.', 'Next.js, TypeScript, Stripe', 'img/logos/cubework.png'],
        ['web-design', 4, 'Nexora', 'https://www.nexora.io', 'Conversion-focused storefront with custom checkout.', 'React, Node.js, Stripe', 'img/logos/nexora.png'],
        ['web-design', 5, 'Zenith Corp Portal', 'https://www.zenithcorp.io', 'Custom web app built around the client\'s workflow.', 'React, Node.js, PostgreSQL', 'img/logos/zenith-corp.png'],
    ];
}

/** http(s) URL or relative path like img/logos/x.png. Returns null when invalid. */
function plan_example_clean_logo(string $u): ?string
{
    $u = trim($u);
    if ($u === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $u) && filter_var($u, FILTER_VALIDATE_URL)) {
        return $u;
    }
    if (preg_match('#^[A-Za-z0-9_./-]+$#', $u) && strpos($u, '..') === false && $u[0] !== '/') {
        return $u;
    }
    return null;
}

function plan_example_clean_url(string $u): ?string
{
    $u = trim($u);
    if ($u === '') {
        return '';
    }
    return (preg_match('#^https?://#i', $u) && filter_var($u, FILTER_VALIDATE_URL)) ? $u : null;
}
