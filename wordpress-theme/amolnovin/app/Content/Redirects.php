<?php

namespace App\Content;

/**
 * 301 redirects of the old WordPress URLs (what the static template did in .htaccess) — works on any web server.
 * Extend with the `amol/redirects` filter: [ '/old-path/' => '/new-path/' ].
 */
class Redirects
{
    public static function map(): array
    {
        return (array) apply_filters('amol/redirects', [
            '/طراحی-سایت-شرکتی-در-آمل-نوین/' => '/services/web-design/corporate/',
            '/طراحی-سایت-فروشگاهی-در-آمل/' => '/services/web-design/ecommerce/',
            '/طراحی-سایت-املاک-در-آمل-نوین/' => '/services/web-design/real-estate/',
            '/طراحی-سایت-وکالت-در-آمل-نوین/' => '/services/web-design/lawyer/',
            '/projects/' => '/portfolio/',
            '/projects/فروشگاه-رنگ-صادقی/' => '/projects/rang-sadeghi/',
            '/projects/فروشگاه-گل-و-گیاه-مانلی/' => '/projects/maneli-plants/',
            '/feed.xml' => '/feed/',
        ]);
    }

    public static function boot(): void
    {
        add_action('template_redirect', [self::class, 'run'], 0);
    }

    public static function run(): void
    {
        $path = rawurldecode((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
        $home = rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
        $rel = '/' . ltrim(substr($path, strlen($home)), '/');
        $key = str_ends_with($rel, '.xml') ? $rel : trailingslashit($rel);

        $map = self::map();

        if (isset($map[$key])) {
            wp_safe_redirect(home_url($map[$key]), 301);
            exit;
        }
    }
}
