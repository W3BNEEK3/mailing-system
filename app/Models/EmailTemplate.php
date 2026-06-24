<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * EmailTemplate
 *
 * Represents a row in the `email_templates` table.
 *
 * Properties map directly to table columns. Cast types are applied
 * in fromArray() so controllers and views always receive correct PHP types
 * regardless of how PDO returns the data.
 */
class EmailTemplate extends Model
{
    protected static string $table = 'email_templates';

    public int    $id;
    public string $name;
    public string $category;
    public string $htmlContent;
    public ?string $thumbnailPath;
    public bool   $isBuiltIn;
    public bool   $supportsLogo;
    public bool   $supportsColors;
    public string $createdAt;
    public string $updatedAt;

    /**
     * Hydrate an EmailTemplate from a PDO row array.
     *
     * @param array $row  Associative array of column_name => value from PDO
     * @return static
     */
    public static function fromArray(array $row): static
    {
        $obj                = new static();
        $obj->id            = (int)  $row['id'];
        $obj->name          = (string) $row['name'];
        $obj->category      = (string) $row['category'];
        $obj->htmlContent   = (string) $row['html_content'];
        $obj->thumbnailPath = $row['thumbnail_path'] ?? null;
        $obj->isBuiltIn     = (bool)   $row['is_built_in'];
        $obj->supportsLogo  = (bool)   $row['supports_logo'];
        $obj->supportsColors = (bool)  $row['supports_colors'];
        $obj->createdAt     = (string) $row['created_at'];
        $obj->updatedAt     = (string) $row['updated_at'];

        return $obj;
    }

    /**
     * Return a human-readable label for the template's category.
     * Used in view badges.
     */
    public function categoryLabel(): string
    {
        return match ($this->category) {
            'newsletter'    => 'Newsletter',
            'transactional' => 'Transactional',
            'promotional'   => 'Promotional',
            default         => ucfirst($this->category),
        };
    }

    /**
     * Return a CSS colour class for the category badge.
     * Used in _template-card.php.
     */
    public function categoryBadgeClass(): string
    {
        return match ($this->category) {
            'newsletter'    => 'bg-blue-100 text-blue-700',
            'transactional' => 'bg-green-100 text-green-700',
            'promotional'   => 'bg-purple-100 text-purple-700',
            default         => 'bg-slate-100 text-slate-600',
        };
    }
}
