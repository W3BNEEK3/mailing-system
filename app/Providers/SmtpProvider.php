<?php

declare(strict_types=1);

namespace App\Providers;

use App\DTOs\EmailPayload;
use App\DTOs\SendResult;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\EmailProviderInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * SmtpProvider
 *
 * Sends email via any SMTP server using PHPMailer.
 *
 * Config array keys (passed from the decrypted credential config):
 *   host        string  SMTP server hostname: 'smtp.gmail.com'
 *   port        int     SMTP port: 465 (SSL), 587 (TLS/STARTTLS)
 *   encryption  string  '', 'ssl', 'tls', 'starttls'
 *   username    string  SMTP login username (usually the sending address)
 *   password    string  SMTP password or app-specific password
 *   from_name   string  Sender display name
 *   from_email  string  Sender email address
 *
 * testConnection():
 *   Uses PHPMailer's SmtpConnect() to establish a connection and authenticate
 *   without sending an email. Closes the connection cleanly afterwards.
 *
 * Usage:
 *   $config   = $credential->decryptedConfig();
 *   $provider = new SmtpProvider($config);
 *   $result   = $provider->send($payload);
 */
class SmtpProvider implements EmailProviderInterface
{
    public function __construct(
        private readonly array $config
    ) {}

    // ─── EmailProviderInterface ───────────────────────────────────────────────

    /**
     * Send an email via SMTP using PHPMailer.
     *
     * @throws ProviderException  On SMTP authentication or send failure
     */
    public function send(EmailPayload $payload): SendResult
    {
        $mailer = $this->buildMailer();

        try {
            // ── Recipients ────────────────────────────────────────────────
            foreach ($payload->recipients as $address) {
                $mailer->addAddress($address);
            }

            if (!empty($payload->cc)) {
                foreach ($payload->cc as $cc) {
                    $mailer->addCC($cc);
                }
            }

            if (!empty($payload->bcc)) {
                foreach ($payload->bcc as $bcc) {
                    $mailer->addBCC($bcc);
                }
            }

            // ── Reply-To ──────────────────────────────────────────────────
            $replyTo = $payload->replyTo ?? $payload->senderEmail;
            $mailer->addReplyTo($replyTo, $payload->senderName);

            // ── Content ───────────────────────────────────────────────────
            $mailer->Subject  = $payload->subject;
            $mailer->isHTML(true);
            $mailer->Body     = $payload->html;
            // Plain text fallback (strip HTML tags)
            $mailer->AltBody  = strip_tags((string) preg_replace('/<br\s*\/?>/i', "\n", $payload->html));

            // ── Send ──────────────────────────────────────────────────────
            $mailer->send();

            // PHPMailer does not provide a message ID in the same way Resend does.
            // We generate a local ID for tracking purposes.
            $messageId = 'smtp_' . uniqid('', true);

            return new SendResult(
                messageId:        $messageId,
                status:           'sent',
                providerResponse: 'Sent via SMTP (' . ($this->config['host'] ?? 'unknown host') . ')',
            );

        } catch (PHPMailerException $e) {
            throw new ProviderException(
                'SMTP send failed: ' . $e->getMessage(),
                'smtp'
            );
        }
    }

    /**
     * Test the SMTP connection without sending any email.
     *
     * Calls PHPMailer's SmtpConnect() to:
     *   1. Connect to the SMTP server
     *   2. Authenticate with username/password
     *   3. Immediately close the connection
     *
     * @return bool  true if connection and authentication succeed
     */
    public function testConnection(): bool
    {
        $mailer = $this->buildMailer();

        try {
            // SmtpConnect() establishes a connection and authenticates.
            // If it returns true, credentials are valid.
            $connected = $mailer->SmtpConnect();
            $mailer->SmtpClose();
            return $connected;
        } catch (\Throwable) {
            return false;
        }
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /**
     * Build and configure a PHPMailer instance from the stored config.
     *
     * @return PHPMailer  Configured PHPMailer instance (not yet sent)
     *
     * @throws ProviderException  If the config is missing required keys
     */
    private function buildMailer(): PHPMailer
    {
        // Validate required config keys
        $required = ['host', 'port', 'username', 'password'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new ProviderException(
                    "SMTP configuration is missing the '{$key}' field.",
                    'smtp'
                );
            }
        }

        // PHPMailer constructor: false = do not throw exceptions (we handle them ourselves)
        $mail = new PHPMailer(true); // true = throw exceptions on error

        // ── SMTP settings ────────────────────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = (string) $this->config['host'];
        $mail->Port       = (int)    ($this->config['port'] ?? 587);
        $mail->SMTPAuth   = true;
        $mail->Username   = (string) $this->config['username'];
        $mail->Password   = (string) $this->config['password'];

        // Map encryption setting to PHPMailer constants
        $mail->SMTPSecure = match (strtolower((string) ($this->config['encryption'] ?? 'tls'))) {
            'ssl'            => PHPMailer::ENCRYPTION_SMTPS,
            'tls', 'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
            default           => '', // No encryption
        };

        // ── Sender ───────────────────────────────────────────────────────────
        $fromEmail = (string) ($this->config['from_email'] ?? $this->config['username'] ?? '');
        $fromName  = (string) ($this->config['from_name']  ?? '');
        $mail->setFrom($fromEmail, $fromName);

        // ── Debug mode ───────────────────────────────────────────────────────
        // Only enable SMTP debug output in development (logs to the error log, not to screen)
        if (config('app.debug')) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = 'error_log';
        } else {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
        }

        // ── Timeout ──────────────────────────────────────────────────────────
        $mail->Timeout = 20; // seconds

        return $mail;
    }
}
