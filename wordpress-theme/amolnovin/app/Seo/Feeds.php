<?php

namespace App\Seo;

use App\Content\PageData;
use App\Content\Repo;
use App\Optimize\Seo;
use App\Support\Data;
use App\Support\Html;
use App\Support\Menus;
use App\Support\Persian;
use App\Support\Site;

/**
 * Virtual files of the static template: /sitemap.xml, /llms.txt, /manifest.webmanifest, robots.txt
 * and the client-side search index (search-index.js, regenerated whenever content changes).
 */
class Feeds
{
    public static function boot(): void
    {
        add_action('parse_request', [self::class, 'serve'], 0);
        add_filter('robots_txt', [self::class, 'robots'], 20, 2);

        foreach (['save_post', 'deleted_post', 'trashed_post', 'untrashed_post', 'wp_update_nav_menu', 'customize_save_after', 'switch_theme', 'amol/content_imported'] as $hook) {
            add_action($hook, [self::class, 'invalidate']);
        }

        add_action('amol/rebuild_search_index', [self::class, 'writeSearchIndex']);
    }

    // ---------------------------------------------------------------- routing
    public static function serve(\WP $wp): void
    {
        $file = trim((string) $wp->request, '/');

        switch ($file) {
            case 'sitemap.xml':
                if (Seo::enabled() && Seo::sitemap()) {
                    self::send('application/xml; charset=UTF-8', self::sitemap());
                }

                break;

            case 'llms.txt':
                if (Seo::enabled() && Seo::llms()) {
                    self::send('text/plain; charset=UTF-8', self::llms());
                }

                break;

            case 'manifest.webmanifest':
                if (Seo::enabled() && Seo::manifest()) {
                    self::send('application/manifest+json; charset=UTF-8', self::manifest());
                }

                break;
        }
    }

    protected static function send(string $type, string $body): void
    {
        status_header(200);
        header('Content-Type: ' . $type);
        header('Cache-Control: public, max-age=3600');
        echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML / JSON / plain-text document assembled from escaped parts.
        exit;
    }

    // ---------------------------------------------------------------- indexable URLs
    /** @return array<int,array{path:string,url:string,title:string,desc:string,kw:string,kws:array,cat:string,priority:float,changefreq:string,modified:string,kind:string}> */
    public static function entries(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $kindLabel = ['home' => 'صفحه', 'service' => 'خدمت', 'type' => 'نوع سایت', 'project' => 'نمونه‌کار', 'post' => 'مقاله', 'generic' => 'صفحه'];
        $posts = get_posts([
            'post_type' => ['page', 'website_type', 'project', 'post'], 'post_status' => 'publish', 'numberposts' => -1, 'no_found_rows' => true,
            'orderby' => ['type' => 'ASC', 'menu_order' => 'ASC', 'date' => 'DESC'],
        ]);

        $weight = ['page' => 0, 'website_type' => 1, 'project' => 2, 'post' => 3];
        usort($posts, fn ($a, $b) => ($weight[$a->post_type] <=> $weight[$b->post_type]) ?: ($a->menu_order <=> $b->menu_order) ?: ($b->post_date <=> $a->post_date));

        $out = [];
        foreach ($posts as $p) {
            if ($p->post_type === 'project') {
                $e = Repo::entity($p);
                if (empty($e['detail'])) {
                    continue;
                }
            }

            $page = PageData::fromPost($p);

            if (! empty($page['noindex'])) {
                continue;
            }

            $title = ! empty($page['short_title']) ? $page['short_title'] : ($page['h1'] ? wp_strip_all_tags($page['h1']) : $page['title']);
            $kind = $page['kind'] ?? 'generic';
            $out[] = [
                'path' => $page['path'], 'url' => $page['canonical'], 'title' => $title, 'desc' => $page['desc'], 'kw' => $page['kw'] ?? '',
                'kws' => $page['kws'] ?? [], 'cat' => $page['cat_label'] ?? ($kindLabel[$kind] ?? 'صفحه'), 'priority' => (float) ($page['priority'] ?? 0.6),
                'changefreq' => $page['changefreq'] ?? 'monthly', 'modified' => $page['modified'] ?? substr($p->post_modified, 0, 10), 'kind' => $kind,
            ];
        }

        return $cache = $out;
    }

    // ---------------------------------------------------------------- sitemap.xml
    protected static function priority(mixed $p): string
    {
        $s = rtrim(number_format((float) $p, 2, '.', ''), '0');

        return str_ends_with($s, '.') ? $s . '0' : $s;
    }

    public static function sitemap(): string
    {
        $urls = [];
        foreach (self::entries() as $e) {
            $loc = esc_xml($e['url']);
            $urls[] = "<url><loc>{$loc}</loc><lastmod>{$e['modified']}</lastmod><changefreq>{$e['changefreq']}</changefreq><priority>" . self::priority($e['priority']) . "</priority>"
                . "<xhtml:link rel=\"alternate\" hreflang=\"fa-IR\" href=\"{$loc}\"/></url>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n"
            . implode("\n", $urls) . "\n</urlset>\n";
    }

    // ---------------------------------------------------------------- robots.txt
    public static function robots(string $output, bool $public): string
    {
        if (! $public) {
            return $output;
        }

        $s = Site::all();
        $admin = wp_parse_url(admin_url('admin-ajax.php'), PHP_URL_PATH);
        $extra = Seo::robotsExtra();
        $extra = $extra ? "\n# extra rules — تنظیمات قالب → سئو\n" . implode("\n", $extra) . "\n" : '';

        if (Seo::siteNoindex()) {
            return "# {$s['name']} — robots.txt (حالت پیش‌نمایش: noindex)\nUser-agent: *\nDisallow: /\n" . $extra;
        }

        return "# {$s['name']} — robots.txt\n"
            . "User-agent: *\nAllow: /\nDisallow: /wp-admin/\nAllow: {$admin}\nDisallow: /*?s=\nDisallow: /*?replytocom=\n"
            . $extra . "\n"
            . "# AI / answer-engine crawlers are welcome to read the public content\n"
            . "User-agent: GPTBot\nAllow: /\nUser-agent: OAI-SearchBot\nAllow: /\nUser-agent: PerplexityBot\nAllow: /\nUser-agent: Google-Extended\nAllow: /\n\n"
            . (Seo::enabled() && Seo::sitemap() ? "Sitemap: {$s['url']}/sitemap.xml\n" : '');
    }

    // ---------------------------------------------------------------- llms.txt
    public static function llms(): string
    {
        $s = Site::all();
        $seed = Data::seed('site');
        $U = $s['url'];
        $lines = [
            "# {$s['name']} ({$s['en_name']})", '',
            "> {$s['name']} — طراحی سایت وردپرس و اختصاصی، پشتیبانی سایت، سئو و تولید محتوا، امنیت و سرعت سایت در آمل، مازندران؛ فعال از سال {$s['founded_j']} با پروانه‌ی کسب رسمی.", '',
            '## اطلاعات کلیدی',
            "- نشانی: {$s['address']}", "- تلفن و واتساپ: {$s['phone_display']}", "- ایمیل: {$s['email']}",
            "- قیمت از: طراحی سایت {$s['price_design']} میلیون تومان؛ پشتیبانی {$s['price_support']} میلیون تومان در ماه؛ سئو {$s['price_seo']} میلیون تومان در ماه",
            '- زمان تحویل: سایت شرکتی ۱۰ تا ۲۰ روز کاری؛ فروشگاهی ۲۰ تا ۴۵ روز کاری', "- پشتیبانی: {$s['support_months']} ماه پشتیبانی فنی رایگان با قرارداد کتبی", '',
            '## خدمات',
        ];

        foreach ($seed['nav_services'] ?? [] as [$u, $n, $d]) {
            $lines[] = "- [{$n}]({$U}{$u}): {$d}";
        }

        $lines[] = '';
        $lines[] = '## انواع سایت';
        foreach (Repo::types() as $t) {
            $lines[] = "- [{$t['title']}]({$U}/services/web-design/{$t['slug']}/): {$t['short']}";
        }

        $lines[] = '';
        $lines[] = '## صفحات مهم';
        foreach ([['نمونه‌کارها', '/portfolio/'], ['هزینه طراحی سایت', '/price-wordpress/'], ['طراحی سایت در آمل', '/web-design-amol/'], ['فروشگاه افزونه‌ها', '/wplm-store/'], ['وبلاگ', '/blog-n/'], ['تماس با ما', '/contact-us/']] as [$n, $u]) {
            $lines[] = "- [{$n}]({$U}{$u})";
        }
        $lines[] = '';

        $txt = implode("\n", $lines);

        return implode('', array_map(
            fn ($seg) => str_starts_with($seg, 'http') ? $seg : Persian::fa($seg),
            preg_split('/(https?:\/\/[^\s)]+)/', $txt, -1, PREG_SPLIT_DELIM_CAPTURE)
        ));
    }

    // ---------------------------------------------------------------- manifest
    public static function manifest(): string
    {
        $s = Site::all();

        return (string) wp_json_encode([
            'name' => $s['name'], 'short_name' => $s['short'], 'description' => $s['tagline'], 'lang' => 'fa', 'dir' => 'rtl', 'start_url' => '/', 'scope' => '/',
            'display' => 'standalone', 'background_color' => '#ffffff', 'theme_color' => $s['theme_color'],
            'icons' => [
                ['src' => Html::asset('img/brand/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => Html::asset('img/brand/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => Html::asset('img/brand/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    // ---------------------------------------------------------------- client-side search index
    protected static function indexFile(): string
    {
        $u = wp_upload_dir();

        return trailingslashit($u['basedir']) . 'amolnovin/search-index.js';
    }

    public static function invalidate(): void
    {
        wp_delete_file(self::indexFile());
    }

    public static function writeSearchIndex(): string
    {
        $items = [];
        foreach (self::entries() as $e) {
            $items[] = Html::tokens(['t' => $e['title'], 'u' => $e['path'], 'd' => $e['desc'], 'k' => trim($e['kw'] . ' ' . implode(' ', $e['kws'])), 'c' => $e['cat']]);
        }

        $js = 'window.AVN_INDEX=' . wp_json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
        $file = self::indexFile();
        wp_mkdir_p(dirname($file));
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- static asset written to uploads.
        file_put_contents($file, $js);

        return $file;
    }

    /** URL of search-index.js (built on first use). */
    public static function searchIndexUrl(): string
    {
        $file = self::indexFile();

        // rebuilt when missing, or when the theme was updated after the file was written
        if (! is_readable($file) || filemtime($file) < (int) filemtime(get_theme_file_path('style.css'))) {
            self::writeSearchIndex();
        }

        $u = wp_upload_dir();

        return trailingslashit($u['baseurl']) . 'amolnovin/search-index.js?ver=' . (is_readable($file) ? filemtime($file) : time());
    }
}
