<?php

namespace App\Support;

/**
 * Inline SVG icon sprite (Lucide + Simple Icons): every page only ships the symbols it actually uses.
 */
class Icons
{
    public static function all(): array
    {
        static $icons = null;

        return $icons ??= Data::seed('icons');
    }

    public static function svg(string $name, string $class = ''): string
    {
        $icons = self::all();

        if (! isset($icons[$name])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[amolnovin] missing icon: {$name}"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- only when WP_DEBUG is on.
            }

            return '';
        }

        $c = 'i' . (! empty($icons[$name]['brand']) ? ' i--brand' : '') . ($class !== '' ? ' ' . $class : '');

        return '<svg class="' . esc_attr($c) . '" aria-hidden="true" focusable="false"><use href="#i-' . esc_attr($name) . '"/></svg>';
    }

    /** Replace the <!--SPRITE--> placeholder by an <svg> holding only the used symbols. */
    public static function sprite(string $html): string
    {
        if (! str_contains($html, '<!--SPRITE-->')) {
            return $html;
        }

        preg_match_all('/href="#i-([a-z0-9-]+)"/', $html, $m);
        $names = array_unique($m[1]);
        sort($names);
        $icons = self::all();
        $syms = '';

        foreach ($names as $n) {
            if (isset($icons[$n])) {
                $syms .= '<symbol id="i-' . $n . '" viewBox="' . $icons[$n]['vb'] . '">' . $icons[$n]['body'] . '</symbol>';
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true" focusable="false"><defs>' . $syms . '</defs></svg>';

        return str_replace('<!--SPRITE-->', $svg, $html);
    }
}
