<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * EmailErrorLog
 *
 * Records failed email send attempts with provider error details.
 * Shown in the "Errors" tab of the Email Logs page.
 *
 * Usage:
 *   $errors = EmailErrorLog::where(['provider' => 'resend'], 'created_at', 'DESC');
 */
class EmailErrorLog extends Model
{
    protected static string $table = 'email_error_logs';

    protected array $fillable = [
        'log_id',
        'error_code',
        'error_message',
        'recipients_json',
        'provider',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Decode the recipients_json column into a PHP array.
     */
    public function recipientsArray(): array
    {
        if (empty($this->recipients_json)) {
            return [];
        }

        $decoded = json_decode((string)$this->recipients_json, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get a short summary of the error for display in the table.
     * Truncates long error messages.
     */
    public function shortMessage(): string
    {
        $msg = (string)$this->error_message;
        return mb_strlen($msg) > 100 ? mb_substr($msg, 0, 97) . '...' : $msg;
    }
}
