<?php

namespace App\Integrations;

use App\Support\Options;

/**
 * Custom HTML Snippets (آمل نوین) integration.
 *
 * The plugin renders a snippet with `[my_snippet id="123"]`; the theme
 *   • keeps that shortcode working inside post/page content, excerpts and page sections,
 *   • offers a native «snippet» page section (Data editor → «قطعه کد سفارشی»),
 *   • lets the blog archive show an intro/outro snippet (تنظیمات قالب → افزونه‌ها).
 */
class Snippets
{
    public static function active(): bool
    {
        return Plugins::snippets() && Options::on('integrations', 'snippets');
    }

    public static function boot(): void
    {
        if (! Plugins::snippets()) {
            return;
        }

        // shortcodes inside excerpts (post cards, search results, RSS)
        add_filter('get_the_excerpt', 'do_shortcode', 9, 1);
        add_filter('the_excerpt', 'do_shortcode', 9, 1);

        // the plugin renders its own assets; make sure the theme never strips its shortcode from the loop
        add_filter('widget_text', 'do_shortcode', 11, 1);
    }

    /**
     * Render a snippet by post ID. Returns an empty string when the plugin is missing, the snippet was deleted
     * or the current user does not pass the snippet's own display conditions (the plugin decides).
     */
    public static function render(int|string $id, array $atts = []): string
    {
        $id = absint($id);

        if (! $id || ! self::active() || ! shortcode_exists('my_snippet')) {
            return '';
        }

        $args = ['id' => (string) $id];

        foreach ($atts as $key => $value) {
            if (is_scalar($value)) {
                $args[$key] = (string) $value;
            }
        }

        $shortcode = '[my_snippet ' . self::attributes($args) . ']';
        $html = do_shortcode($shortcode);

        return is_string($html) ? $html : '';
    }

    /** Render a snippet chosen in the settings panel (0 = nothing). */
    public static function renderOption(string $key): string
    {
        return self::render((int) Options::get('integrations', $key, 0));
    }

    /**
     * Snippets available for the settings panel dropdown.
     *
     * @return array<int,string> id => title
     */
    public static function choices(): array
    {
        if (! Plugins::snippets()) {
            return [];
        }

        $posts = get_posts([
            'post_type' => 'custom_snippet',
            'post_status' => 'publish',
            'numberposts' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ]);

        $out = [];

        foreach ($posts as $post) {
            $out[(int) $post->ID] = $post->post_title !== '' ? $post->post_title : ('#' . $post->ID);
        }

        return $out;
    }

    /** @param array<string,string> $atts */
    protected static function attributes(array $atts): string
    {
        $out = [];

        foreach ($atts as $key => $value) {
            $out[] = $key . '="' . esc_attr($value) . '"';
        }

        return implode(' ', $out);
    }
}
