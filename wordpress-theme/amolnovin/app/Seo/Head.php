<?php

namespace App\Seo;

use App\Content\PageData;
use App\Optimize\Seo;
use App\Support\Html;
use App\Support\Site;

/**
 * <head> SEO output: title, description, robots, canonical, hreflang, Open Graph, Twitter cards, geo tags, icons, JSON-LD.
 * Switches itself off when a dedicated SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress, The SEO Framework) is active.
 */
class Head
{
    public static function boot(): void
    {
        if (! Seo::enabled() || self::pluginActive()) {
            return;
        }

        add_filter('pre_get_document_title', [self::class, 'title'], 20);
        add_action('wp_head', [self::class, 'meta'], 2);
        add_action('wp_head', [self::class, 'jsonLd'], 100);

        // WordPress' own equivalents are replaced by the output above
        remove_action('wp_head', 'rel_canonical');
        remove_action('wp_head', 'wp_robots', 1);
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'wp_site_icon', 99);
        remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        add_filter('wp_robots', '__return_empty_array', 99);
    }

    public static function pluginActive(): bool
    {
        $active = defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION');

        return (bool) apply_filters('amol/seo/external_plugin_active', $active);
    }

    public static function title(string $title = ''): string
    {
        // feeds keep WordPress' own channel title
        return is_feed() ? $title : PageData::current()['title'];
    }

    public static function meta(): void
    {
        $p = PageData::current();
        $s = Site::all();
        $canonical = $p['canonical'];
        $og = $p['og_image'];
        $e = fn ($v) => esc_attr((string) $v);
        $noindex = ! empty($p['noindex']) || Seo::siteNoindex();
        $robots = $noindex ? 'noindex,follow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
        $ogTitle = $p['og_title'] ?? $p['title'];
        $post = ($p['kind'] ?? '') === 'post';

        $o = [];
        $o[] = '<meta name="description" content="' . $e($p['desc']) . '">';
        $o[] = '<meta name="robots" content="' . $e($robots) . '">';
        $stable = ! in_array($p['kind'] ?? '', ['error', 'search'], true);
        if ($stable) {
            $o[] = '<link rel="canonical" href="' . esc_url($canonical) . '">';
            $o[] = '<link rel="alternate" hreflang="fa-IR" href="' . esc_url($canonical) . '">';
            $o[] = '<link rel="alternate" hreflang="x-default" href="' . esc_url($canonical) . '">';
        }
        $o[] = '<meta name="author" content="' . $e($s['name']) . '">';
        $o[] = '<meta name="theme-color" content="#272869" media="(prefers-color-scheme: light)">';
        $o[] = '<meta name="theme-color" content="#080a24" media="(prefers-color-scheme: dark)">';
        $o[] = '<meta name="color-scheme" content="light dark">';
        $o[] = '<meta name="format-detection" content="telephone=yes">';
        $o[] = '<meta name="geo.region" content="IR-MZ">';
        $o[] = '<meta name="geo.placename" content="' . $e($s['city']) . '">';
        $o[] = '<meta name="geo.position" content="' . $s['geo'][0] . ';' . $s['geo'][1] . '">';
        $o[] = '<meta name="ICBM" content="' . $s['geo'][0] . ', ' . $s['geo'][1] . '">';
        $o[] = '<meta property="og:locale" content="' . $e($s['locale']) . '">';
        $o[] = '<meta property="og:site_name" content="' . $e($s['name']) . '">';
        $o[] = '<meta property="og:type" content="' . ($post ? 'article' : 'website') . '">';
        $o[] = '<meta property="og:title" content="' . $e($ogTitle) . '">';
        $o[] = '<meta property="og:description" content="' . $e($p['desc']) . '">';
        if ($stable) {
            $o[] = '<meta property="og:url" content="' . esc_url($canonical) . '">';
        }
        $o[] = '<meta property="og:image" content="' . esc_url($og) . '">';
        $o[] = '<meta property="og:image:width" content="1200">';
        $o[] = '<meta property="og:image:height" content="630">';
        $o[] = '<meta property="og:image:alt" content="' . $e($p['og_alt'] ?? $p['title']) . '">';

        if ($post) {
            $o[] = '<meta property="article:published_time" content="' . $e($p['date']) . '">';
            $o[] = '<meta property="article:modified_time" content="' . $e($p['date']) . '">';
            $o[] = '<meta property="article:section" content="' . $e($p['cat']) . '">';
        }

        $twitter = ltrim((string) \App\Support\Options::get('seo', 'twitter', ''), '@@');
        if ($twitter !== '') {
            $o[] = '<meta name="twitter:site" content="@' . $e($twitter) . '">';
        }
        $o[] = '<meta name="twitter:card" content="summary_large_image">';
        $o[] = '<meta name="twitter:title" content="' . $e($ogTitle) . '">';
        $o[] = '<meta name="twitter:description" content="' . $e($p['desc']) . '">';
        $o[] = '<meta name="twitter:image" content="' . esc_url($og) . '">';

        if (has_site_icon()) {
            $o[] = '<link rel="icon" href="' . esc_url(get_site_icon_url(32)) . '" sizes="32x32">';
            $o[] = '<link rel="icon" href="' . esc_url(get_site_icon_url(192)) . '" sizes="192x192">';
            $o[] = '<link rel="apple-touch-icon" href="' . esc_url(get_site_icon_url(180)) . '">';
        } else {
            $o[] = '<link rel="icon" href="' . esc_url(Html::asset('img/brand/favicon.svg')) . '" type="image/svg+xml">';
            $o[] = '<link rel="icon" href="' . esc_url(Html::asset('img/brand/favicon.ico')) . '" sizes="48x48">';
            $o[] = '<link rel="icon" href="' . esc_url(Html::asset('img/brand/favicon-32.png')) . '" sizes="32x32" type="image/png">';
            $o[] = '<link rel="apple-touch-icon" href="' . esc_url(Html::asset('img/brand/apple-touch-icon.png')) . '">';
        }

        $o[] = '<link rel="manifest" href="' . esc_url(home_url('/manifest.webmanifest')) . '">';
        $o[] = '<link rel="alternate" type="application/rss+xml" title="' . $e('وبلاگ ' . $s['short']) . '" href="' . esc_url(get_feed_link()) . '">';
        $o[] = '<link rel="sitemap" type="application/xml" href="' . esc_url(home_url('/sitemap.xml')) . '">';

        echo implode("\n", $o) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every tag above is built with esc_attr()/esc_url().
    }

    public static function jsonLd(): void
    {
        $p = PageData::current();
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD is encoded by Schema::json() (<, > and & are \u-escaped).
        echo '<script type="application/ld+json">' . Schema::json(Schema::graph($p, $p['canonical'], $p['og_image'])) . "</script>\n";
    }
}
