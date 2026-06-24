<?php
return [
    // The name displayed in the browser tab and emails
    'name' => $_ENV['APP_NAME'] ?? 'Emirates',

    // The full base URL of the application (no trailing slash)
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),

    // When true, shows the detailed debug error page instead of a generic 500
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),

    // The 32-byte base64-encoded encryption key used for AES-256 encryption
    'key' => $_ENV['APP_KEY'] ?? '',

    // PHP timezone identifier — used for date display and log timestamps
    'timezone' => $_ENV['TIMEZONE'] ?? 'UTC',
];
