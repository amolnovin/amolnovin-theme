<?php

namespace App\Support;

/**
 * Brand / contact data (the `SITE` dictionary of the static template).
 * Defaults come from resources/data/site.json; every value can be changed in Appearance → Customize → «آمل نوین».
 */
class Site
{
    protected static ?array $cache = null;

    public static function flush(): void
    {
        self::$cache = null;
    }

    public static function defaults(): array
    {
        return Data::seed('site')['site'] ?? [];
    }

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $s = self::defaults();
        $over = get_theme_mod('amol_site', []);

        if (is_array($over)) {
            foreach ($over as $k => $v) {
                if ($k === 'social' && is_array($v)) {
                    $s['social'] = array_merge($s['social'] ?? [], array_map('strval', $v));
                } elseif ($v !== '' && $v !== null) {
                    $s[$k] = $v;
                }
            }
        }

        $s['url'] = untrailingslashit(home_url());

        // phone: one field in the Customizer, the rest is derived
        $digits = preg_replace('/\D+/', '', Persian::en((string) ($s['phone'] ?? '')));
        $s['phone'] = $digits;
        $s['phone_intl'] = str_starts_with($digits, '0') ? '+98' . substr($digits, 1) : (str_starts_with($digits, '98') ? '+' . $digits : '+' . $digits);
        $s['phone_display'] = (strlen($digits) === 11 && str_starts_with($digits, '09'))
            ? substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7, 4)
            : $digits;

        $wa = (string) ($over['whatsapp'] ?? '');
        $s['whatsapp'] = $wa !== '' ? $wa : 'https://wa.me/' . ltrim($s['phone_intl'], '+');

        $s['address'] = self::pick($over, 'address') ?? ($s['region'] . '، ' . $s['city'] . '، ' . $s['street']);
        $s['maps_query'] = self::pick($over, 'maps_query') ?? ($s['city'] . '، ' . $s['street']);

        $lat = (float) ($over['geo_lat'] ?? ($s['geo'][0] ?? 0));
        $lng = (float) ($over['geo_lng'] ?? ($s['geo'][1] ?? 0));
        $s['geo'] = [$lat, $lng];

        $s['jalali_year'] = Persian::jyear();
        $s['years'] = (string) ($s['years'] ?? '');

        return self::$cache = $s;
    }

    protected static function pick(mixed $over, string $key): ?string
    {
        return (is_array($over) && isset($over[$key]) && $over[$key] !== '') ? (string) $over[$key] : null;
    }
}
