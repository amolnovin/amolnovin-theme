<?php

namespace App\Optimize;

use App\Support\Options;

/**
 * «بهینه‌سازی و سئو» tab of the theme settings panel.
 *
 * The theme's SEO engine lives in App\Seo\Head (meta / Open Graph), App\Seo\Feeds (sitemap, robots.txt, llms.txt,
 * manifest) and App\Seo\Schema (JSON-LD). This class only holds the switches those classes read.
 */
class Seo
{
    public static function boot(): void
    {
        add_action('wp_head', [self::class, 'verification'], 2);
        add_action('pre_get_posts', [self::class, 'postsPerPage']);

        if (self::siteNoindex()) {
            add_filter('wp_robots', [self::class, 'robots'], 99);
            add_filter('wp_robots_noindex', '__return_true');
        }
    }

    /** The theme's own <head> SEO output. */
    public static function enabled(): bool
    {
        return (bool) Options::get('seo', 'enabled', true);
    }

    /** Staging mode: the whole site is noindex (robots.txt + meta robots). */
    public static function siteNoindex(): bool
    {
        return (bool) Options::get('seo', 'site_noindex', false);
    }

    public static function sitemap(): bool
    {
        return (bool) Options::get('seo', 'sitemap', true);
    }

    public static function llms(): bool
    {
        return (bool) Options::get('seo', 'llms', true);
    }

    public static function manifest(): bool
    {
        return (bool) Options::get('seo', 'manifest', true);
    }

    public static function breadcrumbs(): bool
    {
        return (bool) Options::get('seo', 'breadcrumbs', true);
    }

    public static function organizationType(): string
    {
        $type = (string) Options::get('seo', 'organization_type', 'ProfessionalService');

        return $type !== '' ? $type : 'ProfessionalService';
    }

    /** Fallback Open Graph image ('' = the theme's own default card). */
    public static function ogDefault(): string
    {
        return esc_url_raw((string) Options::get('seo', 'og_default', ''));
    }

    /** @return array<string,string> */
    public static function verificationCodes(): array
    {
        return array_filter([
            'google-site-verification' => (string) Options::get('seo', 'verification_google', ''),
            'msvalidate.01' => (string) Options::get('seo', 'verification_bing', ''),
        ]);
    }

    public static function verification(): void
    {
        foreach (self::verificationCodes() as $name => $code) {
            $code = preg_replace('/[^A-Za-z0-9_\-]/', '', $code);

            if ($code !== '') {
                echo '<meta name="' . esc_attr($name) . '" content="' . esc_attr($code) . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_attr().
            }
        }
    }

    /**
     * Extra robots.txt lines from the panel.
     *
     * @return array<int,string>
     */
    public static function robotsExtra(): array
    {
        $raw = (string) Options::get('seo', 'robots_extra', '');
        $lines = [];

        foreach (preg_split('/[\r\n]+/', $raw) ?: [] as $line) {
            $line = trim($line);

            if (! preg_match('#^(Disallow|Allow|User-agent|Sitemap|Crawl-delay):#iu', $line)) {
                continue;
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /** @param array<string,mixed> $robots */
    public static function robots(array $robots): array
    {
        $robots['noindex'] = true;
        $robots['follow'] = true;

        return $robots;
    }

    /** Blog / archive page size from the panel (default 9). */
    public static function postsPerPage(\WP_Query $query): void
    {
        if (is_admin() || ! $query->is_main_query() || $query->is_singular() || $query->is_feed()) {
            return;
        }

        if (! $query->is_home() && ! $query->is_archive() && ! $query->is_search()) {
            return;
        }

        $perPage = (int) Options::get('seo', 'posts_per_page', 9);

        if ($perPage > 0) {
            $query->set('posts_per_page', $perPage);
        }
    }
}
