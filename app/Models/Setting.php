<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Setting
 *
 * Key-value pair for platform-level settings.
 */
class Setting extends Model
{
    protected static string $table = 'settings';

    protected array $fillable = ['key', 'value'];

    /**
     * Create or update a setting value.
     */
    public static function setValue(string $key, mixed $value): void
    {
        $setting = self::findBy('key', $key);
        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            self::create([
                'key' => $key,
                'value' => $value,
            ]);
        }
    }
}
