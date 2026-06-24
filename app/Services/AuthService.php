<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * AuthService
 *
 * Handles all authentication operations:
 *   - Verifying credentials against the database
 *   - Writing and reading the user session
 *   - Logging out
 *
 * The service never touches HTTP — no redirects, no response building.
 * That belongs in the controller. The service just returns true/false or data.
 */
class AuthService
{
    // ─── Authentication ───────────────────────────────────────────────────────

    /**
     * Attempt to authenticate with the given credentials.
     */
    public function attempt(string $email, string $password): bool
    {
        // 1. Find the user — returns null if no matching email
        $user = User::findBy('email', trim($email));

        if ($user === null) {
            return false;
        }

        // 2. Verify the password against the bcrypt hash stored in the database.
        if (!password_verify($password, $user->password_hash)) {
            return false;
        }

        // 3. Regenerate the session ID to prevent session fixation attacks.
        session()->regenerate();

        // 4. Store the authenticated user's data in the session.
        session()->set('user_id',   (int) $user->id);
        session()->set('user_name', (string) $user->name);
        session()->set('user_email', (string) $user->email);

        return true;
    }

    // ─── Status checks ────────────────────────────────────────────────────────

    /**
     * Check whether the current request has an authenticated session.
     */
    public function check(): bool
    {
        return session()->has('user_id');
    }

    /**
     * Retrieve the authenticated user's full record from the database.
     */
    public function user(): ?User
    {
        $userId = session()->get('user_id');

        if ($userId === null) {
            return null;
        }

        return User::find((int) $userId);
    }

    /**
     * Get the authenticated user's ID from the session.
     */
    public function id(): ?int
    {
        $id = session()->get('user_id');
        return $id !== null ? (int) $id : null;
    }

    /**
     * Get the authenticated user's name from the session.
     */
    public function name(): string
    {
        return (string) session()->get('user_name', '');
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    /**
     * Log the current user out.
     */
    public function logout(): void
    {
        session()->destroy();
    }
}
