<?php

namespace App\Content;

use App\Support\Data;
use App\Support\Persian;

/**
 * Data access. Every method returns the SAME array shapes the static template's Jinja files read
 * (TYPES, PROJECTS, PRODUCTS, POSTS …) — but sourced from WordPress posts, with the exported seed data as fallback
 * until the demo content has been imported.
 */
class Repo
{
    protected static array $c = [];

    public static function flush(): void
    {
        self::$c = [];
    }

    // ---------------------------------------------------------------- website types
    public static function types(): array
    {
        return self::$c['types'] ??= self::collect('website_type', fn () => array_column(Data::seed('types'), 'entity'));
    }

    public static function typeBy(): array
    {
        return self::$c['typeBy'] ??= array_column(self::types(), null, 'slug');
    }

    // ---------------------------------------------------------------- projects
    public static function projects(): array
    {
        return self::$c['projects'] ??= self::collect('project', fn () => array_column(Data::seed('projects')['list'] ?? [], 'entity'));
    }

    public static function projectBy(): array
    {
        return self::$c['projectBy'] ??= array_column(self::projects(), null, 'slug');
    }

    /** Projects that have their own case-study page. */
    public static function projectsWithDetail(): array
    {
        return self::$c['detail'] ??= array_values(array_filter(self::projects(), fn ($p) => ! empty($p['detail'])));
    }

    public static function projectCats(): array
    {
        return Data::seed('projects')['cats'] ?? [];
    }

    public static function projectServices(): array
    {
        return Data::seed('projects')['services'] ?? [];
    }

    // ---------------------------------------------------------------- store
    public static function products(): array
    {
        return self::$c['products'] ??= self::collect('wplm_product', fn () => array_column(Data::seed('products')['list'] ?? [], 'entity'));
    }

    public static function productBy(): array
    {
        return self::$c['productBy'] ??= array_column(self::products(), null, 'slug');
    }

    public static function storeCats(): array
    {
        return Data::seed('products')['cats'] ?? [];
    }

    public static function storeUrl(): string
    {
        return (string) (Data::seed('products')['store_url'] ?? '');
    }

    // ---------------------------------------------------------------- blog
    /** Newest first. */
    public static function posts(): array
    {
        return self::$c['posts'] ??= (function () {
            $posts = get_posts([
                'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC',
                'ignore_sticky_posts' => true, 'no_found_rows' => true,
            ]);

            if (! $posts) {
                $list = array_map(fn ($x) => self::shapePost($x['entity']), Data::seed('posts'));
                usort($list, fn ($a, $b) => strcmp($b['date'], $a['date']));

                return $list;
            }

            return array_map([self::class, 'entity'], $posts);
        })();
    }

    /**
     * Categories of the blog archive, taken from WordPress (only categories that have published posts).
     *
     * @return array<int,array{slug:string,name:string,count:int}>
     */
    public static function postCategories(): array
    {
        $terms = get_categories([
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 12,
        ]);

        $out = [];

        foreach ($terms as $term) {
            $out[] = ['slug' => $term->slug, 'name' => $term->name, 'count' => (int) $term->count];
        }

        return $out;
    }

    public static function postBy(): array
    {
        return self::$c['postBy'] ??= array_column(self::posts(), null, 'slug');
    }

    // ---------------------------------------------------------------- generic
    protected static function collect(string $postType, callable $seed): array
    {
        $posts = get_posts([
            'post_type' => $postType, 'post_status' => 'publish', 'numberposts' => -1,
            'orderby' => ['menu_order' => 'ASC', 'ID' => 'ASC'], 'no_found_rows' => true,
        ]);

        return $posts ? array_map([self::class, 'entity'], $posts) : $seed();
    }

    public static function meta(int $id, string $key): array
    {
        $raw = get_post_meta($id, $key, true);

        if (is_array($raw)) {
            return $raw;
        }

        return is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
    }

    /** WP post -> entity array (native fields win over the stored JSON so the normal WP editor works). */
    public static function entity(\WP_Post $p): array
    {
        $e = self::meta($p->ID, '_amol_entity');
        $e['slug'] = $p->post_name;
        $e['_id'] = $p->ID;
        $e['_url'] = (string) get_permalink($p);

        if (has_post_thumbnail($p)) {
            $e['_thumb'] = (string) wp_get_attachment_image_url((int) get_post_thumbnail_id($p), 'large');
        }

        switch ($p->post_type) {
            case 'website_type':
                $e['title'] = $p->post_title;
                if ($p->post_excerpt !== '') {
                    $e['short'] = $p->post_excerpt;
                }
                break;

            case 'project':
                $e['title'] = $p->post_title;
                if ($p->post_excerpt !== '') {
                    $e['card'] = $p->post_excerpt;
                }
                break;

            case 'wplm_product':
                $e['name'] = $p->post_title;
                if ($p->post_excerpt !== '') {
                    $e['desc'] = $p->post_excerpt;
                }
                break;

            case 'post':
                $e['title'] = $p->post_title;
                $e['excerpt'] = $p->post_excerpt !== '' ? $p->post_excerpt : wp_trim_words(wp_strip_all_tags($p->post_content), 30, '…');
                $e['date'] = substr($p->post_date, 0, 10);
                $cats = get_the_category($p->ID);
                $e['cat'] = $cats ? $cats[0]->name : ($e['cat'] ?? 'عمومی');
                $e['cat_slug'] = $cats ? $cats[0]->slug : 'uncategorized';
                $e['body'] = $p->post_content;
                $seoDesc = (string) get_post_meta($p->ID, '_amol_seo_desc', true);
                $e['desc'] = $seoDesc !== '' ? $seoDesc : ($e['desc'] ?? $e['excerpt']);
                $kw = (string) get_post_meta($p->ID, '_amol_kw', true);
                $e['kw'] = $kw !== '' ? $kw : ($e['kw'] ?? '');
                $e = self::shapePost($e);
                break;
        }

        return $e;
    }

    /** Derived fields of a post entity (jdate / read / words / links). */
    public static function shapePost(array $e): array
    {
        $body = (string) ($e['body'] ?? '');
        $e['jdate'] = Persian::jdate((string) ($e['date'] ?? ''));
        $e['read'] = Persian::readMinutes($body);
        $e['words'] = Persian::wordCount($body);

        return $e;
    }

    /**
     * The lead (خلاصه) of a post: what WordPress itself calls the excerpt (چکیده) — the manual excerpt when the
     * editor wrote one, otherwise the generated `wp_trim_excerpt` — with the stored entity value as last resort.
     * Shortcodes are stripped, so a snippet embedded in the excerpt never leaks markup into the headline block.
     */
    public static function lead(array $e): string
    {
        $text = '';
        $id = (int) ($e['_id'] ?? 0);

        if ($id > 0 && function_exists('get_the_excerpt')) {
            $text = (string) get_the_excerpt($id);
        }

        $text = self::plain($text);

        if ($text === '') {
            $text = self::plain((string) ($e['excerpt'] ?? ''));
        }

        return $text;
    }

    /** Strip tags/shortcodes and collapse whitespace (a one-line lead). */
    protected static function plain(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $text = function_exists('strip_shortcodes') ? strip_shortcodes($text) : $text;

        return trim((string) preg_replace('/\s+/u', ' ', wp_strip_all_tags($text)));
    }

    // ---------------------------------------------------------------- images
    /** URL of an entity image: featured image of the post when set, otherwise the theme image `img/{dir}/{name}{suffix}.webp`. */
    public static function image(array $e, string $dir, string $suffix = '', string $key = 'img'): string
    {
        if (! empty($e['_thumb']) && $suffix !== 'full') {
            return $e['_thumb'];
        }

        $name = $e[$key] ?? '';

        return $name !== '' ? \App\Support\Html::asset("img/{$dir}/{$name}{$suffix}.webp") : \App\Support\Html::asset('img/og/default.jpg');
    }
}
