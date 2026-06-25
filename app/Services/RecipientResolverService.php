<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RecipientRepository;

/**
 * RecipientResolverService
 *
 * Converts the raw recipient input from the compose form into a flat list
 * of deliverable email addresses.
 *
 * The compose chip input accepts two types of strings:
 *   1. Email addresses — "alice@example.com"
 *   2. Group names    — "Newsletter" (a RecipientGroup name)
 *
 * This service:
 *   1. Detects which strings are email addresses and which are group names.
 *   2. Expands group names into the individual email addresses of their members.
 *   3. Filters out any recipients that are suppressed (is_suppressed = 1).
 *   4. Deduplicates the final list.
 *   5. Returns the resolved list AND a summary of what was filtered out.
 *
 * The summary allows the controller to surface a meaningful warning toast
 * (e.g. "2 suppressed contacts were excluded from this send.").
 *
 * Usage:
 *   $result = $resolver->resolve(['alice@example.com', 'Newsletter', 'bob@suppressed.com']);
 *   $result->emails          // ['alice@example.com', 'carol@example.com'] (Newsletter expanded, Bob suppressed)
 *   $result->suppressedCount // 1 (Bob was excluded)
 *   $result->unknownGroups   // [] (Newsletter was found)
 */
class RecipientResolverService
{
    public function __construct(
        private readonly RecipientRepository $recipients,
    ) {}

    /**
     * Resolve a raw chip input array to a deliverable email list.
     *
     * @param string[] $rawInputs  Array of chip values (email addresses or group names)
     * @return ResolveResult
     */
    public function resolve(array $rawInputs): ResolveResult
    {
        $emails        = [];  // Collector: all resolved email strings
        $suppressedCount = 0; // Counter: contacts filtered due to suppression
        $unknownGroups = [];  // Collector: group names that weren't found in DB
        $unsavedEmails = [];  // Collector: manual emails not found in DB

        foreach ($rawInputs as $input) {
            $input = trim($input);

            if ($input === '') {
                continue;
            }

            if ($this->looksLikeEmail($input)) {
                // Direct email address — check if suppressed in the database
                $contact = $this->recipients->findByEmail($input);

                if ($contact !== null && $contact->isSuppressed()) {
                    // Contact is explicitly suppressed — exclude from send
                    $suppressedCount++;
                } else {
                    // Not suppressed (or not in DB — unknown contacts are accepted)
                    $emails[] = $input;
                    if ($contact === null) {
                        $unsavedEmails[] = $input;
                    }
                }
            } else {
                // Treat as a group name — expand to member emails
                $members = $this->recipients->findByGroup($input);

                if (empty($members)) {
                    // Group doesn't exist or has no members
                    $unknownGroups[] = $input;
                } else {
                    foreach ($members as $member) {
                        if ($member->isSuppressed()) {
                            $suppressedCount++;
                        } else {
                            $emails[] = $member->email;
                        }
                    }
                }
            }
        }

        // Deduplicate while preserving order (array_unique preserves keys)
        $emails = array_values(array_unique($emails));
        $unsavedEmails = array_values(array_unique($unsavedEmails));

        return new ResolveResult(
            emails:          $emails,
            suppressedCount: $suppressedCount,
            unknownGroups:   $unknownGroups,
            unsavedEmails:   $unsavedEmails,
        );
    }

    /**
     * Determine if a string looks like an email address.
     *
     * Uses PHP's filter_var as the primary check. This is not a strict
     * RFC 5321 validator — its purpose is to distinguish "alice@example.com"
     * (email) from "Newsletter" (group name).
     */
    private function looksLikeEmail(string $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
    }
    /**
 * Search for contacts matching a query string — for autocomplete.
 *
 * @param string $query
 * @return \App\Models\Recipient[]
 */
public function searchContacts(string $query): array
{
    return $this->recipients->search($query, perPage: 5);
}

/**
 * Return all group names — for autocomplete group suggestions.
 *
 * @return string[]
 */
public function allGroups(): array
{
    $groups = $this->recipients->allGroups();
    return array_map(fn($g) => $g->name, $groups);
}
}

// ─── Value Object: ResolveResult ─────────────────────────────────────────────

/**
 * ResolveResult
 *
 * Returned by RecipientResolverService::resolve().
 * Immutable value object — all properties set at construction.
 */
readonly class ResolveResult
{
    /**
     * @param string[] $emails           Flat, deduplicated, non-suppressed email addresses
     * @param int      $suppressedCount  How many contacts were excluded due to suppression
     * @param string[] $unknownGroups    Group names that were not found in the DB
     * @param string[] $unsavedEmails    Email addresses that are not saved in the DB
     */
    public function __construct(
        public array $emails,
        public int   $suppressedCount,
        public array $unknownGroups,
        public array $unsavedEmails = [],
    ) {}

    /**
     * Check if there are any deliverable recipients.
     */
    public function isEmpty(): bool
    {
        return empty($this->emails);
    }

    /**
     * Build a human-readable warning string about exclusions (if any).
     * Returns empty string if nothing was excluded.
     *
     * e.g. "2 suppressed contact(s) excluded." or "Group 'VIPs' not found."
     */
    public function warningMessage(): string
    {
        $parts = [];

        if ($this->suppressedCount > 0) {
            $parts[] = "{$this->suppressedCount} suppressed contact(s) excluded.";
        }

        if (!empty($this->unknownGroups)) {
            $groups  = implode(', ', array_map(fn($g) => "'{$g}'", $this->unknownGroups));
            $parts[] = "Group {$groups} not found.";
        }

        return implode(' ', $parts);
    }
}