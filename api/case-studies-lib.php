<?php
declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'case-studies-lib.php') {
    http_response_code(403);
    exit;
}

function ensure_case_studies_table(): void
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS case_studies (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            label        VARCHAR(120) NOT NULL,
            title        VARCHAR(190) NOT NULL,
            description  TEXT         NOT NULL,
            tech         VARCHAR(255) NOT NULL DEFAULT \'\',
            metrics      TEXT         NOT NULL,
            quote        TEXT         NOT NULL,
            quote_author VARCHAR(160) NOT NULL DEFAULT \'\',
            image_src    VARCHAR(255) NOT NULL DEFAULT \'\',
            sort_order   INT          NOT NULL DEFAULT 0,
            is_published TINYINT(1)   NOT NULL DEFAULT 1,
            created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    // Upgrade older installs: the emoji "visual_icon" column became an image path/URL "image_src".
    $cols = db()->query("SHOW COLUMNS FROM case_studies LIKE 'visual_icon'")->fetchAll();
    if ($cols) {
        db()->exec("ALTER TABLE case_studies CHANGE visual_icon image_src VARCHAR(255) NOT NULL DEFAULT ''");
        db()->exec("UPDATE case_studies SET image_src = ''");
    }
}

/** Accepts an http(s) URL or a relative path like img/case-studies/x.png. Returns null when invalid. */
function case_study_clean_image(string $u): ?string
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
