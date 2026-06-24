<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Helpers\Validator;

/**
 * BaseController
 *
 * All controllers extend this class. It provides shared helper methods
 * so controllers stay clean and consistent.
 *
 * Controllers should be "thin" — they receive input, call a service or model,
 * and return a response. Business logic belongs in Service classes.
 */
abstract class BaseController
{
    /**
     * Return a JSON response.
     *
     * Usage:
     *   return $this->json(['status' => 'ok', 'message' => 'Saved']);
     *   return $this->json(['error' => 'Not found'], 404);
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Return a redirect response.
     *
     * Usage:
     *   return $this->redirect('/login');
     *   return $this->redirect('/compose', 301);
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    /**
     * Redirect back to the previous page.
     *
     * Usage:
     *   return $this->back();
     */
    protected function back(): Response
    {
        return Response::back();
    }

    /**
     * Validate request input against a set of rules.
     *
     * On success: returns the validated data array (only fields in the rules).
     * On failure: throws ValidationException with all error messages.
     *
     * The controller should catch ValidationException and call withErrors().
     *
     * Usage:
     *   $data = $this->validate($request->all(), [
     *       'email'    => 'required|email',
     *       'password' => 'required|min:8',
     *   ]);
     *   // If we get here, $data is safe to use
     */
    protected function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return $validator->validated();
    }

    /**
     * Handle a ValidationException by flashing errors to the session and redirecting back.
     *
     * This preserves:
     *   - The error messages (read back via errors() helper in views)
     *   - The old input values (read back via old() helper in views)
     *
     * Usage in a controller:
     *   try {
     *       $data = $this->validate($request->all(), $rules);
     *   } catch (ValidationException $e) {
     *       return $this->withErrors($e, $request->all());
     *   }
     */
    protected function withErrors(ValidationException $e, ?array $oldInput = null): Response
    {
        // Flash the errors array so errors() helper can read it on next request
        session()->flash('errors', $e->errors());

        // Flash the old input so old() helper can repopulate form fields
        // We omit sensitive fields like 'password' to avoid storing them in session
        $input = $oldInput ?? array_diff_key(
            request()->all(),
            array_flip(['password', 'password_confirmation', '_csrf', '_method'])
        );

        session()->flash('old', $input);

        return $this->back();
    }

    /**
     * Return an HTMX-aware success response with a toast notification.
     *
     * For HTMX requests: returns an empty body with the HX-Trigger toast header.
     * For regular requests: redirects to the given URL.
     *
     * Usage:
     *   return $this->successResponse($request, '/recipients', 'Contact saved.');
     */
    protected function successResponse(
        Request $request,
        string  $redirectUrl,
        string  $toastMessage,
        string  $toastType = 'success'
    ): Response {
        if ($request->isHtmx()) {
            return Response::html('')
                ->htmxTrigger('showToast', [
                    'type'    => $toastType,
                    'message' => $toastMessage,
                ]);
        }

        // Flash a toast message for the next page load (non-HTMX)
        session()->flash('toast', ['type' => $toastType, 'message' => $toastMessage]);
        return $this->redirect($redirectUrl);
    }
    /**
 * Render a view inside the authenticated app layout and return as a Response.
 *
 * Controllers call this for full-page responses. HTMX partial responses
 * should call $this->partial() instead (see below).
 *
 * @param string $template  Path relative to resources/ without .php extension
 * @param array  $data      Variables passed to the view
 * @param string $layout    Layout to wrap the view in (default: 'app')
 */
protected function view(string $template, array $data = [], string $layout = 'app'): Response
{
    $html = view($template, $data, $layout);
    return Response::html($html);
}

/**
 * Render a view with NO layout — for HTMX partial responses.
 *
 * When HTMX swaps only part of the page (e.g. a table, a form, a row),
 * we return only the HTML fragment, not the full document.
 *
 * Usage:
 *   return $this->partial('recipients/_table', ['recipients' => $list]);
 */
protected function partial(string $template, array $data = []): Response
{
    $html = view($template, $data, null);
    return Response::html($html);
}
}
