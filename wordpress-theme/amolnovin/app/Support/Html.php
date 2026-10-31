<?php

namespace App\Support;

use Illuminate\Support\Facades\Vite;

/**
 * Links, assets and the output pipeline (icon sprite + Persian digits) of the theme.
 */
class Html
{
    /** Same contract as the static template's url(): absolute/anchors/tel/mailto untouched, paths become site URLs. */
    public static function url(string $p): string
    {
        if ($p === '') {
            return home_url('/');
        }

        if (preg_match('~^(https?:|mailto:|tel:|#|//)~i', $p)) {
            return $p;
        }

        return home_url($p);
    }

    /** Path of a URL relative to the WordPress home, always "/…/" (home = "/"). */
    public static function path(string $url): string
    {
        $home = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $rel = '/' . ltrim(substr($path, strlen(rtrim($home, '/'))), '/');

        return $rel === '' ? '/' : $rel;
    }

    /** "img/…" -> resources/images, "fonts/…" -> resources/fonts; resolved through the Vite manifest (hashed file name). */
    public static function asset(string $p): string
    {
        $p = ltrim($p, '/');

        if (str_starts_with($p, 'img/')) {
            $rel = 'resources/images/' . substr($p, 4);
        } elseif (str_starts_with($p, 'fonts/')) {
            $rel = 'resources/fonts/' . substr($p, 6);
        } else {
            $rel = 'resources/' . $p;
        }

        try {
            return (string) Vite::asset($rel);
        } catch (\Throwable) {
            return get_theme_file_uri($rel);
        }
    }

    /** Does the theme ship this image? (used for OG images keyed by page) */
    public static function hasImage(string $rel): bool
    {
        return is_readable(get_theme_file_path('resources/images/' . ltrim($rel, '/')));
    }

    /** Resolve the content tokens: href="@/path" and src="@asset:img/…". */
    public static function resolve(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $html = preg_replace_callback('/href="@(\/[^"]*)"/', fn ($m) => 'href="' . esc_url(self::url($m[1])) . '"', $html);

        return preg_replace_callback('/src="@asset:([^"]*)"/', fn ($m) => 'src="' . esc_url(self::asset($m[1])) . '"', $html);
    }

    /** Replace the {SITE_URL} token used by exported schema data. */
    public static function siteTokens(mixed $data): mixed
    {
        $base = untrailingslashit(home_url());

        if (is_string($data)) {
            if (str_starts_with($data, '@asset:')) {
                return self::asset(substr($data, 7));
            }

            return str_replace('{SITE_URL}', $base, $data);
        }

        if (is_array($data)) {
            return array_map(fn ($v) => self::siteTokens($v), $data);
        }

        return $data;
    }

    /**
     * Replace the content tokens «[[phone_display]]», «[[price_design]]» … by the live values of Appearance → Customize.
     * Works on strings and (recursively) on arrays; unknown tokens are left untouched.
     */
    public static function tokens(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([self::class, 'tokens'], $data);
        }

        if (! is_string($data) || ! str_contains($data, '[[')) {
            return $data;
        }

        $site = Site::all();
        $site['street_short'] = preg_replace('/^خیابان\s+/u', '', (string) ($site['street'] ?? ''));

        return (string) preg_replace_callback('/\[\[([a-z_]+)\]\]/', function ($m) use ($site) {
            if (! isset($site[$m[1]]) || ! is_scalar($site[$m[1]])) {
                return $m[0];
            }

            $v = $site[$m[1]];

            // numbers (prices, months, years) are shown with Persian digits everywhere — including data-* attributes
            return (is_int($v) || (is_string($v) && ctype_digit($v))) ? Persian::fa((string) $v) : (string) $v;
        }, $data);
    }

    // ---------------------------------------------------------------- output pipeline

    public static function boot(): void
    {
        add_action('template_redirect', function () {
            if (is_feed() || is_robots() || is_embed() || is_trackback() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
                return;
            }

            if (! apply_filters('amol/output_pipeline', true)) {
                return;
            }

            ob_start([self::class, 'finalize']);
        }, 1);
    }

    /** icon sprite + Persian digits, exactly the post-processing the static generator applies to every page. */
    public static function finalize(string $html): string
    {
        if (! str_contains($html, '<html')) {
            return $html;
        }

        return Persian::faHtml(Icons::sprite(self::tokens($html)));
    }
}
