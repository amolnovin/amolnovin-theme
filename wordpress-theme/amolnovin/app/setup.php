<?php

/**
 * Theme setup.
 */

namespace App;

use App\Support\Menus;
use Illuminate\Support\Facades\Vite;

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_action('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    if (! Vite::isRunningHot()) {
        $dependencies = json_decode(Vite::content('editor.deps.json'));

        foreach ($dependencies as $dependency) {
            if (! wp_script_is($dependency)) {
                wp_enqueue_script($dependency);
            }
        }
    }
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Vite returns trusted <script>/<link> markup.
    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Disable on-demand block asset loading.
 *
 * @link https://core.trac.wordpress.org/ticket/61965
 */
add_filter('should_load_separate_core_block_assets', '__return_false');

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus (Appearance → Menus).
     */
    register_nav_menus(Menus::LOCATIONS);

    /**
     * Disable the default block patterns.
     */
    remove_theme_support('core-block-patterns');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('automatic-feed-links');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_theme_support('custom-logo', ['height' => 84, 'width' => 84, 'flex-width' => true, 'flex-height' => true]);
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     */
    add_theme_support('customize-selective-refresh-widgets');

    // image sizes used by featured images of posts / projects
    add_image_size('amol-card', 900, 506, true);
}, 20);

/*
 * A lean <head> (no generator, emojis, oEmbed discovery …) is one of the switches of
 * تنظیمات قالب → بهینه‌سازی سرعت and lives in App\Optimize\Speed.
 */
