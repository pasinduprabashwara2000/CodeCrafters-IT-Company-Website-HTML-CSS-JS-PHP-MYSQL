<?php
// Edit these values for your environment (XAMPP defaults shown).
return [
    'db_host'    => 'localhost',
    'db_name'    => 'codecrafters',
    'db_user'    => 'root',
    'db_pass'    => '',

    // One-time key required by admin/setup.sql.php. Change before running setup.sql.
    'setup_key'  => 'change-me-before-running-setup.sql',

    // Contact form: optional email notification for each new message ('' disables).
    'notify_email' => '',
    'from_email'   => 'no-reply@codecrafters.gt.tc',

    // Max contact submissions per IP per hour.
    'contact_rate_limit' => 5,
];
