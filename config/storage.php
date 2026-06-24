<?php
// config/storage.php

return [
    /*
     * Absolute path to the storage/uploads/ directory.
     * Used by FileUploadService as the root for all uploaded files.
     */
    'uploads_path' => BASE_PATH . '/storage/uploads',

    /*
     * Maximum allowed size for logo uploads: 2MB in bytes.
     */
    'max_logo_size' => 2 * 1024 * 1024,

    /*
     * Maximum allowed size for template uploads: 5MB in bytes.
     */
    'max_template_size' => 5 * 1024 * 1024,

    /*
     * Allowed MIME types for logo uploads.
     * Validation uses fileinfo (server-side), not the client-supplied type.
     */
    'allowed_logo_mimes' => [
        'image/png',
        'image/jpeg',
        'image/svg+xml',
    ],

    /*
     * Allowed MIME types for template uploads.
     */
    'allowed_template_mimes' => [
        'text/html',
        'application/zip',
    ],
];
