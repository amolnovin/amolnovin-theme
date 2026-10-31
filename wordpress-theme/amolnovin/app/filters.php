<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Excerpts.
 */
add_filter('excerpt_more', fn () => '…');
add_filter('excerpt_length', fn () => 40);

/**
 * Persian text must reach the page exactly as written: no smart quotes / dash conversion, no smilies.
 */
add_filter('run_wptexturize', '__return_false');
add_filter('pre_option_use_smilies', '__return_zero');

/**
 * Content tokens (« [[phone_display]] », « [[price_design]] » …) also work in RSS feeds and excerpts.
 */
foreach (['the_content', 'the_excerpt', 'get_the_excerpt'] as $amol_hook) {
    add_filter($amol_hook, [\App\Support\Html::class, 'tokens'], 20);
}
unset($amol_hook);

/**
 * Layout switch (Customizer → آمل نوین → چیدمان): burger menu between 1021 and 1235 px, see resources/css/06-wp.css.
 */
add_filter('body_class', function ($classes) {
    if (get_theme_mod('amol_tablet_menu', true)) {
        $classes[] = 'amol-tablet-menu';
    }

    return $classes;
});

/**
 * The theme is Persian / right-to-left by design.
 */
add_filter('language_attributes', fn () => 'lang="fa" dir="rtl"');

/**
 * Keep core's inline CSS out of pages that contain no real blocks (the design system styles everything).
 * Imported posts store their body as a single "Custom HTML" block — that does not need block CSS either.
 */
add_action('wp_enqueue_scripts', function () {
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
}, 100);

/**
 * WordPress' automatic speculative-loading script is not part of the template.
 */
add_filter('wp_speculation_rules_configuration', '__return_null');
