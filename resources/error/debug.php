<?php
/**
 * resources/error/debug.php
 *
 * Developer debug view — shown ONLY when APP_DEBUG=true.
 * NEVER shown in production.
 *
 * Variables (set by ErrorHandler):
 *   Throwable $exception  The caught exception
 *   array     $sourceLines  Array of ['line' => n, 'code' => '...', 'active' => bool]
 *                            (5 lines before and after the error line)
 */

$exception   = $exception   ?? null;
$sourceLines = $sourceLines ?? [];

$class   = $exception ? get_class($exception)      : 'Unknown Error';
$message = $exception ? $exception->getMessage()   : 'No message';
$file    = $exception ? $exception->getFile()      : 'Unknown';
$line    = $exception ? $exception->getLine()      : 0;
$trace   = $exception ? $exception->getTraceAsString() : '';
$code    = $exception ? $exception->getCode()      : 0;

/**
 * Safe HTML escape — uses htmlspecialchars because e() may not be available
 * if the error occurred during bootstrap.
 */
$h = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $h($class) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body   { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', monospace; background: #0f172a; color: #e2e8f0; margin: 0; padding: 0; }
        header { background: #7f1d1d; padding: 1.5rem 2rem; border-bottom: 1px solid #991b1b; }
        .exc-class { font-size: 0.875rem; color: #fca5a5; font-family: monospace; margin-bottom: 0.5rem; }
        .exc-msg   { font-size: 1.5rem; font-weight: 700; color: #fff; line-height: 1.3; word-break: break-word; }
        .exc-loc   { font-size: 0.8125rem; color: #fca5a5; margin-top: 0.75rem; font-family: monospace; }
        .exc-code  { display: inline-block; background: #991b1b; border-radius: 4px; padding: 1px 6px; margin-left: 0.5rem; }
        main  { max-width: 1100px; margin: 0 auto; padding: 2rem; }
        section { margin-bottom: 2rem; }
        h2 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; margin-bottom: 0.75rem; font-weight: 600; }
        /* Source code block */
        .source { background: #1e293b; border-radius: 8px; overflow: hidden; font-family: monospace; font-size: 0.8125rem; line-height: 1.6; }
        .source-line { display: flex; padding: 0 1rem; }
        .source-line.active { background: #7f1d1d; }
        .source-line.active .src-code { color: #fca5a5; }
        .src-num  { width: 3rem; flex-shrink: 0; color: #475569; text-align: right; padding-right: 1rem; user-select: none; }
        .src-code { white-space: pre; overflow-x: auto; flex: 1; }
        /* Stack trace */
        .trace { background: #1e293b; border-radius: 8px; padding: 1.25rem 1.5rem; font-family: monospace; font-size: 0.75rem; color: #94a3b8; white-space: pre-wrap; word-break: break-word; line-height: 1.7; }
        /* Context panels */
        details { background: #1e293b; border-radius: 8px; margin-bottom: 0.75rem; overflow: hidden; }
        summary { padding: 0.875rem 1.25rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; color: #cbd5e1; list-style: none; display: flex; align-items: center; justify-content: space-between; }
        summary::after { content: '+'; font-size: 1rem; color: #475569; }
        details[open] summary::after { content: '−'; }
        .panel-body { padding: 1rem 1.25rem; border-top: 1px solid #334155; }
        pre { margin: 0; font-size: 0.75rem; color: #94a3b8; white-space: pre-wrap; word-break: break-word; line-height: 1.7; }
        .empty { color: #475569; font-size: 0.875rem; font-style: italic; padding: 0.5rem 0; }
    </style>
</head>
<body>

<header>
    <p class="exc-class">
        <?= $h($class) ?>
        <?php if ($code): ?>
            <span class="exc-code">Code <?= $h($code) ?></span>
        <?php endif; ?>
    </p>
    <h1 class="exc-msg"><?= $h($message) ?></h1>
    <p class="exc-loc">
        <?= $h($file) ?> : line <?= $h($line) ?>
    </p>
</header>

<main>

    <!-- ── SOURCE CODE SNIPPET ──────────────────────────────────────────── -->
    <?php if (!empty($sourceLines)): ?>
    <section>
        <h2>Source</h2>
        <div class="source">
            <?php foreach ($sourceLines as $srcLine): ?>
                <div class="source-line <?= $srcLine['active'] ? 'active' : '' ?>">
                    <span class="src-num"><?= (int)$srcLine['line'] ?></span>
                    <span class="src-code"><?= $h($srcLine['code']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── STACK TRACE ──────────────────────────────────────────────────── -->
    <section>
        <h2>Stack Trace</h2>
        <div class="trace"><?= $h($trace) ?></div>
    </section>

    <!-- ── CONTEXT PANELS ───────────────────────────────────────────────── -->
    <section>
        <h2>Request Context</h2>

        <?php
        $panels = [
            '$_SERVER' => $_SERVER ?? [],
            '$_POST'   => $_POST   ?? [],
            '$_GET'    => $_GET    ?? [],
            '$_SESSION'=> $_SESSION ?? [],
        ];

        foreach ($panels as $label => $data):
            // Redact sensitive keys
            $redactKeys = ['password', 'password_hash', 'APP_KEY', 'DB_PASS', '_csrf'];
            $safe = [];
            foreach ($data as $k => $v) {
                $safe[$k] = in_array($k, $redactKeys, true) ? '[REDACTED]' : $v;
            }
        ?>
            <details>
                <summary><?= $h($label) ?> (<?= count($data) ?> keys)</summary>
                <div class="panel-body">
                    <?php if (empty($safe)): ?>
                        <p class="empty">Empty</p>
                    <?php else: ?>
                        <pre><?= $h(print_r($safe, true)) ?></pre>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </section>

</main>
</body>
</html>