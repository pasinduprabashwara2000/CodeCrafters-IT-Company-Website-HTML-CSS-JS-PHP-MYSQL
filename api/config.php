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
    'from_email'   => 'no-reply@codecrafters.com.lk',

    // Max contact submissions per IP per hour.
    'contact_rate_limit' => 5,

    // AI chatbot (Anthropic Claude API). Get a key at https://console.anthropic.com
    // Keep this key private: never put it in JavaScript or commit it to a public repo.
    // Alternative: leave '' and set the ANTHROPIC_API_KEY environment variable.
    'anthropic_api_key' => '',

    // Chatbot limits: messages per IP per hour, and total messages per day (0 = no daily cap).
    'ai_rate_limit'  => 30,
    'ai_daily_limit' => 500,
];
