<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * AuthController
 *
 * Handles:
 *   GET  /login   — show the login form
 *   POST /login   — process credentials
 *   POST /logout  — destroy the session
 */
class AuthController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        // Instantiate the auth service once for all methods in this controller.
        $this->auth = new AuthService();
    }

    // ─── GET /login ───────────────────────────────────────────────────────────

    /**
     * Show the login form.
     */
    public function showLogin(Request $request): Response
    {
        return $this->view('auth/login', [
            'pageTitle' => 'Sign In — Emirates',
        ]);
    }

    // ─── POST /login ──────────────────────────────────────────────────────────

    /**
     * Process the login form submission.
     */
    public function login(Request $request): Response
    {
        // 1. Validate the submitted fields.
        try {
            $data = $this->validate($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
            ]);
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->withErrors($e);
        }

        // 2. Attempt to authenticate.
        $success = $this->auth->attempt($data['email'], $data['password']);

        if (!$success) {
            // Flash a generic error message and old email
            session()->flash('errors', ['email' => 'Invalid email or password. Please try again.']);
            session()->flash('old', ['email' => $data['email']]);

            // Redirect back to the login form.
            return $this->back();
        }

        // 3. Authentication succeeded — send the user to the compose page.
        return $this->redirect('/compose');
    }

    // ─── POST /logout ─────────────────────────────────────────────────────────

    /**
     * Log the user out and redirect to the login page.
     */
    public function logout(Request $request): Response
    {
        $this->auth->logout();

        // Redirect to login with a flash message.
        session()->flash('success', 'You have been signed out successfully.');

        return $this->redirect('/login');
    }
}
