<?php

namespace App\Content;

use App\Integrations\MarketManager;

/**
 * Content model of the theme.
 *
 *   page           the 20 content pages (home, services, price, about, contact, portfolio, store, blog index, sitemap …)
 *   post           blog articles
 *   website_type   the 20 «طراحی سایت برای …» pages        → /services/web-design/{slug}/
 *   project        portfolio projects (case studies)       → /projects/{slug}/
 *   wplm_product   plugin store items (admin only; listed on the store page)
 *   amol_lead      requests sent with the consultation forms (admin only)
 */
class PostTypes
{
    public static function boot(): void
    {
        add_action('init', [self::class, 'register'], 5);
        add_action('after_switch_theme', [self::class, 'flushRewrites']);
    }

    public static function flushRewrites(): void
    {
        self::register();
        flush_rewrite_rules();
    }

    public static function register(): void
    {
        $labels = fn (string $one, string $many, string $menu) => [
            'name' => $many, 'singular_name' => $one, 'menu_name' => $menu,
            'add_new' => 'افزودن ' . $one, 'add_new_item' => 'افزودن ' . $one . ' جدید', 'edit_item' => 'ویرایش ' . $one,
            'new_item' => $one . ' جدید', 'view_item' => 'مشاهده ' . $one, 'search_items' => 'جست‌وجوی ' . $many,
            'not_found' => 'موردی پیدا نشد', 'not_found_in_trash' => 'موردی در زباله‌دان نیست', 'all_items' => 'همه‌ی ' . $many,
        ];

        register_post_type('website_type', [
            'labels' => $labels('نوع سایت', 'انواع سایت', 'انواع سایت'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => ['slug' => 'services/web-design', 'with_front' => false],
            'supports' => ['title', 'editor', 'excerpt', 'page-attributes', 'revisions', 'thumbnail'],
            'menu_icon' => 'dashicons-layout',
            'menu_position' => 21,
        ]);

        register_post_type('project', [
            'labels' => $labels('نمونه‌کار', 'نمونه‌کارها', 'نمونه‌کارها'),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => ['slug' => 'projects', 'with_front' => false],
            'supports' => ['title', 'editor', 'excerpt', 'page-attributes', 'revisions', 'thumbnail'],
            'menu_icon' => 'dashicons-portfolio',
            'menu_position' => 22,
        ]);

        // When the market-manager plugin runs the store, the theme's own product list is only a fallback:
        // its admin UI is hidden so editors manage products in one place (و با خاموش‌شدن افزونه برمی‌گردد).
        $pluginStore = MarketManager::usesPlugin();

        register_post_type('wplm_product', [
            'labels' => $labels('افزونه', 'افزونه‌های فروشگاه', 'فروشگاه افزونه'),
            'public' => false,
            'show_ui' => ! $pluginStore,
            'show_in_rest' => false,
            'supports' => ['title', 'excerpt', 'page-attributes', 'thumbnail'],
            'menu_icon' => 'dashicons-admin-plugins',
            'menu_position' => 23,
        ]);

        register_post_type('amol_lead', [
            'labels' => $labels('درخواست مشاوره', 'درخواست‌های مشاوره', 'درخواست‌ها'),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => false,
            'capability_type' => 'post',
            'capabilities' => ['create_posts' => 'do_not_allow'],
            'map_meta_cap' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-email-alt',
            'menu_position' => 24,
        ]);

        // blog categories get the template's chips
        register_taxonomy_for_object_type('category', 'post');
    }
}
