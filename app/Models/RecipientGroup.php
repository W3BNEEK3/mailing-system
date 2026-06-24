<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * RecipientGroup
 *
 * A named group (tag) that recipients can belong to.
 * Example groups: 'Clients', 'Newsletter Subscribers', 'VIPs'.
 *
 * Usage:
 *   $group   = RecipientGroup::findBy('name', 'Clients');
 *   $members = $group->members(); // array of Recipient instances
 */
class RecipientGroup extends Model
{
    protected static string $table = 'recipient_groups';

    protected array $fillable = ['name'];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Get all active (non-suppressed) recipients that belong to this group.
     *
     * Joins recipient_group_pivot → recipients and filters out suppressed contacts.
     *
     * Usage:
     *   $members = $group->members();
     *   foreach ($members as $recipient) {
     *       echo $recipient->email;
     *   }
     */
    public function members(): array
    {
        if (!$this->id) {
            return [];
        }

        return Recipient::raw(
            "SELECT r.*
             FROM recipients r
             INNER JOIN recipient_group_pivot p ON p.recipient_id = r.id
             WHERE p.group_id = ?
               AND r.is_suppressed = 0
             ORDER BY r.first_name ASC",
            [(int)$this->id]
        );
    }

    /**
     * Get the number of active members in this group.
     */
    public function memberCount(): int
    {
        if (!$this->id) {
            return 0;
        }

        $stmt = static::db()->prepare(
            "SELECT COUNT(*)
             FROM recipient_group_pivot p
             INNER JOIN recipients r ON r.id = p.recipient_id
             WHERE p.group_id = ? AND r.is_suppressed = 0"
        );
        $stmt->execute([(int)$this->id]);
        return (int)$stmt->fetchColumn();
    }
}
