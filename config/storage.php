<?php

// File storage configuration.

return [
    // Absolute path to the storage/uploads directory
    // BASE_PATH is defined in public/index.php as the project root
    'uploads_path' => (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__)) . '/storage/uploads',

    // Maximum allowed logo file size: 2MB in bytes
    'max_logo_size' => 2 * 1024 * 1024,

    // Maximum allowed template file size: 5MB in bytes
    'max_template_size' => 5 * 1024 * 1024,

    // Allowed MIME types for logo uploads
    'allowed_logo_mimes' => ['image/png', 'image/jpeg', 'image/svg+xml'],

    // Allowed MIME types for template uploads
    'allowed_template_mimes' => ['text/html', 'application/zip', 'application/x-zip-compressed'],
];
