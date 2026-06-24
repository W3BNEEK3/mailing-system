<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\StorageException;
use App\Exceptions\ValidationException;
use App\Repositories\SettingRepository;
use App\Services\FileUploadService;

/**
 * SettingsController
 *
 * Manages the General Settings page (/settings/general).
 *
 * Responsibilities:
 *   index()  — Load all settings from the DB and render the form.
 *   update() — Validate submitted settings, handle logo uploads,
 *              persist changes, and return HTMX-aware feedback.
 *
 * HTMX behaviour:
 *   The form POSTs to POST /settings/general via a standard HTML form.
 *   On success, the controller returns a redirect (for full-page requests)
 *   or an HX-Trigger showToast + HX-Redirect (for HTMX requests from the
 *   Save button). Both paths result in the page reloading with the
 *   updated values and a success toast.
 *
 * File handling:
 *   The form may include up to two file uploads:
 *     - site_logo       → stored at logos/site/{uuid}.ext
 *     - email_logo      → stored at logos/global/{uuid}.ext
 *
 *   A separate "remove" checkbox per logo (remove_site_logo, remove_email_logo)
 *   signals that the existing file should be deleted even if no replacement
 *   is uploaded.
 *
 *   The controller always deletes the old file AFTER the new one is
 *   confirmed saved, to avoid data loss on upload failure.
 */
class SettingsController extends BaseController
{
    public function __construct(
        private readonly SettingRepository  $settings,
        private readonly FileUploadService  $uploader,
    ) {}

    // ─── GET /settings/general ────────────────────────────────────────────────

    /**
     * Render the General Settings form.
     *
     * Loads all settings as a flat key-value array and passes them to the view.
     * Also passes the list of supported languages and timezones for the dropdowns.
     */
    public function index(Request $request): Response
    {
        return $this->view('settings/general', [
            'pageTitle'          => 'General Settings',
            'settings'           => $this->settings->allAsKeyValue(),
            'supportedLanguages' => $this->supportedLanguages(),
            'timezones'          => \DateTimeZone::listIdentifiers(\DateTimeZone::ALL),
        ]);
    }

    // ─── POST /settings/general ───────────────────────────────────────────────

    /**
     * Validate and persist submitted settings.
     *
     * Validation rules:
     *   site_name         — required, max 100 chars
     *   site_url          — required, valid URL
     *   sender_name       — required, max 100 chars
     *   sender_email      — required, valid email
     *   primary_color     — required, matches hex colour pattern
     *   secondary_color   — required, matches hex colour pattern
     *   default_language  — required, must be in supported list
     *   timezone          — required, must be a valid PHP timezone identifier
     *   site_logo         — optional file upload (validated in FileUploadService)
     *   email_logo        — optional file upload
     */
    public function update(Request $request): Response
    {
        // ── 1. Validate scalar fields ──────────────────────────────────────

        $validLanguages = array_keys($this->supportedLanguages());
        $validTimezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

        try {
            $data = $this->validate($request->post(), [
                'site_name'        => 'required|max:100',
                'site_url'         => 'required|url',
                'sender_name'      => 'required|max:100',
                'sender_email'     => 'required|email',
                'primary_color'    => 'required|regex:/^#[0-9a-fA-F]{6}$/',
                'secondary_color'  => 'required|regex:/^#[0-9a-fA-F]{6}$/',
                'default_language' => 'required|in:' . implode(',', $validLanguages),
                'timezone'         => 'required|in:' . implode(',', $validTimezones),
            ]);
        } catch (ValidationException $e) {
            return $this->withErrors($e);
        }

        // ── 2. Handle site logo upload / removal ──────────────────────────

        $data = $this->handleLogoUpload(
            request:   $request,
            data:      $data,
            fieldName: 'site_logo',
            context:   'site',
            settingKey:'site_logo_path',
        );

        // ── 3. Handle email (global) logo upload / removal ─────────────────

        $data = $this->handleLogoUpload(
            request:   $request,
            data:      $data,
            fieldName: 'email_logo',
            context:   'global',
            settingKey:'email_logo_path',
        );

        // ── 4. Persist all scalar settings ────────────────────────────────

        $persistKeys = [
            'site_name', 'site_url', 'sender_name', 'sender_email',
            'primary_color', 'secondary_color', 'default_language', 'timezone',
        ];

        foreach ($persistKeys as $key) {
            if (isset($data[$key])) {
                $this->settings->set($key, $data[$key]);
            }
        }

        // Persist logo paths if they were updated
        if (array_key_exists('site_logo_path', $data)) {
            $this->settings->set('site_logo_path', $data['site_logo_path']);
        }
        if (array_key_exists('email_logo_path', $data)) {
            $this->settings->set('email_logo_path', $data['email_logo_path']);
        }

        // ── 5. Respond ────────────────────────────────────────────────────

        $successToast = ['type' => 'success', 'message' => 'Settings updated successfully.'];

        if ($request->isHtmx()) {
            // HTMX request: trigger a toast and redirect the browser to reload
            // the settings page so the form shows the freshly saved values.
            return Response::html('')
                ->withStatus(200)
                ->htmxTrigger('showToast', $successToast)
                ->htmxRedirect('/settings/general');
        }

        // Standard form POST: flash a success message and redirect
        session()->flash('_toast', $successToast);
        return $this->redirect('/settings/general');
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Handle a logo file upload or removal for a single logo field.
     *
     * Mutates and returns $data with an updated '{settingKey}' entry when
     * a new file is uploaded or the existing file is removed.
     *
     * On upload failure (StorageException), flashes an error toast and
     * does NOT update the stored path — the old logo is preserved.
     *
     * @param Request $request
     * @param array   $data       Validated scalar data array (passed by value, returned modified)
     * @param string  $fieldName  Name of the file input: 'site_logo' or 'email_logo'
     * @param string  $context    Sub-context for FileUploadService: 'site' or 'global'
     * @param string  $settingKey The setting key that stores the relative file path
     * @return array  Modified $data array
     */
    private function handleLogoUpload(
        Request $request,
        array   $data,
        string  $fieldName,
        string  $context,
        string  $settingKey,
    ): array {
        $file       = $request->file($fieldName);
        $shouldRemove = (bool) $request->post("remove_{$fieldName}");
        $existingPath = $this->settings->get($settingKey);

        // Case A: New file uploaded
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            try {
                $newPath = $this->uploader->uploadLogo($file, $context);

                // Only delete old file after new one is safely stored
                if ($existingPath) {
                    $this->uploader->delete($existingPath);
                }

                $data[$settingKey] = $newPath;
            } catch (StorageException $e) {
                // Non-fatal: log the error and carry on without updating the logo.
                // The user will see the error via the toast triggered in update().
                logger()->warning("Logo upload failed for {$fieldName}: {$e->getMessage()}");
                session()->flash('_toast', [
                    'type'    => 'error',
                    'message' => "Logo upload failed: {$e->getMessage()}",
                ]);
            }

            return $data;
        }

        // Case B: Remove checkbox ticked, no new file
        if ($shouldRemove && $existingPath) {
            try {
                $this->uploader->delete($existingPath);
            } catch (StorageException $e) {
                logger()->warning("Logo delete failed for {$fieldName}: {$e->getMessage()}");
            }

            $data[$settingKey] = null;
        }

        return $data;
    }

    /**
     * Return the supported languages associative array.
     * Keys are language codes; values are display names.
     * Used for validation and for populating the dropdown.
     *
     * @return array<string, string>
     */
    private function supportedLanguages(): array
    {
        return config('translation.supported_languages', [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'pt' => 'Portuguese',
            'ar' => 'Arabic',
            'de' => 'German',
            'zh' => 'Chinese (Simplified)',
            'yo' => 'Yoruba',
        ]);
    }
}
