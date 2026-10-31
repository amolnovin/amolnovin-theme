<?php

namespace App\Seo;

use App\Content\Repo;
use App\Support\Html;
use App\Support\Persian;
use App\Support\Site;

/**
 * JSON-LD (schema.org) graph — a PHP port of the static generator's schema.py.
 */
class Schema
{
    protected const IRR = 10; // toman -> rial

    protected static function url(): string
    {
        return Site::all()['url'];
    }

    public static function orgId(): string
    {
        return self::url() . '/#organization';
    }

    public static function siteId(): string
    {
        return self::url() . '/#website';
    }

    public static function organization(): array
    {
        $s = Site::all();
        $social = array_values(array_filter($s['social'] ?? []));
        $node = [
            '@type' => ['ProfessionalService', 'LocalBusiness'], '@id' => self::orgId(),
            'name' => $s['name'], 'alternateName' => [$s['short'], $s['en_name']],
            'url' => self::url() . '/',
            'logo' => ['@type' => 'ImageObject', 'url' => Html::asset('img/brand/avn_new_logo-512-512.webp'), 'width' => 512, 'height' => 512],
            'image' => Html::asset('img/og/default.jpg'),
            'description' => 'فناوری اطلاعات آمل نوین؛ طراحی سایت وردپرس و اختصاصی، پشتیبانی سایت، سئو و تولید محتوا و امنیت سایت در آمل، مازندران.',
            'telephone' => $s['phone_intl'], 'email' => $s['email'],
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s['street'], 'addressLocality' => $s['city'], 'addressRegion' => $s['region'], 'addressCountry' => $s['country']],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $s['geo'][0], 'longitude' => $s['geo'][1]],
            'areaServed' => [['@type' => 'City', 'name' => 'آمل'], ['@type' => 'AdministrativeArea', 'name' => 'مازندران'], ['@type' => 'Country', 'name' => 'ایران']],
            'foundingDate' => (string) $s['founded'],
            'founder' => ['@type' => 'Person', 'name' => $s['founder']],
            'priceRange' => 'از ' . $s['price_design'] . ' میلیون تومان',
            'knowsAbout' => ['طراحی سایت', 'طراحی سایت وردپرس', 'طراحی سایت اختصاصی', 'پشتیبانی سایت', 'سئو و تولید محتوا', 'ووکامرس', 'امنیت سایت وردپرس'],
            'contactPoint' => [['@type' => 'ContactPoint', 'telephone' => $s['phone_intl'], 'contactType' => 'customer service', 'availableLanguage' => ['fa'], 'areaServed' => 'IR']],
        ];

        if ($social) {
            $node['sameAs'] = $social;
        }

        return $node;
    }

    public static function website(): array
    {
        $s = Site::all();

        return ['@type' => 'WebSite', '@id' => self::siteId(), 'url' => self::url() . '/', 'name' => $s['name'], 'alternateName' => $s['short'], 'inLanguage' => 'fa-IR', 'publisher' => ['@id' => self::orgId()]];
    }

    public static function breadcrumb(array $page, string $canonical): array
    {
        $items = array_merge([['خانه', '/']], $page['crumbs'] ?? []);
        $els = [];
        $n = count($items);

        foreach ($items as $i => [$label, $path]) {
            $pos = $i + 1;
            $els[] = [
                '@type' => 'ListItem', 'position' => $pos, 'name' => $label,
                'item' => $pos === $n ? $canonical : home_url($path ?: $page['path']),
            ];
        }

        return ['@type' => 'BreadcrumbList', '@id' => $canonical . '#breadcrumb', 'itemListElement' => $els];
    }

    public static function webpage(array $page, string $canonical, string $og): array
    {
        $node = [
            '@type' => $page['schema_type'] ?? 'WebPage', '@id' => $canonical . '#webpage', 'url' => $canonical, 'name' => $page['title'], 'description' => $page['desc'],
            'inLanguage' => 'fa-IR', 'isPartOf' => ['@id' => self::siteId()], 'about' => ['@id' => self::orgId()],
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $og],
            'datePublished' => $page['published'] ?? substr((string) current_time('mysql'), 0, 10),
            'dateModified' => $page['modified'] ?? substr((string) current_time('mysql'), 0, 10),
        ];

        if (($page['path'] ?? '') !== '/') {
            $node['breadcrumb'] = ['@id' => $canonical . '#breadcrumb'];
        }

        return $node;
    }

    public static function faq(array $items, string $canonical): array
    {
        return [
            '@type' => 'FAQPage', '@id' => $canonical . '#faq',
            'mainEntity' => array_map(fn ($qa) => [
                '@type' => 'Question', 'name' => $qa[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => Persian::stripTags(str_replace('href="@', 'href="', $qa[1]))],
            ], $items),
        ];
    }

    public static function service(string $name, string $desc, string $canonical, string $type, ?int $priceFrom = null): array
    {
        $node = ['@type' => 'Service', '@id' => $canonical . '#service', 'name' => $name, 'description' => $desc, 'serviceType' => $type, 'url' => $canonical, 'provider' => ['@id' => self::orgId()], 'inLanguage' => 'fa-IR'];

        if ($priceFrom) {
            $node['offers'] = ['@type' => 'Offer', 'priceCurrency' => 'IRR', 'priceSpecification' => ['@type' => 'PriceSpecification', 'priceCurrency' => 'IRR', 'minPrice' => $priceFrom * 1_000_000 * self::IRR], 'url' => $canonical, 'availability' => 'https://schema.org/InStock'];
        }

        return $node;
    }

    public static function offerCatalog(): array
    {
        $s = Site::all();
        $off = fn ($name, $desc, $price, $path) => [
            '@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $name, 'description' => $desc, 'url' => home_url($path)],
            'priceSpecification' => ['@type' => 'PriceSpecification', 'priceCurrency' => 'IRR', 'minPrice' => $price * 1_000_000 * self::IRR],
        ];

        return ['@type' => 'OfferCatalog', 'name' => 'خدمات آمل نوین', 'itemListElement' => [
            $off('طراحی سایت وردپرس', 'طراحی سایت وردپرس اختصاصی، ریسپانسیو و سئو شده', $s['price_design'], '/wordpress-web-design/'),
            $off('پشتیبانی سایت', 'پشتیبانی و نگهداری ماهانه‌ی سایت', $s['price_support'], '/services/site-support/'),
            $off('سئو و تولید محتوا', 'برنامه‌ی سئو و تولید محتوای ماهانه', $s['price_seo'], '/services/seo/'),
        ]];
    }

    public static function article(array $post, string $canonical, string $og): array
    {
        return [
            '@type' => 'BlogPosting', '@id' => $canonical . '#article', 'headline' => $post['title'], 'description' => $post['desc'], 'inLanguage' => 'fa-IR',
            'mainEntityOfPage' => ['@id' => $canonical . '#webpage'], 'image' => [$og], 'datePublished' => $post['date'], 'dateModified' => $post['date'],
            'author' => ['@type' => 'Organization', '@id' => self::orgId(), 'name' => Site::all()['name']], 'publisher' => ['@id' => self::orgId()],
            'articleSection' => $post['cat'], 'wordCount' => $post['words'] ?? 0,
        ];
    }

    public static function itemlist(string $name, array $items, string $canonical): array
    {
        $els = [];
        foreach (array_values($items) as $i => [$n, $u]) {
            $els[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $u, 'name' => $n];
        }

        return ['@type' => 'ItemList', '@id' => $canonical . '#list', 'name' => $name, 'numberOfItems' => count($items), 'itemListElement' => $els];
    }

    /** Nodes that depend on live content are rebuilt, so new posts / projects / plugins appear in the schema automatically. */
    protected static function dynamicList(array $page, string $canonical): ?array
    {
        switch ($page['kind'] ?? '') {
            case 'portfolio':
                return self::itemlist('نمونه‌کارهای آمل نوین', array_map(fn ($p) => [$p['title'], home_url('/projects/' . $p['slug'] . '/')], Repo::projectsWithDetail()), $canonical);

            case 'blog':
                return self::itemlist('مقالات', array_map(fn ($p) => [$p['title'], home_url('/' . $p['slug'] . '/')], Repo::posts()), $canonical);

            case 'store':
                $n = 0;

                return [
                    '@type' => 'ItemList', '@id' => $canonical . '#list', 'name' => 'افزونه‌های آمل نوین', 'numberOfItems' => count(Repo::products()),
                    'itemListElement' => array_map(function ($p) use (&$n) {
                        return ['@type' => 'ListItem', 'position' => ++$n, 'item' => [
                            '@type' => 'SoftwareApplication', 'name' => $p['name'], 'description' => $p['desc'], 'applicationCategory' => 'BusinessApplication',
                            'operatingSystem' => 'WordPress', 'url' => Repo::storeUrl() . $p['slug'], 'publisher' => ['@id' => self::orgId()],
                        ]];
                    }, Repo::products()),
                ];
        }

        return null;
    }

    public static function graph(array $page, string $canonical, string $og): array
    {
        $nodes = [self::organization(), self::website(), self::webpage($page, $canonical, $og)];

        if (($page['path'] ?? '') !== '/') {
            $nodes[] = self::breadcrumb($page, $canonical);
        }

        $dyn = self::dynamicList($page, $canonical);
        foreach ((array) ($page['ld'] ?? []) as $extra) {
            $extra = Html::siteTokens($extra);

            if ($dyn && str_ends_with((string) ($extra['@id'] ?? ''), '#list')) {
                $extra = $dyn;
                $dyn = null;
            }

            $nodes[] = $extra;
        }

        if ($dyn) {
            $nodes[] = $dyn;
        }

        if (! empty($page['faq']) && ($page['faq_schema'] ?? true)) {
            $nodes[] = self::faq($page['faq'], $canonical);
        }

        if (($page['path'] ?? '') === '/') {
            $nodes[0]['hasOfferCatalog'] = self::offerCatalog();
        }

        $graph = ['@context' => 'https://schema.org', '@graph' => Persian::faObj(self::prices(Html::tokens($nodes)))];

        if (($page['kind'] ?? '') === 'post' && ! empty($page['post'])) {
            $graph['@graph'][] = Persian::faObj(self::article($page['post'], $canonical, $og));
        }

        return $graph;
    }

    /** Offer nodes exported with a `_price` marker get their minPrice from the Customizer price (million toman -> rial). */
    protected static function prices(mixed $o): mixed
    {
        if (! is_array($o)) {
            return $o;
        }

        if (isset($o['_price'])) {
            $price = (int) (Site::all()[$o['_price']] ?? 0);

            if ($price && isset($o['priceSpecification'])) {
                $o['priceSpecification']['minPrice'] = $price * 1_000_000 * self::IRR;
            }

            unset($o['_price']);
        }

        return array_map([self::class, 'prices'], $o);
    }

    /** JSON-LD string that is safe inside <script>. */
    public static function json(array $data): string
    {
        $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return str_replace(['<', '>', '&'], ['\u003c', '\u003e', '\u0026'], (string) $json);
    }
}
