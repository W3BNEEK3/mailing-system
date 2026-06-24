<?php
/**
 * resources/layouts/auth.php
 *
 * Minimal layout for unauthenticated pages (login only in MVP).
 * No sidebar, no top nav, no session checks.
 *
 * Variables available:
 *   $content   string  — The rendered view content (injected by view())
 *   $pageTitle string  — The <title> tag content
 */
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= e($pageTitle ?? 'Emirates') ?></title>

    <!-- Tailwind CSS (CDN — replace with compiled asset in production for performance) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /*
         * Custom properties for the Emirates brand.
         * These are overridden at runtime by the user's saved settings,
         * but we need sensible defaults for the login page where settings
         * aren't yet loaded.
         */
        :root {
            --color-primary:   #1d4ed8; /* Default blue — Emirates brand primary */
            --color-secondary: #0f172a; /* Default dark navy */
        }

        /* Prevent FOUC (flash of unstyled content) before Tailwind loads */
        body { opacity: 0; transition: opacity 0.15s ease; }
        body.ready { opacity: 1; }
    </style>
</head>
<body class="h-full bg-slate-50 flex items-center justify-center px-4 py-12">

    <!-- Centered card wrapper -->
    <div class="w-full max-w-sm">
        <?= $content ?>
    </div>

    <script>
        // Reveal body after styles load to prevent flash of unstyled content
        document.body.classList.add('ready');
    </script>
</body>
</html>
