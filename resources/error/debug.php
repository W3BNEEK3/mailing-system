<?php
/**
 * resources/error/debug.php
 *
 * Developer debug error page. Only shown when APP_DEBUG=true.
 * Variables available (injected by ErrorHandler::renderDebugPage()):
 *   $exceptionClass — string: the exception class name
 *   $message        — string: HTML-safe error message
 *   $file           — string: HTML-safe file path
 *   $line           — int:    line number
 *   $trace          — string: HTML-safe stack trace
 *   $sourceLines    — array:  ['line_number' => 'code_line', ...]
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug — <?= $exceptionClass ?? 'Error' ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; font-size: 14px; }
        .header { background: #dc2626; padding: 20px 32px; }
        .header h1 { font-size: 1.1rem; font-weight: 700; color: #fff; font-family: monospace; }
        .header p  { font-size: 1.5rem; font-weight: 600; color: #fecaca; margin-top: 6px; }
        .meta { padding: 16px 32px; background: #1e293b; border-bottom: 1px solid #334155; }
        .meta span { background: #334155; padding: 4px 10px; border-radius: 4px; font-family: monospace; font-size: 0.85rem; color: #94a3b8; }
        .meta span b { color: #f8fafc; }
        .section { padding: 24px 32px; }
        .section h2 { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 12px; }
        .source { background: #1e293b; border-radius: 8px; overflow: hidden; border: 1px solid #334155; }
        .source table { width: 100%; border-collapse: collapse; font-family: 'Fira Code', 'Courier New', monospace; font-size: 0.8rem; }
        .source td { padding: 2px 16px; white-space: pre; }
        .source .line-num { color: #475569; text-align: right; user-select: none; width: 48px; border-right: 1px solid #334155; }
        .source .error-line { background: #450a0a; }
        .source .error-line .line-num { background: #dc2626; color: #fff; }
        .trace { background: #1e293b; border-radius: 8px; padding: 20px; font-family: monospace; font-size: 0.78rem; color: #94a3b8; white-space: pre-wrap; word-break: break-all; border: 1px solid #334155; overflow-x: auto; }
        .panel { background: #1e293b; border-radius: 8px; border: 1px solid #334155; overflow: hidden; margin-bottom: 16px; }
        .panel summary { padding: 12px 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: #cbd5e1; list-style: none; }
        .panel summary:hover { background: #334155; }
        .panel pre { padding: 16px 20px; font-size: 0.78rem; color: #94a3b8; overflow-x: auto; white-space: pre-wrap; border-top: 1px solid #334155; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= htmlspecialchars($exceptionClass ?? 'RuntimeException') ?></h1>
        <p><?= $message ?></p>
    </div>

    <div class="meta">
        <span><b>File:</b> <?= $file ?></span>
         
        <span><b>Line:</b> <?= $line ?></span>
    </div>

    <?php if (!empty($sourceLines)): ?>
    <div class="section">
        <h2>Source Code</h2>
        <div class="source">
            <table>
                <?php foreach ($sourceLines as $lineNum => $codeLine): ?>
                <tr class="<?= $lineNum === $line ? 'error-line' : '' ?>">
                    <td class="line-num"><?= $lineNum ?></td>
                    <td><?= htmlspecialchars($codeLine) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="section">
        <h2>Stack Trace</h2>
        <div class="trace"><?= $trace ?></div>
    </div>

    <div class="section">
        <h2>Request Context</h2>

        <details class="panel" open>
            <summary>$_SERVER</summary>
            <pre><?= htmlspecialchars(print_r($_SERVER, true)) ?></pre>
        </details>

        <details class="panel">
            <summary>$_POST</summary>
            <pre><?= htmlspecialchars(print_r($_POST, true)) ?></pre>
        </details>

        <details class="panel">
            <summary>$_GET</summary>
            <pre><?= htmlspecialchars(print_r($_GET, true)) ?></pre>
        </details>

        <details class="panel">
            <summary>$_SESSION</summary>
            <pre><?= htmlspecialchars(print_r($_SESSION ?? [], true)) ?></pre>
        </details>
    </div>
</body>
</html>
