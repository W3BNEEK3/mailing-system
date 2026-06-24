<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * User
 *
 * The single application user (business owner / operator).
 * There is only ever one row in this table.
 *
 * Usage:
 *   $user = User::find(1);
 *   $user = User::findBy('email', 'admin@example.com');
 *   $user->verifyPassword('mypassword');
 */
class User extends Model
{
    protected static string $table = 'users';

    // Only these columns can be set via create() or update()
    protected array $fillable = ['name', 'email', 'password_hash'];

    // ─── Type-hinted property accessors ───────────────────────────────────
    // These are documentation helpers — PHP reads the actual values
    // from $this->attributes via the __get magic method in Model.

    // public int    $id;
    // public string $name;
    // public string $email;
    // public string $password_hash;
    // public string $created_at;

    // ─── Domain methods ───────────────────────────────────────────────────

    /**
     * Verify that a plaintext password matches this user's stored hash.
     *
     * Usage:
     *   if ($user->verifyPassword($request->post('password'))) { ... }
     */
    public function verifyPassword(string $plaintext): bool
    {
        return password_verify($plaintext, (string)$this->password_hash);
    }

    /**
     * Get the user's display name, falling back to their email.
     */
    public function displayName(): string
    {
        return (string)($this->name ?: $this->email);
    }
}
