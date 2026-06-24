<?php

// PHP session configuration.

return [
    // How long a session stays alive in seconds (7200 = 2 hours)
    'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),

    // The name of the session cookie
    'name' => 'emirates_session',

    // Cookie path — '/' means the cookie works across the whole site
    'path' => '/',

    // Set to true in production when running on HTTPS
    // In development on HTTP, this must be false or sessions won't work
    'secure' => filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN) === false,

    // HttpOnly means JS cannot read the session cookie (security hardening)
    'httponly' => true,

    // SameSite prevents the cookie from being sent in cross-site requests (CSRF protection)
    'samesite' => 'Lax',
];
