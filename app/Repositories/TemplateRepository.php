<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EmailTemplate;
use App\Repositories\Contracts\TemplateRepositoryInterface;
use App\Exceptions\NotFoundException;

/**
 * TemplateRepository
 *
 * Data access for email templates.
 */
class TemplateRepository implements TemplateRepositoryInterface
{
    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Get all built-in templates, ordered by name.
     */
    public function findBuiltIn(): array
    {
        return EmailTemplate::where(['is_built_in' => 1], 'name', 'ASC');
    }

    /**
     * Get all custom (user-uploaded) templates, most recently updated first.
     */
    public function findCustom(): array
    {
        return EmailTemplate::where(['is_built_in' => 0], 'updated_at', 'DESC');
    }

    /**
     * Duplicate an existing template.
     *
     * The copy:
     *   - Has " (Copy)" appended to the name
     *   - Is always custom (is_built_in = 0)
     *   - Has the same HTML, category, supports_logo, supports_colors
     *   - Gets a fresh id, created_at, and updated_at
     */
    public function duplicate(int $id): EmailTemplate
    {
        $original = EmailTemplate::find($id);

        if (!$original) {
            throw new NotFoundException("Template #{$id} not found.");
        }

        // Create a new template copying all relevant fields from the original
        $copy = EmailTemplate::create([
            'name'            => $original->name . ' (Copy)',
            'category'        => $original->category,
            'html_content'    => $original->html_content,
            'thumbnail_path'  => null,          // No thumbnail for the copy initially
            'is_built_in'     => 0,             // Copies are always custom
            'supports_logo'   => $original->supports_logo,
            'supports_colors' => $original->supports_colors,
        ]);

        return $copy;
    }

    // ─── Standard CRUD ────────────────────────────────────────────────────

    public function find(int $id): ?object
    {
        return EmailTemplate::find($id);
    }

    public function all(): array
    {
        return EmailTemplate::all('name', 'ASC');
    }

    public function create(array $data): object
    {
        return EmailTemplate::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $template = EmailTemplate::find($id);
        return $template ? $template->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $template = EmailTemplate::find($id);
        return $template ? $template->delete() : false;
    }
}
