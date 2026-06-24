<?php

// Email sending defaults.
// The active_provider is overridden at runtime by whatever is saved
// in the credentials table via the Email Credentials settings page.

return [
    // Which provider to use if none is saved in the database yet
    // Options: 'resend' or 'smtp'
    'active_provider' => 'resend',

    // Default "From" name shown to email recipients
    'default_sender_name' => $_ENV['APP_NAME'] ?? 'Emirates',

    // Default "From" email address (must match your verified sending domain)
    'default_sender_email' => '',
];
