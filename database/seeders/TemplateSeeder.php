<?php

declare(strict_types=1);

/**
 * TemplateSeeder
 *
 * Seeds the three built-in email templates.
 * These are professional HTML emails using the token placeholders
 * {{LOGO_URL}}, {{PRIMARY_COLOR}}, {{SECONDARY_COLOR}}.
 *
 * Built-in templates cannot be deleted by the user — only duplicated.
 * Uses INSERT IGNORE so re-running is safe.
 */
class TemplateSeeder
{
    public function run(PDO $pdo): void
    {
        $templates = [
            [
                'name'            => 'Newsletter',
                'category'        => 'Newsletter',
                'is_built_in'     => 1,
                'supports_logo'   => 1,
                'supports_colors' => 1,
                'html_content'    => $this->newsletterTemplate(),
            ],
            [
                'name'            => 'Transactional',
                'category'        => 'Transactional',
                'is_built_in'     => 1,
                'supports_logo'   => 1,
                'supports_colors' => 1,
                'html_content'    => $this->transactionalTemplate(),
            ],
            [
                'name'            => 'Promotional',
                'category'        => 'Promotional',
                'is_built_in'     => 1,
                'supports_logo'   => 1,
                'supports_colors' => 1,
                'html_content'    => $this->promotionalTemplate(),
            ],
        ];

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO email_templates
                (name, category, html_content, is_built_in, supports_logo, supports_colors)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($templates as $template) {
            $stmt->execute([
                $template['name'],
                $template['category'],
                $template['html_content'],
                $template['is_built_in'],
                $template['supports_logo'],
                $template['supports_colors'],
            ]);
            echo "    ✅ Template: {$template['name']}\n";
        }
    }

    // ─── Built-in template HTML ───────────────────────────────────────────

    /**
     * Newsletter template.
     * Clean single-column layout with logo, heading, body, and footer.
     */
    private function newsletterTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter</title>
    <style>
        body        { margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper    { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,0.08); }
        .header     { background-color: {{PRIMARY_COLOR}}; padding: 32px 40px; text-align: center; }
        .header img { max-height: 50px; max-width: 200px; }
        .body       { padding: 40px; color: #374151; }
        .body h1    { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0 0 16px; }
        .body p     { font-size: 1rem; line-height: 1.7; color: #4b5563; margin: 0 0 16px; }
        .divider    { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .footer     { background-color: #f9fafb; padding: 24px 40px; text-align: center; }
        .footer p   { font-size: 0.78rem; color: #9ca3af; margin: 0; line-height: 1.6; }
        .footer a   { color: {{SECONDARY_COLOR}}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <img src="{{LOGO_URL}}" alt="Logo">
        </div>
        <div class="body">
            <h1>Your Newsletter Headline</h1>
            <p>
                Welcome to this month's edition. Here's what we've been working on and what's
                coming up next. We're excited to share these updates with you.
            </p>
            <hr class="divider">
            <h1>Section Two</h1>
            <p>
                Add more content sections here. Each section can have its own heading and
                body text. Keep it concise and scannable for best engagement.
            </p>
        </div>
        <div class="footer">
            <p>
                You're receiving this because you subscribed to our newsletter.<br>
                <a href="#">Unsubscribe</a> &nbsp;·&nbsp; <a href="#">View in browser</a>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Transactional template.
     * Minimal, clean layout for receipts, confirmations, and notifications.
     */
    private function transactionalTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification</title>
    <style>
        body        { margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper    { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,0.08); }
        .header     { padding: 32px 40px 0; text-align: center; }
        .header img { max-height: 44px; max-width: 180px; }
        .body       { padding: 32px 40px; color: #374151; }
        .body h1    { font-size: 1.375rem; font-weight: 700; color: #111827; margin: 0 0 12px; }
        .body p     { font-size: 0.9375rem; line-height: 1.7; color: #4b5563; margin: 0 0 16px; }
        .cta        { text-align: center; margin: 28px 0; }
        .cta a      { display: inline-block; padding: 14px 36px; background-color: {{PRIMARY_COLOR}};
                      color: #ffffff; text-decoration: none; border-radius: 8px;
                      font-weight: 600; font-size: 0.9375rem; letter-spacing: 0.01em; }
        .footer     { border-top: 1px solid #e5e7eb; padding: 20px 40px; text-align: center; }
        .footer p   { font-size: 0.75rem; color: #9ca3af; margin: 0; }
        .footer a   { color: {{SECONDARY_COLOR}}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <img src="{{LOGO_URL}}" alt="Logo">
        </div>
        <div class="body">
            <h1>Hi there 👋</h1>
            <p>
                This is a transactional email notification. Replace this text with
                your specific message — a receipt, confirmation, alert, or any
                action-triggered communication.
            </p>
            <p>
                Keep transactional emails short, clear, and focused on a single action.
            </p>
            <div class="cta">
                <a href="#">Take Action →</a>
            </div>
            <p style="font-size:0.875rem; color:#6b7280;">
                If you have any questions, reply to this email and we'll be happy to help.
            </p>
        </div>
        <div class="footer">
            <p>© 2025 Your Company. All rights reserved.<br>
               <a href="#">Unsubscribe</a></p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Promotional template.
     * Eye-catching layout with a coloured hero section for marketing emails.
     */
    private function promotionalTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotion</title>
    <style>
        body         { margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper     { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,0.08); }
        .hero        { background-color: {{PRIMARY_COLOR}}; padding: 48px 40px; text-align: center; }
        .hero img    { max-height: 50px; max-width: 180px; margin-bottom: 24px; display: block; margin-left: auto; margin-right: auto; }
        .hero h1     { font-size: 1.75rem; font-weight: 800; color: #ffffff; margin: 0 0 12px; line-height: 1.25; }
        .hero p      { font-size: 1rem; color: rgba(255,255,255,0.85); margin: 0; line-height: 1.6; }
        .body        { padding: 40px; color: #374151; }
        .body p      { font-size: 0.9375rem; line-height: 1.7; color: #4b5563; margin: 0 0 20px; }
        .cta         { text-align: center; margin: 28px 0; }
        .cta a       { display: inline-block; padding: 16px 44px; background-color: {{PRIMARY_COLOR}};
                       color: #ffffff; text-decoration: none; border-radius: 8px;
                       font-weight: 700; font-size: 1rem; }
        .accent-bar  { height: 4px; background: linear-gradient(90deg, {{PRIMARY_COLOR}}, {{SECONDARY_COLOR}}); }
        .footer      { background-color: #f9fafb; padding: 24px 40px; text-align: center; }
        .footer p    { font-size: 0.75rem; color: #9ca3af; margin: 0; line-height: 1.6; }
        .footer a    { color: {{SECONDARY_COLOR}}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hero">
            <img src="{{LOGO_URL}}" alt="Logo">
            <h1>Your Big Offer Headline</h1>
            <p>A compelling sub-headline that drives excitement and urgency.</p>
        </div>
        <div class="body">
            <p>
                Hello! We have something exciting to share with you. This is where you
                describe your promotion, offer, or announcement in a few short paragraphs.
            </p>
            <p>
                Keep the message focused on the benefit to the reader. What do they get?
                Why should they act now? What makes this offer special?
            </p>
            <div class="cta">
                <a href="#">Claim Your Offer →</a>
            </div>
            <p style="font-size:0.8125rem; color:#9ca3af; text-align:center;">
                Offer valid until [date]. Terms and conditions apply.
            </p>
        </div>
        <div class="accent-bar"></div>
        <div class="footer">
            <p>
                You received this because you signed up for updates.<br>
                <a href="#">Unsubscribe</a> &nbsp;·&nbsp; <a href="#">Privacy Policy</a>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
