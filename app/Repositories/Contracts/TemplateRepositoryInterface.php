<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\EmailTemplate;

/**
 * TemplateRepositoryInterface
 *
 * Contract for email template data access.
 */
interface TemplateRepositoryInterface
{
    /**
     * Get all built-in templates (is_built_in = 1).
     */
    public function findBuiltIn(): array;

    /**
     * Get all custom templates (is_built_in = 0).
     */
    public function findCustom(): array;

    /**
     * Duplicate an existing template.
     * Creates a copy with " (Copy)" appended to the name.
     * The copy is always custom (is_built_in = 0).
     * Returns the new template instance.
     */
    public function duplicate(int $id): EmailTemplate;
}
