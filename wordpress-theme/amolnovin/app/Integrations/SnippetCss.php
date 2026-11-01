<?php

namespace App\Integrations;

use App\Support\Options;

/**
 * Custom HTML Snippets 3.0.x — keep snippet CSS inside the content area.
 *
 * The plugin prints a snippet's stylesheet as a plain `<style>` block: inline inside its
 * `.custom-snippet-container` wrapper, in `wp_head` (snippet scope = global) or as a
 * `chs-snippet-<id>-css` file when «تحویل فایلی» is on. CSS is global, so a rule such as
 * `h2{…}` or `body{background:…}` also restyles the header, the menu, the footer and every
 * other card on the page.
 *
 * This class rewrites those blocks so each selector only reaches the snippet itself:
 *
 *   h2, .card h3          →  .custom-snippet-container h2, .custom-snippet-container .card h3
 *   body .btn             →  .custom-snippet-container .btn
 *   .prose p              →  .custom-snippet-container p        (page wrappers are dropped)
 *   :root                 →  .custom-snippet-container
 *   @media (…) { … }      →  the same rewrite inside the block
 *   @keyframes / @font-face stay untouched
 *
 * Only styles that really belong to a snippet are rewritten (their text is matched against the
 * snippets stored by the plugin), so other plugins' `<style>` tags are never touched. The
 * theme's own snippet renders ({@see Snippets::render()}) are always scoped.
 */
class SnippetCss
{
    /** Selector every snippet rule is scoped to (the plugin's own wrapper). */
    public const SCOPE = '.custom-snippet-container';

    /** Page wrappers that are dropped before the scope is applied (`.prose h2` → `<scope> h2`). */
    protected const WRAPPERS = '\.(?:entry-content|prose|post-content|site-main|content|article-body)|#(?:content|primary)|main|article';

    /** @var array<int,string> id => CSS of the snippets the plugin queued as a file for this request */
    protected static array $files = [];

    /** @var array<string,bool>|null normalized CSS of every published snippet */
    protected static ?array $known = null;

    public static function enabled(): bool
    {
        return Plugins::snippets() && Options::on('integrations', 'snippets_scoped_css');
    }

    public static function boot(): void
    {
        if (! self::enabled()) {
            return;
        }

        // 1. snippets inside post/page content (the plugin expands the shortcode before this runs)
        add_filter('the_content', static fn ($html): string => is_string($html) ? self::scope($html) : (string) $html, 30);

        // 2. «تحویل فایلی»: the plugin enqueues chs-snippet-<id>-css for the whole site
        add_action('wp_enqueue_scripts', [self::class, 'collectFileAssets'], 30);
        add_action('wp_head', [self::class, 'printCollected'], 100);

        // 3. styles the plugin echoes itself (global-header snippets, delayed snippets)
        add_action('wp_head', [self::class, 'startBuffer'], 0);
        add_action('wp_head', [self::class, 'endBuffer'], PHP_INT_MAX);
    }

    /**
     * Rewrite the snippet `<style>` blocks of an HTML string.
     *
     * @param string $html  rendered content
     * @param bool   $force scope every block (used for output that is a snippet by definition)
     */
    public static function scope(string $html, bool $force = false): string
    {
        if (! self::enabled() || $html === '' || stripos($html, '<style') === false) {
            return $html;
        }

        $known = self::known();

        return (string) preg_replace_callback(
            '~<style\b([^>]*)>(.*?)</style>~is',
            static function (array $m) use ($force, $known): string {
                $css = $m[2];

                if (! $force && ! isset($known[self::normalize($css)])) {
                    return $m[0]; // someone else's stylesheet — leave it alone
                }

                $scoped = self::prefixRules($css, self::SCOPE);

                return '<style' . $m[1] . '>' . $scoped . '</style>';
            },
            $html
        );
    }

    /** @return array<string,bool> */
    protected static function known(): array
    {
        if (self::$known !== null) {
            return self::$known;
        }

        self::$known = [];

        if (! function_exists('get_posts')) {
            return self::$known;
        }

        // the plugin already knows which snippets are active (and caches the list)
        if (class_exists('AmolNovin\\CHS\\Cache') && method_exists('AmolNovin\\CHS\\Cache', 'active_ids')) {
            $ids = (array) \AmolNovin\CHS\Cache::active_ids();
        } else {
            $ids = get_posts([
                'post_type' => 'custom_snippet',
                'post_status' => 'publish',
                'numberposts' => 200,
                'fields' => 'ids',
                'no_found_rows' => true,
                'suppress_filters' => true,
                'update_post_term_cache' => false,
            ]);
        }

        foreach ((array) $ids as $id) {
            $css = trim((string) get_post_meta((int) $id, '_chs_css', true));

            if ($css !== '') {
                self::$known[self::normalize($css)] = true;
            }
        }

        return self::$known;
    }

    protected static function normalize(string $css): string
    {
        return (string) preg_replace('/\s+/', ' ', trim($css));
    }

    // ---------------------------------------------------------------- file assets (تحویل فایلی)

    public static function collectFileAssets(): void
    {
        if (! function_exists('wp_styles')) {
            return;
        }

        $styles = wp_styles();

        foreach ((array) ($styles->queue ?? []) as $handle) {
            if (! preg_match('/^chs-snippet-(\d+)-css$/', (string) $handle, $m)) {
                continue;
            }

            $id = (int) $m[1];
            $css = trim((string) get_post_meta($id, '_chs_css', true));

            wp_dequeue_style((string) $handle);

            if ($css !== '') {
                self::$files[$id] = $css;
            }
        }
    }

    public static function printCollected(): void
    {
        foreach (self::$files as $id => $css) {
            printf(
                "<style id=\"amol-snippet-%d-css\">\n%s\n</style>\n", // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is stored by the snippet plugin; only selectors are rewritten.
                (int) $id,
                self::prefixRules($css, self::SCOPE)
            );
        }

        self::$files = [];
    }

    // ---------------------------------------------------------------- head buffer

    public static function startBuffer(): void
    {
        ob_start();
    }

    public static function endBuffer(): void
    {
        $buffer = ob_get_clean();

        if (is_string($buffer) && $buffer !== '') {
            echo self::scope($buffer); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered head output, only selector prefixes are added.
        }
    }

    // ---------------------------------------------------------------- CSS rewriting

    /** Prefix every rule of a stylesheet with the snippet scope. */
    public static function prefixRules(string $css, string $scope = self::SCOPE): string
    {
        if (trim($css) === '') {
            return $css;
        }

        $out = '';
        $len = strlen($css);
        $i = 0;

        while ($i < $len) {
            $open = strpos($css, '{', $i);

            if ($open === false) {
                $out .= substr($css, $i);

                break;
            }

            $close = self::matchingBrace($css, $open);

            if ($close === false) {
                $out .= substr($css, $i); // unbalanced CSS — hand it back untouched

                break;
            }

            $prelude = substr($css, $i, $open - $i);
            $body = substr($css, $open + 1, $close - $open - 1);

            // keep at-statements without a block (@import …, @charset …) in front of the rule
            $statement = '';

            if (($semi = strrpos($prelude, ';')) !== false) {
                $statement = substr($prelude, 0, $semi + 1);
                $prelude = substr($prelude, $semi + 1);
            }

            $name = strtolower(trim((string) preg_replace('~/\*.*?\*/~s', '', $prelude)));
            $out .= $statement;

            if ($name !== '' && $name[0] === '@') {
                if (preg_match('/^@(media|supports|container|layer|document|scope)\b/', $name)) {
                    $out .= $prelude . '{' . self::prefixRules($body, $scope) . '}';
                } else {
                    $out .= $prelude . '{' . $body . '}'; // @keyframes, @font-face, @page …
                }
            } else {
                $out .= self::prefixSelectors($prelude, $scope) . '{' . $body . '}';
            }

            $i = $close + 1;
        }

        return $out;
    }

    /** @param string $css */
    protected static function matchingBrace(string $css, int $open): int|false
    {
        $depth = 0;
        $len = strlen($css);
        $quote = '';

        for ($i = $open; $i < $len; $i++) {
            $char = $css[$i];

            if ($quote !== '') {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === $quote) {
                    $quote = '';
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;

                continue;
            }

            if ($char === '/' && ($css[$i + 1] ?? '') === '*') {
                $end = strpos($css, '*/', $i + 2);
                $i = $end === false ? $len : $end + 1;

                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return false;
    }

    protected static function prefixSelectors(string $prelude, string $scope): string
    {
        // keep the indentation and the comments written in front of the rule
        $lead = '';

        while (preg_match('~^(?:\s+|/\*.*?\*/)~s', $prelude, $m)) {
            $lead .= $m[0];
            $prelude = substr($prelude, strlen($m[0]));
        }

        $tail = '';

        if (preg_match('/\s+$/', $prelude, $m)) {
            $tail = $m[0];
            $prelude = substr($prelude, 0, -strlen($tail));
        }

        $out = [];

        foreach (self::splitSelectors($prelude) as $selector) {
            $selector = trim($selector);

            if ($selector === '') {
                continue;
            }

            $out[] = self::scopeSelector($selector, $scope);
        }

        if (! $out) {
            return $lead . $prelude . $tail;
        }

        return $lead . implode(', ', $out) . $tail;
    }

    protected static function scopeSelector(string $selector, string $scope): string
    {
        $clean = trim((string) preg_replace('~/\*.*?\*/~s', '', $selector));

        // already scoped by the snippet author — leave it alone
        if ($clean === '' || strpos($clean, 'custom-snippet-container') !== false) {
            return $selector;
        }

        // html / body / :root → the snippet container itself
        if (preg_match('/^(html|body|:root)(?=[\s>+~.:#\[]|$)/i', $clean)) {
            return (string) preg_replace('/^(html|body|:root)(?=[\s>+~.:#\[]|$)/i', $scope, $selector, 1);
        }

        // a bare pseudo selector (::selection, ::before …) applies to the container
        if ($clean[0] === ':') {
            return $scope . $clean;
        }

        if ($clean === '*') {
            return $scope . ', ' . $scope . ' *';
        }

        // drop a page wrapper the snippet used to target (it now lives inside the snippet)
        $stripped = (string) preg_replace('/^(?:' . self::WRAPPERS . ')\b\s*/i', '', $clean);

        if ($stripped !== $clean && $stripped !== '') {
            if (preg_match('/^[>+~]/', $stripped)) {
                return $scope . ' ' . $stripped;
            }

            return $scope . ' ' . $stripped;
        }

        if (preg_match('/^[>+~]/', $clean)) {
            return $scope . ' ' . $clean;
        }

        return $scope . ' ' . $selector;
    }

    /** Split a selector list on commas that are not inside (), [], or quotes. @return array<int,string> */
    protected static function splitSelectors(string $prelude): array
    {
        $out = [];
        $current = '';
        $depth = 0;
        $quote = '';
        $len = strlen($prelude);

        for ($i = 0; $i < $len; $i++) {
            $char = $prelude[$i];

            if ($quote !== '') {
                $current .= $char;

                if ($char === '\\' && $i + 1 < $len) {
                    $current .= $prelude[++$i];
                } elseif ($char === $quote) {
                    $quote = '';
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                $current .= $char;

                continue;
            }

            if ($char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === ']') {
                $depth = max(0, $depth - 1);
            }

            if ($char === ',' && $depth === 0) {
                $out[] = $current;
                $current = '';

                continue;
            }

            $current .= $char;
        }

        $out[] = $current;

        return $out;
    }
}
