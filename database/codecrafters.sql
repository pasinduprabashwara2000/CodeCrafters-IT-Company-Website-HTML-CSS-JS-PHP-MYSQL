SET NAMES utf8mb4;

-- ===================== TABLES =====================

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    subject    VARCHAR(190) NOT NULL DEFAULT '',
    message    TEXT         NOT NULL,
    ip         VARCHAR(45)  NOT NULL DEFAULT '',
    user_agent VARCHAR(255) NOT NULL DEFAULT '',
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read (is_read),
    INDEX idx_ip_time (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client       VARCHAR(120) NOT NULL,
    domain       VARCHAR(120) NOT NULL DEFAULT '',
    url          VARCHAR(255) NOT NULL DEFAULT '',
    tag          VARCHAR(60)  NOT NULL DEFAULT '',
    title        VARCHAR(190) NOT NULL,
    description  TEXT         NOT NULL,
    tech         VARCHAR(255) NOT NULL DEFAULT '',
    thumb_style  CHAR(1)      NOT NULL DEFAULT 'a',
    thumb_icon   VARCHAR(8)   NOT NULL DEFAULT '',
    logo_path    VARCHAR(255) NOT NULL DEFAULT '',
    sort_order   INT          NOT NULL DEFAULT 0,
    is_published TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip           VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS seo_settings (
    skey       VARCHAR(60) NOT NULL PRIMARY KEY,
    svalue     TEXT        NOT NULL,
    updated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pricing_plans (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_slug   VARCHAR(60)  NOT NULL,
    position    TINYINT UNSIGNED NOT NULL,
    plan_name   VARCHAR(120) NOT NULL,
    currency    VARCHAR(12)  NOT NULL DEFAULT 'USD',
    prefix      VARCHAR(20)  NOT NULL DEFAULT '',
    amount      VARCHAR(40)  NOT NULL,
    note        VARCHAR(120) NOT NULL DEFAULT '',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_page_pos (page_slug, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plan_examples (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_slug    VARCHAR(60)  NOT NULL,
    position     TINYINT UNSIGNED NOT NULL,
    title        VARCHAR(160) NOT NULL,
    url          VARCHAR(255) NOT NULL DEFAULT '',
    description  VARCHAR(300) NOT NULL DEFAULT '',
    tech         VARCHAR(255) NOT NULL DEFAULT '',
    logo_path    VARCHAR(255) NOT NULL DEFAULT '',
    sort_order   INT          NOT NULL DEFAULT 0,
    is_published TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_pos (page_slug, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS case_studies (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label        VARCHAR(120) NOT NULL,
    title        VARCHAR(190) NOT NULL,
    description  TEXT         NOT NULL,
    tech         VARCHAR(255) NOT NULL DEFAULT '',
    metrics      TEXT         NOT NULL,
    quote        TEXT         NOT NULL,
    quote_author VARCHAR(160) NOT NULL DEFAULT '',
    image_src    VARCHAR(255) NOT NULL DEFAULT '',
    sort_order   INT          NOT NULL DEFAULT 0,
    is_published TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================== DEFAULT DATA =====================

-- Default prices (same values as on the site; INSERT IGNORE skips rows that already exist)
INSERT IGNORE INTO pricing_plans (page_slug, position, plan_name, currency, prefix, amount, note, is_active) VALUES
('education', 1, 'Course Platform', 'USD', '', '750', 'starting from', 1),
('education', 2, 'School Management System', 'USD', '', '1,800', 'starting from', 1),
('education', 3, 'Custom Learning Platform', '', '', 'Custom', 'Let''s discuss your requirements', 1),
('finance', 1, 'Fintech MVP', 'USD', '', '1,200', 'starting from', 1),
('finance', 2, 'Digital Banking Platform', 'USD', '', '3,200', 'starting from', 1),
('finance', 3, 'Custom Financial Platform', '', '', 'Custom', 'Let''s discuss your requirements', 1),
('logistics', 1, 'Delivery Tracking App', 'USD', '', '1,200', 'starting from', 1),
('logistics', 2, 'Fleet & Route Platform', 'USD', '', '2,800', 'starting from', 1),
('logistics', 3, 'Custom Supply Chain Platform', '', '', 'Custom', 'Let''s discuss your requirements', 1),
('retail', 1, 'Online Storefront', 'USD', '', '400', 'starting from', 1),
('retail', 2, 'Omnichannel Retail Platform', 'USD', '', '850', 'starting from', 1),
('retail', 3, 'Custom Retail Platform', '', '', 'Custom', 'Let''s discuss your requirements', 1),
('software-development', 1, 'Starter Desktop App', 'USD', '', '250', 'starting from', 1),
('software-development', 2, 'Business Desktop App', 'USD', '', '500', 'starting from', 1),
('software-development', 3, 'Advanced Business System', 'USD', '', '1,000', 'starting from', 1),
('web-design', 1, 'Starter Website', 'USD', '', '120', 'starting from', 1),
('web-design', 2, 'Business Website', 'USD', '', '500', 'starting from', 1),
('web-design', 3, 'Business Pro', 'USD', '', '750', 'starting from', 1),
('web-design', 4, 'E-Commerce', 'USD', '', '1,200', 'starting from', 1),
('web-design', 5, 'Custom Web App', '', '', 'Custom', 'Let''s discuss your requirements', 1),
('mobile-app', 1, 'Single-Platform App', 'USD', '', '1,500', 'starting from', 1),
('mobile-app', 2, 'Cross-Platform App', 'USD', '', '2,800', 'starting from', 1),
('mobile-app', 3, 'Enterprise App', 'USD', '', '8,500+', 'let''s discuss your requirements', 1),
('seo', 1, 'SEO Foundation', 'USD', '', '70', '/ month', 1),
('seo', 2, 'SEO Growth', 'USD', '', '150', '/ month', 1),
('seo', 3, 'SEO Strategy', 'USD', 'From', '300', '/ month', 1),
('website-maintenance', 1, 'Essential Maintenance', 'USD', '', '20', '/ month', 1),
('website-maintenance', 2, 'Professional Maintenance', 'USD', '', '50', '/ month', 1),
('website-maintenance', 3, 'Advanced Maintenance', 'USD', '', '100', '/ month', 1);

-- Example projects shown under each price card (managed in Admin > Plan Examples)
INSERT INTO plan_examples (page_slug, position, title, url, description, tech, logo_path, sort_order)
SELECT * FROM (
                  SELECT 'web-design' AS page_slug, 1 AS position, 'Sheiba Arcade' AS title, 'https://sheiba.lk/' AS url, 'Retail site for computers, gaming gear and accessories.' AS description, 'HTML5, CSS, PHP, MySQL' AS tech, 'img/logos/sheiba.png' AS logo_path, 0 AS sort_order
                  UNION ALL SELECT 'web-design', 2, 'Pulsewave', 'https://www.pulsewave.co', 'Marketing site rebuild with technical SEO.', 'React, Next.js, Node.js', 'img/logos/pulsewave.png', 1
                  UNION ALL SELECT 'web-design', 3, 'Lumina Systems', 'https://www.luminasystems.ai', 'Self-serve knowledge base and help center.', 'Next.js, MongoDB, Node.js', 'img/logos/lumina-systems.png', 2
                  UNION ALL SELECT 'web-design', 3, 'Zenith Corp', 'https://www.zenithcorp.io', 'Client portal redesign with faster load times.', 'React, Node.js, PostgreSQL', 'img/logos/zenith-corp.png', 3
                  UNION ALL SELECT 'web-design', 4, 'Cubework', 'https://www.cubework.io', 'Coworking booking and payments platform.', 'Next.js, TypeScript, Stripe', 'img/logos/cubework.png', 4
                  UNION ALL SELECT 'web-design', 4, 'Nexora', 'https://www.nexora.io', 'Conversion-focused storefront with custom checkout.', 'React, Node.js, Stripe', 'img/logos/nexora.png', 5
                  UNION ALL SELECT 'web-design', 5, 'Zenith Corp Portal', 'https://www.zenithcorp.io', 'Custom web app built around the client''s workflow.', 'React, Node.js, PostgreSQL', 'img/logos/zenith-corp.png', 6
              ) AS seed
WHERE NOT EXISTS (SELECT 1 FROM plan_examples);

-- Projects shown on the Our Work page (managed in Admin > Projects)
INSERT INTO projects (client, domain, url, tag, title, description, tech, thumb_style, thumb_icon, logo_path, sort_order, is_published)
SELECT * FROM (
                  SELECT 'Sheiba Arcade' AS client, 'sheiba.lk' AS domain, 'https://sheiba.lk/' AS url, 'E-Commerce' AS tag, 'Technology & Gaming Retail Site' AS title, 'A retail website for Sheiba Arcade showcasing computers, gaming gear and tech accessories with easy browsing and inquiry.' AS description, 'HTML5, CSS, JavaScript' AS tech, 'a' AS thumb_style, '' AS thumb_icon, 'img/sheiba.png' AS logo_path, 1 AS sort_order, 1 AS is_published
                  UNION ALL SELECT 'Cubework', 'cubework.io', 'https://www.cubework.io', 'E-Commerce', 'Coworking Booking Platform', 'A booking and payments platform for a coworking network, with an admin dashboard for every location.', 'Next.js, TypeScript, Stripe', 'b', '🛒', '', 2, 1
                  UNION ALL SELECT 'Pulsewave', 'pulsewave.co', 'https://www.pulsewave.co', 'SEO & Web', 'Marketing Site Rebuild', 'A full redesign and technical SEO overhaul that lifted organic traffic within the first quarter.', 'HTML5, CSS, JavaScript', 'c', '↗', '', 3, 1
                  UNION ALL SELECT 'Zenith Corp', 'zenithcorp.io', 'https://www.zenithcorp.io', 'Web Platform', 'Client Portal Redesign', 'A refreshed client portal with faster load times, ongoing monitoring and ongoing maintenance support.', 'React, Node.js, PostgreSQL', 'd', '↻', '', 4, 1
                  UNION ALL SELECT 'Lumina Systems', 'luminasystems.ai', 'https://www.luminasystems.ai', 'Web Platform', 'Support Knowledge Base', 'A self-serve knowledge base and help center, kept fast and up to date with a managed maintenance plan.', 'Next.js, MongoDB, Node.js', 'e', '▤', '', 5, 1
                  UNION ALL SELECT 'Nexora', 'nexora.io', 'https://www.nexora.io', 'E-Commerce', 'E-Commerce Storefront', 'A fast, conversion-focused storefront with a custom checkout, built to scale with the product catalog.', 'React, Node.js, Stripe', 'f', '🛒', '', 6, 1
              ) AS seed
WHERE NOT EXISTS (SELECT 1 FROM projects);

-- Case studies shown on the Our Work page (managed in Admin > Case Studies)
INSERT INTO case_studies (label, title, description, tech, metrics, quote, quote_author, image_src, sort_order, is_published)
SELECT 'E-Commerce · Sheiba Arcade', 'A Storefront for a Local Computer Shop',
       'Sheiba Arcade, a computer shop in Wadduwa selling new and used computers and accessories, needed a website to show its stock and let customers get in touch online. We built a fast PHP and MySQL site that makes browsing products easy and turns visits into inquiries.',
       'HTML5, CSS, PHP, MySQL', 'New & used | computers and accessories\nWadduwa | Kalutara District, Sri Lanka', '', '', 'img/sheiba.png', 0, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM case_studies WHERE title = 'A Storefront for a Local Computer Shop');

-- ===================== ADMIN LOGIN =====================
-- Username: admin   Password: ChangeMe!2026   -> CHANGE IT after first login.
INSERT INTO users (username, password_hash)
VALUES ('admin', '$2y$10$658hqEjU8wMV5lmXUSJOyuZGGUEE.B2k61l1PYkBnugHgSFBwOJKC');