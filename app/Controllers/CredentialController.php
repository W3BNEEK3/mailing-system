<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\CredentialService;

/**
 * CredentialController
 *
 * Manages the Email Credentials settings page (/settings/credentials).
 *
 * Routes:
 *   GET  /settings/credentials          → index()
 *   POST /settings/credentials          → store()
 *   POST /settings/credentials/test     → test()
 *
 * Page behaviour:
 *   The credentials page has two forms — one for Resend and one for SMTP.
 *   Each form has a hidden `provider` field identifying which provider it belongs to.
 *   Both forms POST to the same `store()` endpoint.
 *
 *   Password fields are always rendered as empty inputs in the HTML form.
 *   If credentials already exist for a provider, a masked placeholder (••••••••)
 *   is shown below the field to indicate "credentials are saved". Submitting the
 *   form with a blank password field does NOT clear the existing password —
 *   the controller merges the new config with the existing config so that
 *   fields left blank preserve their saved values.
 *
 * HTMX behaviour:
 *   The "Test Connection" button in each section posts to `test()` via HTMX
 *   and receives a partial response — either a success or error badge — that
 *   replaces the test result area (#test-result-{provider}).
 *
 *   The "Save" button can also use HTMX (hx-post on the form) or a standard POST.
 *   On HTMX save: returns a toast trigger header.
 *   On standard save: flashes a toast and redirects.
 *
 * Security:
 *   Credentials are NEVER echoed back in any response.
 *   The `maskField()` method on the Credential model is used to show
 *   "••••••••" where credentials exist without revealing any characters.
 *   No credential values are logged.
 */
class CredentialController extends BaseController
{
    public function __construct(
        private readonly CredentialService $credentials
    ) {}

    // ─── GET /settings/credentials ────────────────────────────────────────────

    /**
     * Render the credentials settings page.
     *
     * Loads both Resend and SMTP credential rows (may be null if not yet configured).
     * The active provider is determined by is_active = 1.
     */
    public function index(Request $request): Response
    {
        $resend = $this->credentials->getForProvider('resend');
        $smtp   = $this->credentials->getForProvider('smtp');
        $active = $this->credentials->getActive();

        return $this->view('settings/credentials', [
            'pageTitle'      => 'Email Credentials',
            'resend'         => $resend,        // Credential|null
            'smtp'           => $smtp,          // Credential|null
            'activeProvider' => $active?->provider ?? null, // 'resend', 'smtp', or null
        ]);
    }

    // ─── POST /settings/credentials ──────────────────────────────────────────

    /**
     * Save credentials for a provider.
     *
     * The form must include a hidden `provider` field ('resend' or 'smtp').
     * Only the fields for that provider are validated and saved.
     *
     * Password merge logic:
     *   If the user submits a blank password-type field, the existing stored
     *   password is preserved (so users can update the API key without
     *   having to re-enter the password, and vice versa).
     *   If the user enters a new value, the new value replaces the old one.
     *
     * Activate-on-save:
     *   If the `set_active` checkbox is ticked, this provider is immediately
     *   set as the active provider.
     */
    public function store(Request $request): Response
    {
        $provider = $request->post('provider', '');

        if (!in_array($provider, ['resend', 'smtp'], true)) {
            return $this->redirect('/settings/credentials');
        }

        // ── Route to the correct handler based on provider type ────────────
        try {
            if ($provider === 'resend') {
                return $this->storeResend($request);
            }
            return $this->storeSmtp($request);
        } catch (ValidationException $e) {
            return $this->withErrors($e, $request->post());
        }
    }

    // ─── POST /settings/credentials/test ─────────────────────────────────────

    /**
     * Test a provider's connection and return an HTMX partial with the result.
     *
     * Called via HTMX from the "Test Connection" button in each provider section.
     * Returns an inline result badge (success or error) that replaces
     * #test-result-{provider} in the DOM.
     *
     * This endpoint DOES NOT send a real email — it only tests connectivity.
     *
     * @param Request $request  Must contain `provider` POST param ('resend' or 'smtp')
     */
    public function test(Request $request): Response
    {
        $provider = $request->post('provider', '');

        if (!in_array($provider, ['resend', 'smtp'], true)) {
            return $this->partial('settings/_credential-test-result', [
                'success'  => false,
                'message'  => 'Unknown provider.',
                'provider' => $provider,
            ]);
        }

        $result = $this->credentials->testConnection($provider);

        // Return a small HTML partial (no layout)
        return $this->partial('settings/_credential-test-result', [
            'success'  => $result['success'],
            'message'  => $result['message'],
            'provider' => $provider,
        ]);
    }

    // ─── Private: Resend save ─────────────────────────────────────────────────

    /**
     * Validate and save Resend credentials.
     *
     * Resend config fields:
     *   api_key  — required if no existing credentials; optional if updating
     *              (blank = keep existing key)
     *
     * @throws ValidationException
     */
    private function storeResend(Request $request): Response
    {
        $apiKey  = trim($request->post('api_key', ''));
        $existing = $this->credentials->getForProvider('resend');

        // If no existing credentials, the API key is required
        if (!$existing && $apiKey === '') {
            throw new ValidationException(['api_key' => 'API Key is required when adding Resend for the first time.']);
        }

        // Build config: start from existing decrypted config, overlay new values
        $config = $existing ? $existing->decryptedConfig() : [];

        if ($apiKey !== '') {
            $config['api_key'] = $apiKey;
        }

        // Optional: from_email stored for informational display
        $fromEmail = trim($request->post('from_email', ''));
        if ($fromEmail !== '') {
            $config['from_email'] = $fromEmail;
        }

        $this->credentials->save('resend', $config);

        // Activate if checkbox is checked
        if ($request->post('set_active') === '1') {
            $this->credentials->setActive('resend');
        }

        return $this->credentialSaved($request, 'resend');
    }

    /**
     * Validate and save SMTP credentials.
     *
     * SMTP config fields:
     *   host, port, encryption — always required
     *   username               — required
     *   password               — required for new, optional for update (blank = keep existing)
     *   from_name, from_email  — optional
     *
     * @throws ValidationException
     */
    private function storeSmtp(Request $request): Response
    {
        $existing = $this->credentials->getForProvider('smtp');

        // Validate fields that are always required
        $data = $this->validate($request->post(), [
            'host'       => 'required|max:255',
            'port'       => 'required|integer',
            'encryption' => 'required|in:,ssl,tls,starttls',
            'username'   => 'required|max:255',
        ]);

        $password = trim($request->post('password', ''));

        // Password is required only if no existing credentials exist
        if (!$existing && $password === '') {
            throw new ValidationException(['password' => 'Password is required when setting up SMTP for the first time.']);
        }

        // Merge: start from existing config, overlay new values
        $config = $existing ? $existing->decryptedConfig() : [];

        $config['host']       = $data['host'];
        $config['port']       = (int) $data['port'];
        $config['encryption'] = $data['encryption'];
        $config['username']   = $data['username'];

        if ($password !== '') {
            $config['password'] = $password;
        }

        $fromName  = trim($request->post('from_name', ''));
        $fromEmail = trim($request->post('from_email', ''));

        if ($fromName !== '')  $config['from_name']  = $fromName;
        if ($fromEmail !== '') $config['from_email'] = $fromEmail;

        $this->credentials->save('smtp', $config);

        // Activate if requested
        if ($request->post('set_active') === '1') {
            $this->credentials->setActive('smtp');
        }

        return $this->credentialSaved($request, 'smtp');
    }

    /**
     * Build the success response after saving credentials.
     *
     * HTMX request:    Returns a toast trigger header + HX-Redirect to reload the page
     * Regular request: Flashes a toast and redirects
     */
    private function credentialSaved(Request $request, string $provider): Response
    {
        $label = $provider === 'resend' ? 'Resend' : 'SMTP';
        $toast = [
            'type'    => 'success',
            'message' => "{$label} credentials saved successfully.",
        ];

        if ($request->isHtmx()) {
            return Response::html('')
                ->htmxTrigger('showToast', $toast)
                ->htmxRedirect('/settings/credentials');
        }

        session()->flash('_toast', $toast);
        return $this->redirect('/settings/credentials');
    }
}
