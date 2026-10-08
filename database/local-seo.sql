-- Optional. The admin "Local SEO" page creates this table automatically on first save.
CREATE TABLE IF NOT EXISTS seo_settings (
    skey       VARCHAR(60) NOT NULL PRIMARY KEY,
    svalue     TEXT        NOT NULL,
    updated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
