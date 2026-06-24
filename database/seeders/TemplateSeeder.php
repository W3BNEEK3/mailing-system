<?php

declare(strict_types=1);

/**
 * database/seeders/TemplateSeeder.php
 *
 * Seeds the three built-in email templates.
 *
 * All three templates:
 *   - Use {{LOGO_URL}}, {{PRIMARY_COLOR}}, {{SECONDARY_COLOR}} so
 *     supports_logo = 1 and supports_colors = 1.
 *   - Are marked is_built_in = 1 (cannot be deleted via the UI).
 *   - Use table-based layout for maximum email client compatibility
 *     (Outlook 2007–2021, Gmail, Apple Mail).
 *   - Inline CSS only — no <style> blocks (many email clients strip them).
 *   - Max width 600px, single-column.
 *
 * Uses INSERT IGNORE so re-running the seeder is safe.
 * Duplicate detection is on (name, is_built_in) — if a built-in template
 * with the same name already exists, the row is skipped.
 */
class TemplateSeeder
{
    public function run(\PDO $pdo): void
    {
        $templates = [
            [
                'name'            => 'Newsletter',
                'category'        => 'newsletter',
                'supports_logo'   => 1,
                'supports_colors' => 1,
                'html_content'    => $this->newsletter(),
            ],
            [
                'name'            => 'Transactional',
                'category'        => 'transactional',
                'supports_logo'   => 1,
                'supports_colors' => 1,
                'html_content'    => $this->transactional(),
            ],
            [
                'name'            => 'Promotional',
                'category'        => 'promotional',
                'supports_logo'   => 1,
                'supports_colors' => 1,
                'html_content'    => $this->promotional(),
            ],
        ];

        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO email_templates
             (name, category, html_content, is_built_in, supports_logo, supports_colors, created_at, updated_at)
             VALUES (?, ?, ?, 1, ?, ?, NOW(), NOW())'
        );

        foreach ($templates as $t) {
            $stmt->execute([
                $t['name'],
                $t['category'],
                $t['html_content'],
                $t['supports_logo'],
                $t['supports_colors'],
            ]);
        }
    }

    // ─── Template HTML ────────────────────────────────────────────────────────

    private function newsletter(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Newsletter</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9;">
    <tr>
      <td align="center" style="padding:32px 16px;">

        <!-- Outer card -->
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">

          <!-- Header: logo + brand colour bar -->
          <tr>
            <td style="background-color:{{PRIMARY_COLOR}};padding:24px 32px;text-align:center;">
              <img src="{{LOGO_URL}}" alt="Logo" width="140" style="max-width:140px;height:auto;display:inline-block;">
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:40px 32px 32px;">
              <h1 style="margin:0 0 12px;font-size:24px;line-height:1.3;color:#1e293b;font-weight:700;">
                Your Monthly Update
              </h1>
              <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#475569;">
                Hello, here is a summary of what happened this month. We've been working hard
                to bring you the best experience possible — read on for the highlights.
              </p>

              <!-- Divider -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
                <tr>
                  <td style="border-top:1px solid #e2e8f0;"></td>
                </tr>
              </table>

              <h2 style="margin:0 0 10px;font-size:18px;color:#1e293b;font-weight:600;">
                Feature Highlight
              </h2>
              <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569;">
                This month we launched a brand new feature that helps you save time and stay
                organised. It is now available to all users — no additional setup required.
              </p>

              <!-- CTA Button -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 32px;">
                <tr>
                  <td style="background-color:{{PRIMARY_COLOR}};border-radius:6px;text-align:center;">
                    <a href="#" style="display:inline-block;padding:12px 28px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                      Read More
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:15px;line-height:1.7;color:#475569;">
                Thanks for reading — we'll be back next month with more updates.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color:{{SECONDARY_COLOR}};padding:24px 32px;text-align:center;">
              <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                You are receiving this because you subscribed to our newsletter.<br>
                Sent by {{SENDER_NAME}} &lt;{{SENDER_EMAIL}}&gt;
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private function transactional(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Notification</title>
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc;">
    <tr>
      <td align="center" style="padding:40px 16px;">

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;">

          <!-- Minimal header -->
          <tr>
            <td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;text-align:left;">
              <img src="{{LOGO_URL}}" alt="Logo" width="120" style="max-width:120px;height:auto;display:inline-block;">
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:36px 32px;">
              <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:{{PRIMARY_COLOR}};text-transform:uppercase;letter-spacing:0.05em;">
                Action Required
              </p>
              <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#1e293b;font-weight:700;">
                Hello there,
              </h1>
              <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#475569;">
                We received a request associated with your account. If this was you,
                please proceed by clicking the button below. If you did not make this
                request, you can safely ignore this email.
              </p>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0;">
                <tr>
                  <td style="background-color:{{PRIMARY_COLOR}};border-radius:6px;">
                    <a href="#" style="display:inline-block;padding:13px 32px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                      Confirm Action
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 8px;font-size:13px;color:#94a3b8;">
                This link expires in 24 hours. If you need help, reply to this email.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 32px;border-top:1px solid #e2e8f0;background-color:#f8fafc;">
              <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                This is an automated message from {{SENDER_NAME}}.<br>
                Please do not reply directly — contact us at
                <a href="mailto:{{SENDER_EMAIL}}" style="color:{{PRIMARY_COLOR}};text-decoration:none;">{{SENDER_EMAIL}}</a>.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private function promotional(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Special Offer</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9;">
    <tr>
      <td align="center" style="padding:32px 16px;">

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

          <!-- Hero with primary colour background -->
          <tr>
            <td style="background-color:{{PRIMARY_COLOR}};padding:40px 32px;text-align:center;">
              <img src="{{LOGO_URL}}" alt="Logo" width="140" style="max-width:140px;height:auto;display:inline-block;margin-bottom:20px;">
              <h1 style="margin:0 0 10px;font-size:28px;font-weight:800;color:#ffffff;line-height:1.2;">
                Exclusive Offer Just for You
              </h1>
              <p style="margin:0;font-size:16px;color:rgba(255,255,255,0.85);line-height:1.5;">
                Don't miss out — this offer ends soon.
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:40px 32px 32px;">
              <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#475569;">
                We are excited to share something special with you. As one of our valued
                customers, you get early access to our latest products and an exclusive
                discount to go with it.
              </p>

              <!-- Offer box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 28px;background-color:#f8fafc;border-radius:6px;border:1px solid #e2e8f0;">
                <tr>
                  <td style="padding:24px;text-align:center;">
                    <p style="margin:0 0 4px;font-size:13px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">
                      Your Discount Code
                    </p>
                    <p style="margin:0 0 8px;font-size:28px;font-weight:800;color:{{PRIMARY_COLOR}};letter-spacing:0.1em;">
                      SAVE20
                    </p>
                    <p style="margin:0;font-size:13px;color:#64748b;">
                      Use this code at checkout for 20% off your next order.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 32px;">
                <tr>
                  <td style="background-color:{{PRIMARY_COLOR}};border-radius:6px;text-align:center;">
                    <a href="#" style="display:inline-block;padding:14px 36px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">
                      Shop Now
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:13px;color:#94a3b8;line-height:1.6;text-align:center;">
                Offer valid until end of month. Cannot be combined with other promotions.
              </p>
            </td>
          </tr>

          <!-- Footer with secondary colour -->
          <tr>
            <td style="background-color:{{SECONDARY_COLOR}};padding:24px 32px;text-align:center;">
              <p style="margin:0 0 4px;font-size:12px;color:#94a3b8;">
                {{SENDER_NAME}} · <a href="mailto:{{SENDER_EMAIL}}" style="color:#94a3b8;">{{SENDER_EMAIL}}</a>
              </p>
              <p style="margin:0;font-size:11px;color:#64748b;">
                You are receiving this because you opted in to our promotional emails.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}
