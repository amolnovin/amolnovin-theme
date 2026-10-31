<?php

namespace App\Support;

use App\Content\Repo;

/**
 * «تنظیمات شمارنده» — the animated numbers of the home page and the portfolio page.
 *
 * Rows are stored in تنظیمات قالب → شمارنده‌ها as `value|suffix|label`. A value may be a plain number or one of the
 * dynamic tokens {types}, {projects}, {detail}, {posts}, {years}, {founded}, so the numbers follow the real content
 * instead of being hard-coded in the template.
 */
class Counters
{
    public static function enabled(): bool
    {
        return (bool) Options::get('counters', 'enabled', true);
    }

    public static function duration(): int
    {
        $duration = (int) Options::get('counters', 'duration', 1400);

        return $duration > 0 ? $duration : 0;
    }

    /**
     * Resolved rows of a set.
     *
     * @return array<int,array{value:string,number:string,suffix:string,label:string}>
     */
    public static function items(string $set = 'home'): array
    {
        $rows = Options::get('counters', $set, []);
        $out = [];

        foreach ((array) $rows as $row) {
            if (! is_array($row) || empty($row['label'])) {
                continue;
            }

            $value = self::resolve((string) ($row['value'] ?? ''));
            // the animation needs Latin digits: the panel may be filled with ۱۵ or ١٥
            $number = (string) preg_replace('/[^0-9\.\-]/', '', Persian::en($value));

            $out[] = [
                'value' => $value,
                'number' => $number !== '' ? $number : '0',
                'suffix' => (string) ($row['suffix'] ?? ''),
                'label' => self::resolve((string) $row['label']),
            ];
        }

        return $out;
    }

    /** Replace {token} placeholders with live numbers (Persian digits are added by the template). */
    public static function resolve(string $value): string
    {
        if (! str_contains($value, '{')) {
            return $value;
        }

        $site = Site::all();
        $founded = (int) ($site['founded_j'] ?? 0);
        $years = $founded > 0 ? max(0, (int) $site['jalali_year'] - $founded) : 0;

        $map = [
            '{types}' => (string) count(Repo::types()),
            '{projects}' => (string) count(Repo::projects()),
            '{detail}' => (string) count(Repo::projectsWithDetail()),
            '{posts}' => (string) count(Repo::posts()),
            '{years}' => (string) $years,
            '{founded}' => (string) $founded,
        ];

        return strtr($value, $map);
    }
}
