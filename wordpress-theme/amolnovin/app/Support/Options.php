<?php

namespace App\Support;

/**
 * Theme options of the «تنظیمات قالب آمل نوین» panel (admin → آمل نوین).
 *
 * Everything is stored in one option row (`amol_options`) grouped by panel tab, so a save of one tab never
 * touches the others. All modules (speed, SEO, header/footer, counters, forms, integrations, updater) read their
 * values through this class — with `Options::section('speed')` for a whole tab or `Options::get('speed', 'key')`.
 */
class Options
{
    public const KEY = 'amol_options';

    /** @var array<string,mixed>|null */
    protected static ?array $cache = null;

    public static function flush(): void
    {
        self::$cache = null;
    }

    /**
     * Every option of the panel with its default. The shape of each value also drives sanitizing on save
     * (bool → checkbox, int → number, string → text, array → repeatable rows).
     *
     * @return array<string,array<string,mixed>>
     */
    public static function defaults(): array
    {
        return [
            // ------------------------------------------------------------ سرعت سایت
            'speed' => [
                'strip_head_meta' => true,      // generator / RSD / WLW / shortlink / REST link / emoji
                'disable_embeds' => true,       // oEmbed discovery + wp-embed.js
                'dequeue_block_css' => true,    // block library CSS on pages without real blocks
                'disable_dashicons' => true,    // dashicons for visitors (not for logged-in users)
                'remove_query_strings' => true, // ?ver= on static assets
                'disable_jquery' => false,      // never on by default: plugins may depend on it
                'lazy_load' => true,            // WordPress native lazy loading
                'heartbeat' => 'slow',          // default | slow | disabled
                'revisions' => 5,               // revisions kept per post (0 = WordPress default)
                'preload_font' => true,         // <link rel=preload> for Vazirmatn
                'preconnect' => '',             // one URL per line
                'speculative' => false,         // WordPress speculative loading rules
            ],

            // ------------------------------------------------------------ سئو
            'seo' => [
                'enabled' => true,              // the theme's own <head> SEO engine
                'sitemap' => true,
                'llms' => true,
                'manifest' => true,
                'site_noindex' => false,        // staging mode: noindex for the whole site
                'robots_extra' => '',           // extra robots.txt rules (one per line)
                'og_default' => '',             // fallback Open Graph image URL
                'twitter' => '',                // @handle for twitter:site
                'verification_google' => '',
                'verification_bing' => '',
                'breadcrumbs' => true,          // BreadcrumbList schema
                'organization_type' => 'ProfessionalService',
                'posts_per_page' => 9,
            ],

            // ------------------------------------------------------------ هدر و فوتر
            'header_footer' => [
                'topbar' => true,
                'sticky_header' => true,
                'cta_label' => 'مشاوره‌ی رایگان',
                'cta_url' => '/contact-us/',
                'announcement' => '',
                'announcement_url' => '',
                'announcement_dismiss' => true,
                'footer_about' => '',
                'footer_copyright' => '',
                'back_to_top' => true,
                'head_code' => '',              // analytics / meta tags in <head>
                'body_code' => '',              // after <body> (GTM noscript, chat widgets …)
                'footer_code' => '',            // before </body>
            ],

            // ------------------------------------------------------------ شمارنده‌ها
            'counters' => [
                'enabled' => true,
                'duration' => 1400,
                'home' => [
                    ['value' => '15', 'suffix' => '+', 'label' => 'سال تجربه در طراحی وب'],
                    ['value' => '150', 'suffix' => '+', 'label' => 'پروژه‌ی تحویل‌شده'],
                    ['value' => '24', 'suffix' => '', 'label' => 'ماه پشتیبانی فنی رایگان'],
                    ['value' => '{types}', 'suffix' => '', 'label' => 'نوع سایت با ساختار تخصصی'],
                ],
                'portfolio' => [
                    ['value' => '150', 'suffix' => '+', 'label' => 'پروژه‌ی تحویل‌شده از سال {founded}'],
                    ['value' => '{projects}', 'suffix' => '', 'label' => 'نمونه‌ی شاخص در این صفحه'],
                    ['value' => '{detail}', 'suffix' => '', 'label' => 'مطالعه‌ی موردی کامل'],
                    ['value' => '3', 'suffix' => '', 'label' => 'حوزه‌ی خدمت: طراحی، پشتیبانی، سئو'],
                ],
            ],

            // ------------------------------------------------------------ فرم‌ها و کپچا
            'forms' => [
                'captcha' => 'auto',            // auto (on when the plugin is active) | on | off
                'captcha_comments' => 'auto',   // let wp-math-captcha handle the core comment form
                'honeypot' => true,
                'rate_limit' => 30,             // seconds between two requests of one IP (0 = off)
                'success_message' => 'درخواست شما ثبت شد؛ کارشناس ما در کمتر از ۲ ساعت با شما تماس می‌گیرد. سپاس از اعتماد شما.',
                'error_message' => 'ارسال درخواست ممکن نشد. لطفاً دوباره تلاش کنید یا در واتساپ پیام بدهید.',
                'captcha_message' => 'پاسخ سؤال امنیتی درست نیست یا زمان آن گذشته است؛ لطفاً دوباره تلاش کنید.',
            ],

            // ------------------------------------------------------------ افزونه‌ها و فروشگاه
            'integrations' => [
                'snippets' => true,             // shortcodes of custom-html-snippets in content/excerpts
                'blog_intro_snippet' => 0,      // snippet shown above the blog index
                'blog_outro_snippet' => 0,      // snippet shown below the blog index
                'store_source' => 'auto',       // auto | plugin | theme
                'store_page_id' => 0,           // the page that renders /wplm-store/
                'security' => true,             // novin-security-suite compatibility layer
                'security_rest_exempt' => true, // keep the public lead form reachable when REST requires login
                'security_headers' => 'auto',   // auto | on | off (theme's own security headers)
            ],

            // ------------------------------------------------------------ بروزرسانی با پچ
            'updates' => [
                'manifest_url' => 'https://raw.githubusercontent.com/amolnovin/amolnovin-theme/main/wordpress-theme/updates/manifest.json',
                'channel' => 'stable',
                'auto_check' => true,
                'last_check' => 0,
                'last_version' => '',
            ],
        ];
    }

    /**
     * All options merged with their defaults.
     *
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $out = self::defaults();
        $saved = get_option(self::KEY, []);

        if (is_array($saved)) {
            foreach ($out as $group => $values) {
                if (! is_array($values)) {
                    if (array_key_exists($group, $saved)) {
                        $out[$group] = $saved[$group];
                    }

                    continue;
                }

                $in = isset($saved[$group]) && is_array($saved[$group]) ? $saved[$group] : [];

                foreach ($values as $key => $default) {
                    if (! array_key_exists($key, $in)) {
                        continue;
                    }

                    $out[$group][$key] = is_bool($default) ? (bool) $in[$key] : $in[$key];
                }
            }
        }

        return self::$cache = $out;
    }

    /**
     * A whole tab of options.
     *
     * @return array<string,mixed>
     */
    public static function section(string $group): array
    {
        $all = self::all();

        return is_array($all[$group] ?? null) ? $all[$group] : [];
    }

    /** One option of a tab. */
    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $section = self::section($group);

        return array_key_exists($key, $section) ? $section[$key] : $default;
    }

    /** Is a checkbox of a tab on? */
    public static function on(string $group, string $key): bool
    {
        return (bool) self::get($group, $key, false);
    }

    /**
     * Save one tab (merged over what is already stored — other tabs are left untouched).
     *
     * @param array<string,mixed> $values
     */
    public static function saveSection(string $group, array $values): void
    {
        $all = get_option(self::KEY, []);
        $all = is_array($all) ? $all : [];
        $all[$group] = $values;

        update_option(self::KEY, $all, false);
        self::flush();
    }
}
