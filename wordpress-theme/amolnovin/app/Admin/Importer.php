<?php

namespace App\Admin;

use App\Content\Repo;
use App\Support\Data;
use App\Support\Menus;

/**
 * Demo-content importer: turns the theme's seed data (resources/data/*.json — exported from the static HTML template)
 * into real WordPress content so that, right after activation, the site is identical to the HTML template.
 *
 *   wp amol import            create / update everything (safe to run again)
 *   wp amol import --reset    also removes WordPress' sample page & post first
 *
 * Pages, website types, projects, plugin-store items, blog posts and the five navigation menus are created;
 * existing items are matched by slug and updated.
 */
class Importer
{
    protected const DATE = '2026-10-15 08:00:00';

    /** SEO fields live in their own post meta; everything else is the JSON document `_amol_page`. */
    protected const SEO = ['title' => '_amol_seo_title', 'desc' => '_amol_seo_desc', 'h1' => '_amol_h1', 'kw' => '_amol_kw'];

    protected const TEMPLATES = ['portfolio' => 'template-portfolio.blade.php', 'store' => 'template-store.blade.php', 'sitemap' => 'template-sitemap.blade.php'];

    protected static array $ids = [];

    /** @return array<string,int> counters */
    public static function run(array $opts = []): array
    {
        $stats = ['pages' => 0, 'types' => 0, 'projects' => 0, 'products' => 0, 'posts' => 0, 'menus' => 0];

        if (! empty($opts['reset'])) {
            self::removeSamples();
        }

        if (! post_type_exists('website_type')) {
            \App\Content\PostTypes::register();
        }

        // pages first (parents before children)
        $pages = Data::seed('pages');
        usort($pages, fn ($a, $b) => substr_count($a['path'], '/') <=> substr_count($b['path'], '/'));
        $order = 0;
        foreach ($pages as $row) {
            self::importPage($row, $order++);
            $stats['pages']++;
        }

        foreach (Data::seed('types') as $row) {
            self::importEntity('website_type', $row, $stats['types']++);
        }

        foreach ((Data::seed('projects')['list'] ?? []) as $row) {
            self::importEntity('project', $row, $stats['projects']++);
        }

        foreach ((Data::seed('products')['list'] ?? []) as $row) {
            self::importProduct($row, $stats['products']++);
        }

        foreach (Data::seed('posts') as $row) {
            self::importPost($row);
            $stats['posts']++;
        }

        self::settings();
        $stats['menus'] = self::menus();

        Repo::flush();
        update_option('amol_imported', time(), false);
        delete_option('amol_show_import_notice');
        flush_rewrite_rules();
        do_action('amol/content_imported');

        return $stats;
    }

    // ---------------------------------------------------------------- helpers
    protected static function json(mixed $v): string
    {
        return wp_slash((string) wp_json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected static function find(string $type, string $slug, int $parent = 0): int
    {
        $q = get_posts([
            'post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'post_parent' => $type === 'page' ? $parent : null,
            'fields' => 'ids', 'no_found_rows' => true,
        ]);

        return $q ? (int) $q[0] : 0;
    }

    protected static function upsert(array $args, array $meta = []): int
    {
        $id = (int) ($args['ID'] ?? 0);
        $args = wp_slash($args);
        $id = $id ? wp_update_post($args, true) : wp_insert_post($args, true);

        if (is_wp_error($id)) {
            throw new \RuntimeException(esc_html($id->get_error_message()));
        }

        foreach ($meta as $k => $v) {
            if ($v === '' || $v === null) {
                delete_post_meta($id, $k);
            } else {
                update_post_meta($id, $k, $v);
            }
        }

        // pin the dates so the schema / sitemap match the static template exactly
        global $wpdb;
        $date = $args['post_date'] ?? null;
        if ($date) {
            $wpdb->update($wpdb->posts, ['post_modified' => $date, 'post_modified_gmt' => get_gmt_from_date($date)], ['ID' => $id]);
            clean_post_cache($id);
        }

        return $id;
    }

    /** Split a registry page into SEO meta + the JSON document. */
    protected static function splitPage(array $page): array
    {
        $meta = [];
        foreach (self::SEO as $key => $metaKey) {
            $meta[$metaKey] = $page[$key] ?? '';
            unset($page[$key]);
        }

        $meta['_amol_noindex'] = ! empty($page['noindex']) ? '1' : '';
        unset($page['noindex']);

        return [$page, $meta];
    }

    // ---------------------------------------------------------------- pages
    protected static function importPage(array $row, int $order): int
    {
        $page = $row['page'];
        $parentPath = $row['parent'];
        $parent = $parentPath ? (self::$ids['/' . trim($parentPath, '/') . '/'] ?? 0) : 0;
        $existing = self::find('page', $row['slug'], $parent);
        [$doc, $meta] = self::splitPage($page);

        $title = $page['short_title'] ?? wp_strip_all_tags($page['h1'] ?? $row['slug']);
        $kind = $page['kind'] ?? '';

        $meta['_amol_page'] = self::json($doc);
        $meta['_amol_imported'] = '1';
        $meta['_wp_page_template'] = self::TEMPLATES[$kind] ?? 'default';

        $id = self::upsert([
            'ID' => $existing, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $row['slug'], 'post_parent' => $parent,
            'post_excerpt' => '', 'post_content' => '', 'menu_order' => $order, 'post_date' => self::DATE, 'comment_status' => 'closed', 'ping_status' => 'closed',
        ], $meta);

        self::$ids[$row['path']] = $id;

        return $id;
    }

    // ---------------------------------------------------------------- types & projects
    protected static function importEntity(string $type, array $row, int $order): int
    {
        $e = $row['entity'];
        $page = $row['page'] ?? null;
        $existing = self::find($type, $row['slug']);
        $meta = ['_amol_entity' => self::json($e), '_amol_imported' => '1'];

        if ($page) {
            [$doc, $seo] = self::splitPage($page);
            $meta['_amol_page'] = self::json($doc);
            $meta += $seo;
        }

        $excerpt = $type === 'website_type' ? ($e['short'] ?? '') : ($e['card'] ?? '');

        $id = self::upsert([
            'ID' => $existing, 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $e['title'], 'post_name' => $row['slug'],
            'post_excerpt' => $excerpt, 'post_content' => '', 'menu_order' => $order, 'post_date' => self::DATE, 'comment_status' => 'closed',
        ], $meta);

        self::$ids[$type . ':' . $row['slug']] = $id;

        return $id;
    }

    protected static function importProduct(array $row, int $order): int
    {
        $e = $row['entity'];
        $id = self::upsert([
            'ID' => self::find('wplm_product', $row['slug']), 'post_type' => 'wplm_product', 'post_status' => 'publish', 'post_title' => $e['name'], 'post_name' => $row['slug'],
            'post_excerpt' => $e['desc'], 'post_content' => '', 'menu_order' => $order, 'post_date' => self::DATE,
        ], ['_amol_entity' => self::json($e), '_amol_imported' => '1']);

        return $id;
    }

    // ---------------------------------------------------------------- blog
    protected static function importPost(array $row): int
    {
        $e = $row['entity'];
        $page = $row['page'];

        $term = term_exists($e['cat'], 'category');
        $termId = $term ? (int) (is_array($term) ? $term['term_id'] : $term) : (int) (wp_insert_term($e['cat'], 'category')['term_id'] ?? 0);

        $store = $e;
        unset($store['body'], $store['title'], $store['excerpt'], $store['date'], $store['cat'], $store['jdate'], $store['read'], $store['words']);

        $meta = [
            '_amol_entity' => self::json($store), '_amol_imported' => '1',
            '_amol_seo_title' => $page['title'] ?? '', '_amol_seo_desc' => $e['desc'] ?? '', '_amol_kw' => $e['kw'] ?? '',
        ];

        return self::upsert([
            'ID' => self::find('post', $row['slug']), 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $e['title'], 'post_name' => $row['slug'],
            'post_excerpt' => $e['excerpt'], 'post_content' => "<!-- wp:html -->\n" . $e['body'] . "\n<!-- /wp:html -->", 'post_date' => $e['date'] . ' 08:00:00',
            'post_category' => $termId ? [$termId] : [], 'comment_status' => 'closed', 'ping_status' => 'closed',
        ], $meta);
    }

    // ---------------------------------------------------------------- settings
    protected static function settings(): void
    {
        $site = Data::seed('site')['site'] ?? [];

        update_option('blogname', $site['name'] ?? 'آمل نوین');
        update_option('blogdescription', $site['tagline'] ?? '');
        update_option('show_on_front', 'page');
        update_option('page_on_front', self::$ids['/'] ?? 0);
        update_option('page_for_posts', self::$ids['/blog-n/'] ?? 0);
        update_option('wp_page_for_privacy_policy', self::$ids['/privacy-policy/'] ?? 0);
        update_option('use_smilies', 0);
        update_option('default_comment_status', 'closed');
        update_option('default_ping_status', 'closed');
        update_option('timezone_string', 'Asia/Tehran');
        update_option('start_of_week', 6);

        if (! get_option('permalink_structure')) {
            update_option('permalink_structure', '/%postname%/');
        }
    }

    protected static function removeSamples(): void
    {
        foreach (['hello-world' => 'post', 'sample-page' => 'page'] as $slug => $type) {
            if ($id = self::find($type, $slug)) {
                wp_delete_post($id, true);
            }
        }
    }

    // ---------------------------------------------------------------- menus
    protected static function menu(string $name, string $location): int
    {
        $obj = wp_get_nav_menu_object($name);
        $menuId = $obj ? (int) $obj->term_id : (int) wp_create_nav_menu($name);

        foreach ((array) wp_get_nav_menu_items($menuId) as $old) {
            wp_delete_post($old->ID, true);
        }

        $loc = get_theme_mod('nav_menu_locations', []);
        $loc[$location] = $menuId;
        set_theme_mod('nav_menu_locations', $loc);

        return $menuId;
    }

    protected static function addItem(int $menuId, array $it, int $parent = 0): int
    {
        $args = [
            'menu-item-title' => $it['title'], 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent,
            'menu-item-classes' => implode(' ', $it['classes'] ?? []), 'menu-item-description' => $it['desc'] ?? '',
        ];

        $ref = $it['ref'] ?? '';
        if (str_starts_with($ref, '#') || $ref === '') {
            $args += ['menu-item-type' => 'custom', 'menu-item-url' => $ref ?: '#'];
        } elseif (str_contains($ref, ':')) {
            [$type, $slug] = explode(':', $ref, 2);
            $id = self::$ids[$type . ':' . $slug] ?? 0;
            $args += $id ? ['menu-item-type' => 'post_type', 'menu-item-object' => $type, 'menu-item-object-id' => $id] : ['menu-item-type' => 'custom', 'menu-item-url' => home_url('/')];
        } else {
            $id = self::$ids[$ref] ?? 0;
            $args += $id ? ['menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $id] : ['menu-item-type' => 'custom', 'menu-item-url' => home_url($ref)];
        }

        $itemId = (int) wp_update_nav_menu_item($menuId, 0, $args);

        update_post_meta($itemId, '_amol_menu_icon', $it['icon'] ?? '');
        update_post_meta($itemId, '_amol_menu_color', $it['color'] ?? '');

        foreach (($it['children'] ?? []) as $child) {
            self::addItem($menuId, $child, $itemId);
        }

        return $itemId;
    }

    protected static function menus(): int
    {
        $seed = Data::seed('site');
        $types = Data::seed('types');
        $services = array_map(fn ($s) => ['title' => $s[1], 'ref' => $s[0], 'desc' => $s[2], 'icon' => $s[3], 'color' => $s[4]], $seed['nav_services'] ?? []);
        $more = array_map(fn ($m) => ['title' => $m[1], 'ref' => $m[0], 'icon' => $m[2]], $seed['nav_more'] ?? []);
        $short = fn ($t) => str_replace(['طراحی سایت ', 'طراحی '], '', $t);

        $tree = [
            ['منوی اصلی (هدر)', 'primary_navigation', [
                ['title' => 'خدمات', 'ref' => '/services/', 'classes' => ['mega-services'], 'children' => $services],
                ['title' => 'انواع سایت', 'ref' => '/services/web-design/', 'classes' => ['mega-types'], 'children' => array_map(
                    fn ($t) => ['title' => $short($t['entity']['title']), 'ref' => 'website_type:' . $t['slug'], 'icon' => $t['entity']['icon'], 'color' => $t['entity']['color']], $types
                )],
                ['title' => 'طراحی سایت وردپرس', 'ref' => '/wordpress-web-design/'],
                ['title' => 'نمونه‌کارها', 'ref' => '/portfolio/'],
                ['title' => 'هزینه طراحی سایت', 'ref' => '/price-wordpress/'],
                ['title' => 'وبلاگ', 'ref' => '/blog-n/'],
                ['title' => 'بیشتر', 'ref' => '#', 'children' => $more],
            ]],
            ['منوی موبایل', 'mobile_navigation', [
                ['title' => 'خدمات', 'ref' => '#', 'children' => array_map(fn ($s) => ['title' => $s['title'], 'ref' => $s['ref'], 'icon' => $s['icon']], $services)],
                ['title' => 'انواع سایت', 'ref' => '#', 'children' => array_merge(
                    array_map(fn ($t) => ['title' => $t['entity']['title'], 'ref' => 'website_type:' . $t['slug'], 'icon' => $t['entity']['icon']], $types),
                    [['title' => 'مشاهده‌ی همه', 'ref' => '/services/web-design/', 'icon' => 'arrow-left', 'classes' => ['is-strong']]]
                )],
                ['title' => 'طراحی سایت وردپرس', 'ref' => '/wordpress-web-design/'], ['title' => 'نمونه‌کارها', 'ref' => '/portfolio/'],
                ['title' => 'هزینه طراحی سایت', 'ref' => '/price-wordpress/'], ['title' => 'وبلاگ', 'ref' => '/blog-n/'],
                ['title' => 'آموزش وردپرس', 'ref' => '/wordpress-learn/'], ['title' => 'فروشگاه افزونه‌ها', 'ref' => '/wplm-store/'],
                ['title' => 'درباره‌ی ما', 'ref' => '/about-us/'], ['title' => 'رزومه‌ی مجموعه', 'ref' => '/resume/'], ['title' => 'تماس با ما', 'ref' => '/contact-us/'],
            ]],
            ['فوتر: خدمات', 'footer_services', array_map(fn ($s) => ['title' => $s['title'], 'ref' => $s['ref']], $services)],
            ['فوتر: انواع سایت', 'footer_types', array_merge(
                array_map(fn ($t) => ['title' => $t['entity']['title'], 'ref' => 'website_type:' . $t['slug']], array_slice($types, 0, 10)),
                [['title' => '+ ' . (count($types) - 10) . ' نوع دیگر', 'ref' => '/services/web-design/', 'classes' => ['is-strong']]]
            )],
            ['فوتر: دسترسی سریع', 'footer_quick', array_map(fn ($a) => ['title' => $a[0], 'ref' => $a[1]], [
                ['نمونه‌کارها', '/portfolio/'], ['هزینه طراحی سایت', '/price-wordpress/'], ['طراحی سایت در آمل', '/web-design-amol/'], ['آموزش وردپرس', '/wordpress-learn/'],
                ['فروشگاه افزونه‌ها', '/wplm-store/'], ['وبلاگ', '/blog-n/'], ['درباره‌ی ما', '/about-us/'], ['رزومه‌ی مجموعه', '/resume/'], ['تماس با ما', '/contact-us/'], ['نقشه‌ی سایت', '/sitemap/'],
            ])],
        ];

        foreach ($tree as [$name, $location, $items]) {
            $menuId = self::menu($name, $location);
            foreach ($items as $it) {
                self::addItem($menuId, $it);
            }
        }

        return count($tree);
    }
}
