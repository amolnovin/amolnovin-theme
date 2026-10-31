<?php

namespace App\Support;

/**
 * Seed data of the theme (resources/data/*.json — exported from the static template) and the
 * editable "shared lists" option that overrides it.
 */
class Data
{
    protected static array $cache = [];

    /** Decoded seed file, e.g. Data::seed('types'). */
    public static function seed(string $name): array
    {
        return self::$cache[$name] ??= (function () use ($name) {
            $file = get_theme_file_path("resources/data/{$name}.json");

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reads a local file shipped with the theme.
            return is_readable($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        })();
    }

    /** Shared lists (TECH, WHY, PROCESS, PLANS, CALC, ROTATOR …): option `amol_common` overrides the seed per key. */
    public static function common(): array
    {
        return self::$cache['__common'] ??= (function () {
            $over = get_option('amol_common');
            $over = is_array($over) ? $over : (is_string($over) ? (json_decode($over, true) ?: []) : []);

            return array_replace(self::seed('common'), $over);
        })();
    }

    public static function flush(): void
    {
        unset(self::$cache['__common']);
    }
}
