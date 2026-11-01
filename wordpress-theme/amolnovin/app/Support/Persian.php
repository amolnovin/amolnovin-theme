<?php

namespace App\Support;

/**
 * Persian text helpers — an exact PHP port of the digit / calendar helpers of the static template generator.
 */
class Persian
{
    public const FA = '۰۱۲۳۴۵۶۷۸۹';

    protected const MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    /** text nodes inside these tags keep Latin digits */
    protected const SKIP_TAGS = ['script', 'style', 'svg', 'textarea', 'code', 'pre', 'kbd'];

    /** <meta> tags whose content is user visible */
    protected const META_RE = '/^<meta\s+(?:name|property)="(?:description|og:title|og:description|og:image:alt|og:site_name|twitter:title|twitter:description|twitter:image:alt)"/';

    protected static function map(): array
    {
        static $map = null;

        return $map ??= array_combine(str_split('0123456789'), mb_str_split(self::FA));
    }

    /** Latin digits -> Persian digits (every digit of the string). */
    public static function fa(string $s): string
    {
        return strtr($s, self::map());
    }

    /** Persian / Arabic-Indic digits -> Latin digits. */
    public static function en(string $s): string
    {
        return strtr($s, array_flip(self::map()) + array_combine(mb_str_split('٠١٢٣٤٥٦٧٨٩'), str_split('0123456789')));
    }

    protected static function convertText(string $t): string
    {
        // numbers only — never touches HTML entities such as &#39;
        return preg_replace_callback(
            '/(?<![A-Za-z0-9])(?<!&#)[0-9]+(?:[.,\/:\-][0-9]+)*(?![A-Za-z])/',
            fn ($m) => strtr($m[0], self::map()),
            $t
        );
    }

    /** Convert digits of every text node (and alt/title/aria-label + SEO <meta> content) of an HTML document. */
    public static function faHtml(string $doc): string
    {
        $parts = preg_split('/(<!--.*?-->|<[^>]+>)/s', $doc, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $out = [];
        $skip = 0;

        foreach ($parts as $part) {
            if (str_starts_with($part, '<!--')) {
                $out[] = $part;

                continue;
            }

            if ($part[0] === '<') {
                if (preg_match('/^<(\/?)([a-zA-Z0-9]+)/', $part, $m)) {
                    $name = strtolower($m[2]);
                    $closing = $m[1] === '/';
                    $isSkipTag = in_array($name, self::SKIP_TAGS, true);

                    if ($isSkipTag) {
                        if ($closing) {
                            $skip = max(0, $skip - 1);
                        } elseif (! str_ends_with(rtrim($part), '/>')) {
                            $skip++;
                        }
                    }

                    if (! $skip || $isSkipTag) {
                        $part = preg_replace_callback(
                            '/(\s(?:alt|title|aria-label)=")([^"]*)(")/',
                            fn ($a) => $a[1] . self::convertText($a[2]) . $a[3],
                            $part
                        );

                        if ($name === 'meta' && preg_match(self::META_RE, $part)) {
                            $part = preg_replace_callback(
                                '/(content=")([^"]*)(")/',
                                fn ($a) => $a[1] . self::convertText($a[2]) . $a[3],
                                $part
                            );
                        }
                    }
                }
                $out[] = $part;
            } else {
                $out[] = $skip ? $part : self::convertText($part);
            }
        }

        return implode('', $out);
    }

    /** Digits inside all strings of a JSON-LD structure (urls, ids, dates and numeric fields excluded). */
    public static function faObj(mixed $o): mixed
    {
        static $keep = ['@id', '@type', 'url', 'telephone', 'email', 'datePublished', 'dateModified', 'price', 'minPrice', 'priceCurrency', 'image', 'logo', 'sameAs', '@context', 'inLanguage', 'latitude', 'longitude', 'item', 'contentUrl'];

        if (is_string($o)) {
            if (str_starts_with($o, 'http') || str_starts_with($o, 'tel:') || str_starts_with($o, 'mailto:') || str_starts_with($o, '+') || str_starts_with($o, '@') || preg_match('/^[\d\-:TZ+.]+$/', $o)) {
                return $o;
            }

            return self::convertText($o);
        }

        if (is_array($o)) {
            $out = [];
            foreach ($o as $k => $v) {
                $out[$k] = (is_string($k) && in_array($k, $keep, true)) ? $v : self::faObj($v);
            }

            return $out;
        }

        return $o;
    }

    // ---------------------------------------------------------------- Jalali calendar

    /** @return array{0:int,1:int,2:int} */
    public static function g2j(int $gy, int $gm, int $gd): array
    {
        $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + $days % 31;
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + ($days - 186) % 30;
        }

        return [$jy, $jm, $jd];
    }

    /** "2026-10-15" -> "23 مهر 1405" (Latin digits; faHtml converts them on output) */
    public static function jdate(string $iso): string
    {
        // drafts, auto-drafts and imported rows may carry an empty / zero date ("0000-00-00")
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', substr(trim($iso), 0, 10), $m)) {
            return '';
        }

        $y = (int) $m[1];
        $mo = (int) $m[2];
        $d = (int) $m[3];

        if ($y < 1900 || $y > 2200 || $mo < 1 || $mo > 12 || $d < 1 || $d > 31) {
            return '';
        }

        [$jy, $jm, $jd] = self::g2j($y, $mo, $d);
        $month = self::MONTHS[$jm - 1] ?? '';

        return $month === '' ? '' : $jd . ' ' . $month . ' ' . $jy;
    }

    public static function jyear(): int
    {
        return self::g2j((int) wp_date('Y'), (int) wp_date('n'), (int) wp_date('j'))[0];
    }

    // ---------------------------------------------------------------- text

    public static function stripTags(string $h): string
    {
        $t = preg_replace('/<[^>]+>/', ' ', $h);
        $t = preg_replace('/\s+/u', ' ', $t);

        return trim(html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function wordCount(string $html): int
    {
        $t = self::stripTags($html);

        return $t === '' ? 0 : count(preg_split('/\s+/u', $t, -1, PREG_SPLIT_NO_EMPTY));
    }

    public static function readMinutes(string $html, int $wpm = 170): int
    {
        return max(1, (int) round(self::wordCount($html) / $wpm, 0, PHP_ROUND_HALF_EVEN));
    }
}
