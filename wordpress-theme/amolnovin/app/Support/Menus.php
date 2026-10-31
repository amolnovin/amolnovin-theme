<?php

namespace App\Support;

use App\Content\Repo;

/**
 * Navigation. Items come from Appearance → Menus (locations below); when a location has no menu yet,
 * the structure of the static template is used, so the theme looks right straight after activation.
 */
class Menus
{
    public const LOCATIONS = [
        'primary_navigation' => 'منوی اصلی (هدر)',
        'mobile_navigation' => 'منوی موبایل',
        'footer_services' => 'فوتر: خدمات',
        'footer_types' => 'فوتر: انواع سایت',
        'footer_quick' => 'فوتر: دسترسی سریع',
    ];

    /** @return array<int,array> */
    public static function tree(string $location): array
    {
        static $cache = [];

        return $cache[$location] ??= self::fromWordPress($location) ?? self::defaults($location);
    }

    protected static function fromWordPress(string $location): ?array
    {
        $locations = get_nav_menu_locations();

        if (empty($locations[$location])) {
            return null;
        }

        $items = wp_get_nav_menu_items((int) $locations[$location]);

        if (! $items) {
            return null;
        }

        $nodes = [];
        foreach ($items as $it) {
            $classes = array_values(array_filter((array) $it->classes));
            $nodes[$it->ID] = [
                'id' => (int) $it->ID,
                'parent' => (int) $it->menu_item_parent,
                'title' => $it->title,
                'url' => $it->url,
                'path' => Html::path($it->url),
                'classes' => $classes,
                'icon' => (string) get_post_meta($it->ID, '_amol_menu_icon', true),
                'color' => (string) get_post_meta($it->ID, '_amol_menu_color', true),
                'desc' => (string) $it->description,
                'target' => (string) $it->target,
                'layout' => in_array('mega-services', $classes, true) ? 'services' : (in_array('mega-types', $classes, true) ? 'types' : 'menu'),
                'strong' => in_array('is-strong', $classes, true),
                'children' => [],
            ];
        }

        $root = [];
        foreach ($nodes as $id => &$n) {
            if ($n['parent'] && isset($nodes[$n['parent']])) {
                $nodes[$n['parent']]['children'][] = &$n;
            } else {
                $root[] = &$n;
            }
        }
        unset($n);

        return json_decode(wp_json_encode($root), true); // detach references
    }

    protected static function item(string $title, string $url, array $extra = []): array
    {
        return $extra + [
            'id' => 0, 'parent' => 0, 'title' => $title, 'url' => Html::url($url), 'path' => $url, 'classes' => [], 'icon' => '', 'color' => '', 'desc' => '',
            'target' => '', 'layout' => 'menu', 'strong' => false, 'children' => [],
        ];
    }

    /** The static template's menus. */
    protected static function defaults(string $location): array
    {
        $seed = Data::seed('site');
        $services = array_map(fn ($s) => self::item($s[1], $s[0], ['desc' => $s[2], 'icon' => $s[3], 'color' => $s[4]]), $seed['nav_services'] ?? []);
        $more = array_map(fn ($m) => self::item($m[1], $m[0], ['icon' => $m[2]]), $seed['nav_more'] ?? []);
        $types = Repo::types();
        $short = fn ($t) => str_replace(['طراحی سایت ', 'طراحی '], '', $t['title']);

        switch ($location) {
            case 'primary_navigation':
                return [
                    self::item('خدمات', '/services/', ['layout' => 'services', 'classes' => ['mega-services'], 'children' => $services]),
                    self::item('انواع سایت', '/services/web-design/', ['layout' => 'types', 'classes' => ['mega-types'], 'children' => array_map(
                        fn ($t) => self::item($short($t), '/services/web-design/' . $t['slug'] . '/', ['icon' => $t['icon'], 'color' => $t['color']]),
                        $types
                    )]),
                    self::item('طراحی سایت وردپرس', '/wordpress-web-design/'),
                    self::item('نمونه‌کارها', '/portfolio/'),
                    self::item('هزینه طراحی سایت', '/price-wordpress/'),
                    self::item('وبلاگ', '/blog-n/'),
                    self::item('بیشتر', '#', ['layout' => 'menu', 'children' => $more]),
                ];

            case 'mobile_navigation':
                return [
                    self::item('خدمات', '#', ['children' => array_map(fn ($s) => self::item($s['title'], $s['path'], ['icon' => $s['icon']]), $services)]),
                    self::item('انواع سایت', '#', ['children' => array_merge(array_map(
                        fn ($t) => self::item($t['title'], '/services/web-design/' . $t['slug'] . '/', ['icon' => $t['icon']]),
                        $types
                    ), [self::item('مشاهده‌ی همه', '/services/web-design/', ['strong' => true, 'icon' => 'arrow-left'])])]),
                    self::item('طراحی سایت وردپرس', '/wordpress-web-design/'),
                    self::item('نمونه‌کارها', '/portfolio/'),
                    self::item('هزینه طراحی سایت', '/price-wordpress/'),
                    self::item('وبلاگ', '/blog-n/'),
                    self::item('آموزش وردپرس', '/wordpress-learn/'),
                    self::item('فروشگاه افزونه‌ها', '/wplm-store/'),
                    self::item('درباره‌ی ما', '/about-us/'),
                    self::item('رزومه‌ی مجموعه', '/resume/'),
                    self::item('تماس با ما', '/contact-us/'),
                ];

            case 'footer_services':
                return array_map(fn ($s) => self::item($s['title'], $s['path']), $services);

            case 'footer_types':
                $first = array_map(fn ($t) => self::item($t['title'], '/services/web-design/' . $t['slug'] . '/'), array_slice($types, 0, 10));
                $first[] = self::item('+ ' . (count($types) - 10) . ' نوع دیگر', '/services/web-design/', ['strong' => true]);

                return $first;

            case 'footer_quick':
                return array_map(fn ($a) => self::item($a[0], $a[1]), [
                    ['نمونه‌کارها', '/portfolio/'], ['هزینه طراحی سایت', '/price-wordpress/'], ['طراحی سایت در آمل', '/web-design-amol/'],
                    ['آموزش وردپرس', '/wordpress-learn/'], ['فروشگاه افزونه‌ها', '/wplm-store/'], ['وبلاگ', '/blog-n/'], ['درباره‌ی ما', '/about-us/'],
                    ['رزومه‌ی مجموعه', '/resume/'], ['تماس با ما', '/contact-us/'], ['نقشه‌ی سایت', '/sitemap/'],
                ]);
        }

        return [];
    }

    /** Is a nav link "current"? Same rules as the static template (portfolio also covers projects, blog also covers posts). */
    public static function isCurrent(array $item, array $page): bool
    {
        $p = $item['path'] ?? '';
        $cur = $page['path'] ?? '';

        if ($p === '' || $p === '#') {
            return false;
        }

        if (str_ends_with($p, '/portfolio/')) {
            return str_starts_with($cur, '/portfolio/') || str_starts_with($cur, '/projects/');
        }

        if (($page['kind'] ?? '') === 'blog' || ($page['kind'] ?? '') === 'post') {
            $blog = get_option('page_for_posts') ? Html::path((string) get_permalink((int) get_option('page_for_posts'))) : '/blog-n/';

            return $p === $blog;
        }

        return $p === $cur;
    }
}
