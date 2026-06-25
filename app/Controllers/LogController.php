<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Repositories\LogRepository;

/**
 * LogController
 *
 * Phase 10 — Email Logs
 *
 * Routes served:
 *   GET  /logs              → index()  — paginated log list with tabs + filters
 *   GET  /logs/{id}         → show()   — single log detail partial (HTMX)
 *   POST /logs/clear        → clear()  — delete all logs of a type
 */
class LogController extends BaseController
{
    public function __construct(
        private readonly LogRepository $logs,
    ) {}

    // ─── GET /logs ────────────────────────────────────────────────────────────

    /**
     * Render the main logs page or return a table partial for HTMX tab/filter requests.
     *
     * Query params:
     *   type       (string) — 'sent' | 'error' | 'received'  (default: 'sent')
     *   page       (int)    — page number
     *   recipient  (string) — filter by recipient email
     *   subject    (string) — filter by subject
     *   status     (string) — filter by status (sent logs only)
     *   date_from  (string) — Y-m-d start date
     *   date_to    (string) — Y-m-d end date
     */
    public function index(Request $request): Response
    {
        $type    = in_array($request->get('type', 'sent'), ['sent', 'error', 'received'], true)
                   ? $request->get('type', 'sent')
                   : 'sent';
        $page    = max(1, (int) $request->get('page', 1));
        $filters = [
            'recipient' => trim($request->get('recipient', '')),
            'subject'   => trim($request->get('subject', '')),
            'status'    => trim($request->get('status', '')),
            'date_from' => trim($request->get('date_from', '')),
            'date_to'   => trim($request->get('date_to', '')),
        ];

        $paginated = $this->logs->paginate($page, $type, $filters);

        // HTMX tab / filter request: return only the table partial
        if ($request->isHtmx()) {
            return $this->partial("logs/_{$type}-table", [
                'rows'      => $paginated['rows'],
                'paginated' => $paginated,
                'type'      => $type,
                'filters'   => $filters,
            ]);
        }

        // Full page load
        return $this->view('logs/index', [
            'pageTitle' => 'Email Logs — ' . setting('site_name', 'Emirates'),
            'type'      => $type,
            'rows'      => $paginated['rows'],
            'paginated' => $paginated,
            'filters'   => $filters,
        ]);
    }

    // ─── GET /logs/{id} ───────────────────────────────────────────────────────

    /**
     * Return the detail panel partial for a single sent-email log entry.
     * Called via HTMX when a table row is clicked.
     */
    public function show(Request $request, int $id): Response
    {
        $row = $this->logs->findLog($id);

        if (!$row) {
            throw new NotFoundException("Log entry #{$id} not found.");
        }

        return $this->partial('logs/_log-detail', ['log' => $row]);
    }

    // ─── POST /logs/clear ─────────────────────────────────────────────────────

    /**
     * Delete all log records of the given type.
     *
     * POST body:
     *   type  (string) — 'sent' | 'error' | 'received'
     */
    public function clear(Request $request): Response
    {
        $type = $request->post('type', '');
        if (!in_array($type, ['sent', 'error', 'received'], true)) {
            return Response::html('')
                ->htmxTrigger('showToast', ['type' => 'error', 'message' => 'Invalid log type.']);
        }

        try {
            $deleted = $this->logs->clearAll($type);
        } catch (\InvalidArgumentException $e) {
            return Response::html('')
                ->htmxTrigger('showToast', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        $label = match ($type) {
            'sent'     => 'Sent',
            'error'    => 'Error',
            'received' => 'Received',
        };

        return Response::html('')
            ->htmxTrigger('showToast', [
                'type'    => 'success',
                'message' => "{$label} logs cleared ({$deleted} record" . ($deleted !== 1 ? 's' : '') . " deleted).",
            ])
            ->htmxTrigger('logsCleared', ['logType' => $type]);
    }
}