<?php
/**
 * resources/layouts/error.php
 *
 * Minimal layout for error pages (404, 403, 500).
 * No sidebar, no session dependency, no external CSS files that might fail.
 * Bootstrap Icons CDN is the only external dependency.
 *
 * Variables:
 *   $content   string — The rendered error view content
 *   $pageTitle string — Browser <title> content
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'Error' ?></title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 3rem;
            text-decoration: none;
            color: inherit;
        }
        .brand-icon {
            width: 2rem; height: 2rem;
            background: #1d4ed8;
            border-radius: 0.5rem;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-name {
            font-weight: 700;
            font-size: 1rem;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <a href="/" class="brand">
        <div class="brand-icon">
            <i class="bi bi-send-fill" style="color:#fff;font-size:0.875rem;"></i>
        </div>
        <span class="brand-name">Emirates</span>
    </a>
    <?= $content ?>
</body>
</html>