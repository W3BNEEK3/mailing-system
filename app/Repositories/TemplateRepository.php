<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\TemplateData;
use App\Models\EmailTemplate;
use App\Repositories\Contracts\TemplateRepositoryInterface;

/**
 * TemplateRepository
 *
 * Manages CRUD operations for the `email_templates` table.
 *
 * Built-in vs custom:
 *   - Built-in templates (is_built_in = 1) are seeded at setup time and cannot
 *     be deleted or have their HTML overwritten by the user.
 *   - Custom templates (is_built_in = 0) are created by the user and can be
 *     fully edited, replaced, and deleted.
 *
 * HTML storage strategy:
 *   Template HTML is stored directly in the `html_content` column.
 *   When the user uploads a .html or .zip file, FileUploadService writes it
 *   to disk, then TemplateController reads the HTML back and passes it here
 *   to create(). The file on disk is kept as a source-of-truth backup, but
 *   all rendering uses the DB column.
 */
class TemplateRepository implements TemplateRepositoryInterface
{
    // ─── Queries ──────────────────────────────────────────────────────────────

    /**
     * Find all built-in templates, ordered by ID (the seed order).
     *
     * @return EmailTemplate[]
     */
    public function findBuiltIn(): array
    {
        $rows = EmailTemplate::db()
            ->query('SELECT * FROM email_templates WHERE is_built_in = 1 ORDER BY id ASC')
            ->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn($row) => EmailTemplate::fromArray($row), $rows);
    }

    /**
     * Find all custom (user-created) templates, newest first.
     *
     * @return EmailTemplate[]
     */
    public function findCustom(): array
    {
        $rows = EmailTemplate::db()
            ->query('SELECT * FROM email_templates WHERE is_built_in = 0 ORDER BY created_at DESC')
            ->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn($row) => EmailTemplate::fromArray($row), $rows);
    }

    /**
     * Find all templates (built-in + custom), for use in the compose template selector.
     *
     * @return EmailTemplate[]
     */
    public function findAll(): array
    {
        $rows = EmailTemplate::db()
            ->query('SELECT * FROM email_templates ORDER BY is_built_in DESC, name ASC')
            ->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn($row) => EmailTemplate::fromArray($row), $rows);
    }

    /**
     * Find a single template by ID.
     *
     * @return EmailTemplate|null
     */
    public function findById(int $id): ?EmailTemplate
    {
        $stmt = EmailTemplate::db()->prepare(
            'SELECT * FROM email_templates WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? EmailTemplate::fromArray($row) : null;
    }

    // ─── Mutations ────────────────────────────────────────────────────────────

    /**
     * Create a new custom template from a TemplateData DTO.
     *
     * @param TemplateData $data  Validated template data from the controller
     * @return EmailTemplate      The newly created template (with its DB-assigned id)
     */
    public function create(TemplateData $data): EmailTemplate
    {
        $stmt = EmailTemplate::db()->prepare(
            'INSERT INTO email_templates
             (name, category, html_content, is_built_in, supports_logo, supports_colors, created_at, updated_at)
             VALUES (?, ?, ?, 0, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $data->name,
            $data->category,
            $data->htmlContent,
            $data->supportsLogo   ? 1 : 0,
            $data->supportsColors ? 1 : 0,
        ]);

        $id = (int) EmailTemplate::db()->lastInsertId();

        return $this->findById($id);
    }

    /**
     * Update an existing template's name, category, and HTML content.
     * Built-in templates' HTML content is updated here too — the "built-in"
     * protection is enforced at the controller level (you cannot DELETE them),
     * not at the repository level.
     *
     * @param int          $id
     * @param TemplateData $data
     * @return EmailTemplate
     */
    public function update(int $id, TemplateData $data): EmailTemplate
    {
        $stmt = EmailTemplate::db()->prepare(
            'UPDATE email_templates
             SET name = ?, category = ?, html_content = ?,
                 supports_logo = ?, supports_colors = ?, updated_at = NOW()
             WHERE id = ?'
        );

        $stmt->execute([
            $data->name,
            $data->category,
            $data->htmlContent,
            $data->supportsLogo   ? 1 : 0,
            $data->supportsColors ? 1 : 0,
            $id,
        ]);

        return $this->findById($id);
    }

    /**
     * Delete a template by ID.
     *
     * Does NOT check is_built_in — the controller enforces that constraint.
     * This method deletes unconditionally. Never call it without first checking
     * that the template is not built-in.
     *
     * @param int $id
     * @return bool  true if a row was deleted
     */
    public function delete(int $id): bool
    {
        $stmt = EmailTemplate::db()->prepare(
            'DELETE FROM email_templates WHERE id = ?'
        );
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Duplicate a template and return the new copy.
     *
     * Rules:
     *   - `is_built_in` is always set to 0 for the copy — built-in originals
     *     remain untouched.
     *   - `name` gets " (Copy)" appended.
     *   - `created_at` and `updated_at` are reset to NOW().
     *   - All other columns (category, html_content, thumbnail_path,
     *     supports_logo, supports_colors) are copied verbatim.
     *
     * @param int $id  ID of the template to copy
     * @return EmailTemplate  The newly created copy
     */
    public function duplicate(int $id): EmailTemplate
    {
        $stmt = EmailTemplate::db()->prepare(
            "INSERT INTO email_templates
             (name, category, html_content, thumbnail_path, is_built_in, supports_logo, supports_colors, created_at, updated_at)
             SELECT
                 CONCAT(name, ' (Copy)'),
                 category,
                 html_content,
                 thumbnail_path,
                 0,
                 supports_logo,
                 supports_colors,
                 NOW(),
                 NOW()
             FROM email_templates
             WHERE id = ?"
        );

        $stmt->execute([$id]);
        $newId = (int) EmailTemplate::db()->lastInsertId();

        return $this->findById($newId);
    }
}
