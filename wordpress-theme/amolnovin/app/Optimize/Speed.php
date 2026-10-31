<?php

namespace App\Optimize;

use App\Support\Options;

/**
 * «بهینه‌سازی سرعت» tab of the theme settings panel.
 *
 * Every switch maps to a native WordPress hook; nothing here touches the database or the generated HTML of a page
 * beyond removing what WordPress adds by default and the design does not use.
 */
class Speed
{
    public static function boot(): void
    {
        $o = Options::section('speed');

        if (! empty($o['strip_head_meta'])) {
            add_action('init', [self::class, 'stripHeadMeta']);
        }

        if (! empty($o['strip_head_meta'])) {
            add_action('init', [self::class, 'stripEmojis']);
        }

        if (! empty($o['disable_embeds'])) {
            add_action('init', [self::class, 'stripEmbeds']);
        }

        if (! empty($o['dequeue_block_css'])) {
            add_action('wp_enqueue_scripts', [self::class, 'dequeueBlockCss'], 100);
        }

        if (! empty($o['disable_dashicons'])) {
            add_action('wp_enqueue_scripts', [self::class, 'dequeueDashicons'], 999);
        }

        if (! empty($o['remove_query_strings'])) {
            add_filter('script_loader_src', [self::class, 'stripVersion'], 15);
            add_filter('style_loader_src', [self::class, 'stripVersion'], 15);
        }

        if (! empty($o['disable_jquery'])) {
            add_action('wp_enqueue_scripts', [self::class, 'dequeueJquery'], 1);
        }

        if (empty($o['lazy_load'])) {
            add_filter('wp_lazy_loading_enabled', '__return_false');
        }

        if (($o['heartbeat'] ?? 'slow') === 'disabled') {
            add_action('wp_enqueue_scripts', [self::class, 'disableHeartbeat'], 1);
        } elseif (($o['heartbeat'] ?? 'slow') === 'slow') {
            add_filter('heartbeat_settings', [self::class, 'slowHeartbeat']);
        }

        if ((int) ($o['revisions'] ?? 0) > 0) {
            add_filter('wp_revisions_to_keep', [self::class, 'revisions'], 10, 2);
        }

        if (empty($o['speculative'])) {
            add_filter('wp_speculation_rules_configuration', '__return_null');
        }
    }

    /** generator / RSD / WLW / shortlink / REST discovery — none of them are part of the design. */
    public static function stripHeadMeta(): void
    {
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
    }

    public static function stripEmojis(): void
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', static fn ($plugins) => array_diff((array) $plugins, ['wpemoji']));
    }

    public static function stripEmbeds(): void
    {
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('rest_api_init', 'wp_oembed_register_route');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        add_filter('embed_oembed_discover', '__return_false');
    }

    /**
     * Keep core's inline CSS out of pages that contain no real blocks (the design system styles everything).
     * Imported posts store their body as a single «Custom HTML» block — that does not need block CSS either.
     */
    public static function dequeueBlockCss(): void
    {
        $needsBlockCss = false;

        if (is_singular()) {
            $post = get_post();

            if ($post && has_blocks($post->post_content)) {
                foreach (parse_blocks($post->post_content) as $block) {
                    if (! empty($block['blockName']) && ! in_array($block['blockName'], ['core/html', 'core/freeform'], true)) {
                        $needsBlockCss = true;
                        break;
                    }
                }
            }
        }

        if ($needsBlockCss) {
            return;
        }

        foreach (['wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles', 'wp-img-auto-sizes-contain'] as $handle) {
            wp_dequeue_style($handle);
        }
    }

    /** Dashicons are an admin icon font; visitors only load it when a plugin really needs it. */
    public static function dequeueDashicons(): void
    {
        if (is_user_logged_in()) {
            return;
        }

        wp_dequeue_style('dashicons');

        if ((bool) apply_filters('amol/speed/deregister_dashicons', false)) {
            wp_deregister_style('dashicons');
        }
    }

    /** Drop ?ver= from static assets so proxies and browsers can cache them for real. */
    public static function stripVersion(string $src): string
    {
        if (is_admin() || is_user_logged_in() || ! str_contains($src, 'ver=')) {
            return $src;
        }

        return (string) remove_query_arg('ver', $src);
    }

    /** Never on by default: many plugins still expect jQuery on the front end. */
    public static function dequeueJquery(): void
    {
        if (is_admin()) {
            return;
        }

        wp_deregister_script('jquery');
        wp_deregister_script('jquery-core');
        wp_deregister_script('jquery-migrate');
    }

    public static function disableHeartbeat(): void
    {
        if (! is_admin()) {
            wp_deregister_script('heartbeat');
        }
    }

    /**
     * @param array<string,mixed> $settings
     * @return array<string,mixed>
     */
    public static function slowHeartbeat(array $settings): array
    {
        $settings['interval'] = max(60, (int) ($settings['interval'] ?? 15));

        return $settings;
    }

    public static function revisions(int $num, \WP_Post $post): int
    {
        $limit = (int) Options::get('speed', 'revisions', 5);

        return $limit > 0 ? $limit : $num;
    }

    /** <link rel="preconnect"> lines from the panel (one URL per line). */
    public static function preconnect(): array
    {
        $raw = (string) Options::get('speed', 'preconnect', '');
        $out = [];

        foreach (preg_split('/[\r\n]+/', $raw) ?: [] as $line) {
            $url = esc_url_raw(trim($line));

            if ($url !== '' && preg_match('#^https?://#i', $url)) {
                $out[] = $url;
            }
        }

        return array_values(array_unique($out));
    }

    public static function preloadFont(): bool
    {
        return (bool) Options::get('speed', 'preload_font', true);
    }
}
