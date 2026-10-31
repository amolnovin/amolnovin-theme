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

/*
 * Core block CSS on pages without real blocks and WordPress' speculative loading are switches of
 * تنظیمات قالب → بهینه‌سازی سرعت (App\Optimize\Speed), so they are no longer hard-coded here.
 */

/**
 * Header switches (تنظیمات قالب → هدر و فوتر): sticky header.
 */
add_filter('body_class', function ($classes) {
    if (! \App\Support\Options::on('header_footer', 'sticky_header')) {
        $classes[] = 'no-sticky-header';
    }

    return $classes;
});
