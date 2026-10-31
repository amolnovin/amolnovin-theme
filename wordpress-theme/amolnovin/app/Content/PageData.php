<?php

namespace App\Content;

use App\Support\Data;
use App\Support\Html;
use App\Support\Persian;
use App\Support\Site;

/**
 * Builds the `$page` array of the current request — the same keys the static template's `page` dictionary has
 * (title, desc, h1, eyebrow, lead, tldr, hero, crumbs, sections, faq, kind, path, canonical …).
 *
 * Storage: SEO fields live in their own post meta (_amol_seo_title, _amol_seo_desc, _amol_h1, _amol_kw, _amol_noindex);
 * everything else of the page definition is one JSON document in `_amol_page`, edited with the theme's data editor.
 */
class PageData
{
    protected static ?array $page = null;

    public const SEO_META = ['seo_title' => 'title', 'seo_desc' => 'desc', 'h1' => 'h1', 'kw' => 'kw'];

    public static function reset(): void
    {
        self::$page = null;
    }

    public static function current(): array
    {
        return self::$page ??= self::resolve();
    }

    // ---------------------------------------------------------------- dispatch
    protected static function resolve(): array
    {
        $obj = get_queried_object();

        if (is_404()) {
            return self::error();
        }

        if (is_search()) {
            return self::search();
        }

        if ($obj instanceof \WP_Post && (is_singular() || is_front_page() || is_home())) {
            return self::fromPost($obj);
        }

        if (is_front_page()) {
            return self::fromSeed('/');
        }

        if (is_home()) {
            return self::fromSeed('/blog-n/');
        }

        return self::archive();
    }

    public static function fromPost(\WP_Post $post): array
    {
        return match ($post->post_type) {
            'post' => self::fromBlogPost($post),
            'website_type' => self::fromType($post),
            'project' => self::fromProject($post),
            default => self::fromPage($post),
        };
    }

    // ---------------------------------------------------------------- pages
    protected static function fromPage(\WP_Post $post): array
    {
        $d = Repo::meta($post->ID, '_amol_page');

        $kind = $d['kind'] ?? 'generic';
        $d += ['kind' => $kind, 'sections' => [], 'crumbs' => null, 'priority' => 0.6, 'changefreq' => 'monthly'];

        if ($d['crumbs'] === null) {
            $d['crumbs'] = [];
            foreach (array_reverse(get_post_ancestors($post)) as $aid) {
                $d['crumbs'][] = [get_the_title($aid), Html::path((string) get_permalink($aid))];
            }
            if (! is_front_page()) {
                $d['crumbs'][] = [$post->post_title, null];
            }
        }

        if (empty($d['lead']) && $post->post_excerpt !== '') {
            $d['lead'] = $post->post_excerpt;
        }

        return self::finish($d, $post);
    }

    /** Page of the static registry by path — used when the WordPress page does not exist yet. */
    protected static function fromSeed(string $path): array
    {
        foreach (Data::seed('pages') as $row) {
            if ($row['path'] === $path) {
                $d = $row['page'];
                $d['title'] = $d['title'] ?? '';
                $d['canonical'] = home_url($path);
                $d['path'] = $path;

                return self::finishCommon($d, null);
            }
        }

        return self::error();
    }

    // ---------------------------------------------------------------- website types
    protected static function fromType(\WP_Post $post): array
    {
        $d = Repo::meta($post->ID, '_amol_page');
        $t = Repo::entity($post);
        $d += ['kind' => 'type', 'crumbs' => [['خدمات', '/services/'], ['طراحی انواع سایت', '/services/web-design/'], [$t['name'] ?? $post->post_title, null]]];
        $d['t'] = $t;

        return self::finish($d, $post);
    }

    // ---------------------------------------------------------------- projects
    protected static function fromProject(\WP_Post $post): array
    {
        $d = Repo::meta($post->ID, '_amol_page');
        $p = Repo::entity($post);
        $cats = Repo::projectCats();
        $catMap = $cats ? array_column($cats, 1, 0) : [];
        $d += [
            'kind' => 'project', 'h1_suffix' => 'مطالعه‌ی موردی',
            'crumbs' => [['نمونه‌کارها', '/portfolio/'], [$p['title'], null]],
        ];
        $d['p'] = $p;
        $d['h1'] = $d['h1'] ?? ($p['title'] . ': مطالعه‌ی موردی');
        $d['lead'] = $d['lead'] ?? ($p['overview'] ?? '');

        // previous / next / related among the projects that have a case-study page
        $detail = Repo::projectsWithDetail();
        $idx = array_search($p['slug'], array_column($detail, 'slug'), true);
        $d['prev'] = ($idx !== false && $idx > 0) ? $detail[$idx - 1] : null;
        $d['next'] = ($idx !== false && isset($detail[$idx + 1])) ? $detail[$idx + 1] : null;
        $same = array_values(array_filter($detail, fn ($q) => $q['cat'] === $p['cat'] && $q['slug'] !== $p['slug']));
        $others = array_values(array_filter($detail, fn ($q) => $q['slug'] !== $p['slug'] && $q['cat'] !== $p['cat']));
        $d['related'] = array_map(fn ($q) => $q['slug'], array_slice(array_merge($same, $others), 0, 3));

        $d['title_default'] = ($p['seo_name'] ?? $p['title']) . ' | مطالعه‌ی موردی ' . ($catMap[$p['cat']] ?? '') . ' | آمل نوین';

        return self::finish($d, $post);
    }

    // ---------------------------------------------------------------- blog posts
    protected static function fromBlogPost(\WP_Post $post): array
    {
        $p = Repo::entity($post);
        $site = Site::all();
        $blog = (int) get_option('page_for_posts');
        $blogPath = $blog ? Html::path((string) get_permalink($blog)) : '/blog-n/';

        $d = [
            'kind' => 'post', 'post' => $p, 'slug' => $p['slug'], 'priority' => 0.7, 'og' => 'post-' . $p['slug'],
            'date' => $p['date'], 'cat' => $p['cat'], 'published' => $p['date'], 'modified' => substr($post->post_modified, 0, 10),
            'title_default' => $p['title'] . ' | ' . $site['short'], 'desc' => $p['desc'], 'h1' => $p['title'], 'kw' => $p['kw'], 'kws' => [],
            'short_title' => $p['title'], 'cat_label' => 'مقاله', 'faq' => $p['faq'] ?? [],
            'crumbs' => [['وبلاگ', $blogPath], [$p['title'], null]], 'changefreq' => 'monthly',
        ];

        return self::finish($d, $post);
    }

    // ---------------------------------------------------------------- synthetic pages
    protected static function error(): array
    {
        return self::finishCommon([
            'kind' => 'error', 'noindex' => true, 'abs_links' => true, 'priority' => 0.0, 'crumbs' => [],
            'title' => 'صفحه پیدا نشد (۴۰۴) | آمل نوین', 'desc' => 'صفحه‌ی مورد نظر پیدا نشد. به صفحه‌ی اصلی آمل نوین برگردید یا با ما تماس بگیرید.',
            'h1' => 'صفحه پیدا نشد', 'path' => '/404.html', 'canonical' => home_url('/'),
        ], null);
    }

    protected static function search(): array
    {
        $q = get_search_query();

        return self::finishCommon([
            'kind' => 'search', 'noindex' => true, 'crumbs' => [['نتایج جست‌وجو', null]], 'eyebrow' => 'جست‌وجو',
            'title' => 'نتایج جست‌وجو برای «' . $q . '» | آمل نوین', 'desc' => 'نتایج جست‌وجو در وب‌سایت آمل نوین.',
            'h1' => 'نتایج جست‌وجو برای «<em>' . esc_html($q) . '</em>»', 'lead' => 'نتیجه‌ها را در زیر ببینید یا عبارت دیگری را جست‌وجو کنید.',
            'path' => '/search/', 'canonical' => home_url('/'),
        ], null);
    }

    protected static function archive(): array
    {
        $title = wp_strip_all_tags(get_the_archive_title());

        return self::finishCommon([
            'kind' => 'archive', 'crumbs' => [[$title, null]], 'eyebrow' => 'آرشیو',
            'title' => $title . ' | آمل نوین', 'desc' => wp_strip_all_tags((string) get_the_archive_description()) ?: ($title . ' در وبلاگ آمل نوین.'),
            'h1' => esc_html($title), 'lead' => wp_strip_all_tags((string) get_the_archive_description()),
            'path' => Html::path((string) (get_pagenum_link(1) ?: home_url('/'))), 'canonical' => (string) get_pagenum_link(1),
        ], null);
    }

    // ---------------------------------------------------------------- finishing
    protected static function m(\WP_Post $post, string $key): string
    {
        return (string) get_post_meta($post->ID, '_amol_' . $key, true);
    }

    protected static function finish(array $d, \WP_Post $post): array
    {
        $site = Site::all();
        $url = (string) get_permalink($post);

        $d['path'] = Html::path($url);
        $d['canonical'] = $url;

        $d['title'] = self::m($post, 'seo_title') ?: ($d['title'] ?? '') ?: ($d['title_default'] ?? '') ?: ($post->post_title . ' | ' . $site['short']);
        $d['desc'] = self::m($post, 'seo_desc') ?: ($d['desc'] ?? '') ?: wp_strip_all_tags($post->post_excerpt ?: wp_trim_words($post->post_content, 30, '…'));
        $d['h1'] = self::m($post, 'h1') ?: ($d['h1'] ?? '') ?: esc_html($post->post_title);
        $d['kw'] = self::m($post, 'kw') ?: ($d['kw'] ?? '');
        $d['noindex'] = (bool) self::m($post, 'noindex') || ! empty($d['noindex']);
        $d['published'] = $d['published'] ?? substr($post->post_date, 0, 10);
        $d['modified'] = $d['modified'] ?? substr($post->post_modified, 0, 10);
        $d['short_title'] = $d['short_title'] ?? $post->post_title;
        $d['post_id'] = $post->ID;

        return self::finishCommon($d, $post);
    }

    protected static function finishCommon(array $d, ?\WP_Post $post): array
    {
        $d += ['crumbs' => [], 'priority' => 0.6, 'changefreq' => 'monthly', 'kws' => [], 'noindex' => false, 'kind' => 'generic'];
        $d['og_image'] = self::ogImage($d, $post);

        return $d;
    }

    /** OG image: the theme's card for the page key, else the featured image, else the default card. */
    public static function ogImage(array $page, ?\WP_Post $post): string
    {
        $key = $page['og'] ?? trim(str_replace('/', '-', trim($page['path'] ?? '', '/')), '-');
        $key = $key !== '' ? $key : 'home';

        if (Html::hasImage("og/{$key}.jpg")) {
            return Html::asset("img/og/{$key}.jpg");
        }

        if ($post && has_post_thumbnail($post)) {
            return (string) wp_get_attachment_image_url((int) get_post_thumbnail_id($post), 'full');
        }

        return Html::asset('img/og/default.jpg');
    }

    /** FAQ items of all `faq` sections of a page (used to keep the FAQPage schema in sync). */
    public static function faqFromSections(array $sections): array
    {
        $items = [];

        foreach ($sections as $s) {
            if (($s['kind'] ?? '') === 'faq') {
                $items = array_merge($items, $s['items'] ?? []);
            }
        }

        return $items;
    }
}
