CREATE DATABASE IF NOT EXISTS codecrafters CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE codecrafters;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(60)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
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

-- ===== Default prices (same values as on the site) =====
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

-- ===== Sample data =====

-- Sample projects (mirror the built-in cards on work.html)
INSERT INTO projects (client, domain, url, tag, title, description, tech, thumb_style, thumb_icon, logo_path, sort_order, is_published) VALUES
('Sheiba Arcade', 'sheiba.lk', 'https://sheiba.lk/', 'E-Commerce', 'Technology & Gaming Retail Site',
 'A retail website for Sheiba Arcade showcasing computers, gaming gear and tech accessories with easy browsing and inquiry.',
 'HTML5, CSS, JavaScript', 'a', '', 'img/sheiba.png', 1, 1),
('Cubework', 'cubework.io', 'https://www.cubework.io', 'E-Commerce', 'Coworking Booking Platform',
 'A booking and payments platform for a coworking network, with an admin dashboard for every location.',
 'Next.js, TypeScript, Stripe', 'b', '🛒', '', 2, 1),
('Pulsewave', 'pulsewave.co', 'https://www.pulsewave.co', 'SEO & Web', 'Marketing Site Rebuild',
 'A full redesign and technical SEO overhaul that lifted organic traffic within the first quarter.',
 'HTML5, CSS, JavaScript', 'c', '↗', '', 3, 1),
('Zenith Corp', 'zenithcorp.io', 'https://www.zenithcorp.io', 'Web Platform', 'Client Portal Redesign',
 'A refreshed client portal with faster load times, ongoing monitoring and ongoing maintenance support.',
 'React, Node.js, PostgreSQL', 'd', '↻', '', 4, 1),
('Lumina Systems', 'luminasystems.ai', 'https://www.luminasystems.ai', 'Web Platform', 'Support Knowledge Base',
 'A self-serve knowledge base and help center, kept fast and up to date with a managed maintenance plan.',
 'Next.js, MongoDB, Node.js', 'e', '▤', '', 5, 1),
('Nexora', 'nexora.io', 'https://www.nexora.io', 'E-Commerce', 'E-Commerce Storefront',
 'A fast, conversion-focused storefront with a custom checkout, built to scale with the product catalog.',
 'React, Node.js, Stripe', 'f', '🛒', '', 6, 1),
('Orbitals', 'orbitals.example', 'https://orbitals.example', 'Mobile App', 'Fitness Tracking App (draft)',
 'Cross-platform fitness tracker with workout plans and progress charts. Unpublished sample to test drafts.',
 'Flutter, Dart, Firebase', 'a', '◎', '', 7, 0);

-- Sample contact messages
INSERT INTO messages (name, email, subject, message, ip, user_agent, is_read, created_at) VALUES
('Nimal Perera', 'nimal@example.com', 'E-commerce website quote',
 'Hi, we run a small clothing store and want an online shop with card payments. Can you share a quote and timeline?',
 '203.94.10.21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 0, NOW() - INTERVAL 2 HOUR),
('Sarah Fernando', 'sarah@example.org', 'Mobile app for our clinic',
 'We need a booking app for iOS and Android. Do you build with Flutter? Happy to jump on a call this week.',
 '112.134.55.8', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', 0, NOW() - INTERVAL 1 DAY),
('David Silva', 'david@example.net', 'SEO audit',
 'Our traffic dropped after a redesign. Looking for an SEO audit and fix plan.',
 '175.157.3.90', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 1, NOW() - INTERVAL 3 DAY),
('Ayesha Khan', 'ayesha@example.com', '',
 'Just checking if you offer monthly website maintenance packages.',
 '124.43.17.6', 'Mozilla/5.0 (Linux; Android 14)', 1, NOW() - INTERVAL 6 DAY);

-- Optional sample admin (skip this and use admin/setup.php instead).
-- Username: admin   Password: ChangeMe!2026   -> CHANGE IT after first login.
-- If you insert this, admin/setup.php is locked because a user already exists.
-- INSERT INTO users (username, password_hash) VALUES ('admin', '$2y$10$658hqEjU8wMV5lmXUSJOyuZGGUEE.B2k61l1PYkBnugHgSFBwOJKC');