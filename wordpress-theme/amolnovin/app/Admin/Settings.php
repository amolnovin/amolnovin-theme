<?php

namespace App\Admin;

use App\Integrations\MarketManager;
use App\Integrations\Plugins;
use App\Integrations\Security;
use App\Integrations\Snippets;
use App\Support\Counters;
use App\Support\Options;
use App\Updater\Patcher;

/**
 * «تنظیمات قالب آمل نوین» — admin → آمل نوین.
 *
 * The single place for everything that is not content: speed, SEO, header & footer, counters, forms & captcha,
 * plugin integrations (snippets / market manager / captcha / security suite) and theme updates through patches.
 *
 * Values are stored by App\Support\Options; every module reads them from there, so a tab can be added without
 * touching the front end.
 */
class Settings
{
    public const PAGE = 'amol-theme';
    public const OPTION_GROUP = 'amol_options';

    public const TABS = [
        'speed' => 'بهینه‌سازی سرعت',
        'seo' => 'سئو',
        'header_footer' => 'هدر و فوتر',
        'counters' => 'شمارنده‌ها',
        'forms' => 'فرم‌ها و کپچا',
        'integrations' => 'افزونه‌ها و فروشگاه',
        'updates' => 'بروزرسانی قالب',
        'tools' => 'ابزارها و اطلاعات',
    ];

    public static function boot(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_post_amol_save_options', [self::class, 'save']);
        add_action('admin_post_amol_store_sync', [self::class, 'syncStore']);
        add_action('admin_post_amol_patch_check', [self::class, 'patchCheck']);
        add_action('admin_post_amol_patch_apply', [self::class, 'patchApply']);
        add_action('admin_post_amol_patch_rollback', [self::class, 'patchRollback']);
        add_action('admin_post_amol_patch_upload', [self::class, 'patchUpload']);
        add_action('admin_post_amol_rebuild_index', [self::class, 'rebuildIndex']);
        add_action('admin_notices', [self::class, 'updateNotice']);
        add_action('admin_init', [self::class, 'autoCheck']);
    }

    // ---------------------------------------------------------------- menu & chrome

    public static function menu(): void
    {
        add_menu_page(
            'تنظیمات قالب آمل نوین',
            'قالب آمل نوین',
            'manage_options',
            self::PAGE,
            [self::class, 'render'],
            'dashicons-admin-customizer',
            59
        );
    }

    public static function url(string $tab = 'speed'): string
    {
        return add_query_arg(['page' => self::PAGE, 'tab' => $tab], admin_url('admin.php'));
    }

    protected static function currentTab(): string
    {
        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'speed'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.

        return array_key_exists($tab, self::TABS) ? $tab : 'speed';
    }

    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('دسترسی به این صفحه مجاز نیست.', 'amolnovin'));
        }

        $tab = self::currentTab();
        $notice = get_transient('amol_settings_notice');
        delete_transient('amol_settings_notice');

        echo '<div class="wrap amol-panel">';
        echo '<h1>تنظیمات قالب آمل نوین <span style="font-size:13px;font-weight:400;color:#646970">نسخه ' . esc_html(Patcher::currentVersion()) . '</span></h1>';

        if (is_array($notice) && ! empty($notice['message'])) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type'] === 'error' ? 'error' : 'success'),
                esc_html((string) $notice['message'])
            );
        }

        if (! Options::on('seo', 'enabled') && \App\Seo\Head::pluginActive()) {
            echo '<div class="notice notice-info"><p>یک افزونه‌ی سئوی مستقل فعال است؛ خروجی سئوی قالب خودکار خاموش شده است.</p></div>';
        }

        echo '<nav class="nav-tab-wrapper" aria-label="بخش‌های تنظیمات">';

        foreach (self::TABS as $key => $label) {
            printf(
                '<a class="nav-tab%s" href="%s">%s</a>',
                $key === $tab ? ' nav-tab-active' : '',
                esc_url(self::url($key)),
                esc_html($label)
            );
        }

        echo '</nav>';

        self::styles();

        $method = 'tab' . str_replace('_', '', ucwords($tab, '_'));

        if (method_exists(self::class, $method)) {
            self::$method();
        }

        echo '</div>';
    }

    /** A tiny bit of scoped styling — the panel uses WordPress' own form/table classes otherwise. */
    protected static function styles(): void
    {
        echo '<style>
            .amol-panel .amol-hero{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin:14px 0 4px}
            .amol-panel .amol-hero .button{display:inline-flex;align-items:center;gap:6px}
            .amol-panel .form-table th{width:260px;font-weight:600}
            .amol-panel .form-table td .description{margin-top:6px}
            .amol-panel .amol-badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;line-height:1.7}
            .amol-panel .amol-badge--ok{background:#d1f0d8;color:#0a5c2a}
            .amol-panel .amol-badge--off{background:#f0f0f1;color:#50575e}
            .amol-panel .amol-badge--wait{background:#fcf3cf;color:#6b5b00}
            .amol-panel .amol-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:14px 16px;margin:12px 0}
            .amol-panel .amol-card h2{margin-top:0;font-size:15px}
            .amol-panel .amol-code{font-family:Consolas,Menlo,monospace;font-size:12px;width:100%}
            .amol-panel .amol-inline{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
            .amol-panel table.amol-status td,.amol-panel table.amol-status th{padding:8px 10px}
            .amol-panel .amol-checklist{list-style:none;margin:8px 0 0;padding:0;display:grid;gap:8px}
            .amol-panel .amol-checklist__item{display:flex;gap:8px;align-items:flex-start;margin:0}
            .amol-panel .amol-checklist__icon{flex:0 0 20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;background:#f0f0f1;color:#50575e}
            .amol-panel .amol-checklist__item--ok .amol-checklist__icon{background:#d1f0d8;color:#0a5c2a}
            .amol-panel .amol-checklist__item--warn .amol-checklist__icon{background:#fcf3cf;color:#6b5b00}
        </style>';
    }

    // ---------------------------------------------------------------- field helpers

    protected static function name(string $group, string $key): string
    {
        return self::OPTION_GROUP . '[' . $group . '][' . $key . ']';
    }

    protected static function id(string $group, string $key): string
    {
        return 'amol-' . $group . '-' . $key;
    }

    protected static function desc(string $text): string
    {
        return $text === '' ? '' : '<p class="description">' . wp_kses_post($text) . '</p>';
    }

    protected static function row(string $label, string $control, string $desc = '', string $id = ''): void
    {
        echo '<tr><th scope="row">' . ($id !== '' ? '<label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>' : esc_html($label)) . '</th><td>' . $control . self::desc($desc) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every helper escapes its own markup.
    }

    protected static function checkbox(string $group, string $key, string $label, string $desc = ''): void
    {
        $id = self::id($group, $key);
        $control = '<label for="' . esc_attr($id) . '"><input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr(self::name($group, $key)) . '" value="1" ' . checked(Options::on($group, $key), true, false) . '> ' . esc_html($label) . '</label>';

        self::row($label, $control, $desc);
    }

    /** @param array<string,string|int> $attrs */
    protected static function input(string $group, string $key, string $label, string $desc = '', array $attrs = [], string $type = 'text'): void
    {
        $id = self::id($group, $key);
        $extra = '';

        foreach ($attrs as $attr => $value) {
            $extra .= ' ' . esc_attr($attr) . '="' . esc_attr((string) $value) . '"';
        }

        $value = (string) Options::get($group, $key, '');
        $control = '<input type="' . esc_attr($type) . '" class="regular-text" id="' . esc_attr($id) . '" name="' . esc_attr(self::name($group, $key)) . '" value="' . esc_attr($value) . '"' . $extra . '>';

        self::row($label, $control, $desc, $id);
    }

    protected static function number(string $group, string $key, string $label, string $desc = '', int $min = 0, int $max = 0): void
    {
        $attrs = ['min' => $min, 'step' => 1];

        if ($max > 0) {
            $attrs['max'] = $max;
        }

        self::input($group, $key, $label, $desc, $attrs, 'number');
    }

    protected static function textarea(string $group, string $key, string $label, string $desc = '', int $rows = 4, string $class = 'large-text', ?string $value = null): void
    {
        $id = self::id($group, $key);
        $value = $value ?? (string) Options::get($group, $key, '');
        $control = '<textarea rows="' . (int) $rows . '" class="' . esc_attr($class) . '" id="' . esc_attr($id) . '" name="' . esc_attr(self::name($group, $key)) . '">' . esc_textarea($value) . '</textarea>';

        self::row($label, $control, $desc, $id);
    }

    /** @param array<string,string> $choices */
    protected static function select(string $group, string $key, string $label, array $choices, string $desc = ''): void
    {
        $id = self::id($group, $key);
        $current = (string) Options::get($group, $key, '');
        $control = '<select id="' . esc_attr($id) . '" name="' . esc_attr(self::name($group, $key)) . '">';

        foreach ($choices as $value => $text) {
            $control .= '<option value="' . esc_attr((string) $value) . '" ' . selected($current, (string) $value, false) . '>' . esc_html($text) . '</option>';
        }

        $control .= '</select>';

        self::row($label, $control, $desc, $id);
    }

    protected static function openForm(string $group): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('amol_save_options');
        echo '<input type="hidden" name="action" value="amol_save_options">';
        echo '<input type="hidden" name="group" value="' . esc_attr($group) . '">';
        echo '<input type="hidden" name="tab" value="' . esc_attr($group) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
    }

    protected static function closeForm(string $text = 'ذخیره‌ی تنظیمات'): void
    {
        echo '</tbody></table>';
        submit_button($text, 'primary', 'submit', false);
        echo '</form>';
    }

    // ---------------------------------------------------------------- tabs

    protected static function tabSpeed(): void
    {
        self::openForm('speed');

        self::checkbox('speed', 'strip_head_meta', 'حذف متاهای اضافی از <head>', 'ژنراتور وردپرس، RSD، WLW، لینک کوتاه و لینک کشف REST.');
        self::checkbox('speed', 'disable_embeds', 'خاموش‌کردن oEmbed', 'اسکریپت wp-embed.js و مسیر REST مربوط به oEmbed حذف می‌شود.');
        self::checkbox('speed', 'dequeue_block_css', 'حذف CSS بلوک‌ها در صفحه‌های بدون بلوک', 'صفحه‌هایی که فقط «HTML سفارشی» دارند به استایل بلوک گوتنبرگ نیازی ندارند.');
        self::checkbox('speed', 'disable_dashicons', 'حذف Dashicons برای بازدیدکنندگان', 'آیکن‌فونت پیشخوان برای کاربران واردشده باقی می‌ماند. اگر افزونه‌ای در سایت از Dashicons استفاده می‌کند این گزینه را خاموش کنید.');
        self::checkbox('speed', 'remove_query_strings', 'حذف ?ver= از فایل‌های CSS و JS', 'کش مرورگر و CDN مؤثرتر می‌شود؛ برای کاربران واردشده اعمال نمی‌شود.');
        self::checkbox('speed', 'disable_jquery', 'حذف jQuery از سایت', 'فقط برای سایت‌هایی که هیچ افزونه‌ای به jQuery نیاز ندارد.');
        self::checkbox('speed', 'lazy_load', 'بارگذاری تنبل تصاویر (Lazy loading وردپرس)', 'تصویر اصلی بالای صفحه (LCP) با fetchpriority بالا بارگذاری می‌شود.');
        self::select('speed', 'heartbeat', 'Heartbeat API', [
            'default' => 'پیش‌فرض وردپرس',
            'slow' => 'کندتر (۶۰ ثانیه) — پیشنهادی',
            'disabled' => 'خاموش در بخش عمومی',
        ], 'Heartbeat در پیشخوان و ویرایشگر دست‌نخورده می‌ماند.');
        self::number('speed', 'revisions', 'حداکثر نگارش‌های هر نوشته', '۰ = پیش‌فرض وردپرس (بدون محدودیت).', 0, 50);
        self::checkbox('speed', 'preload_font', 'preload فونت وزیرمتن', 'بهبود LCP متن صفحه.');
        self::textarea('speed', 'preconnect', 'پیش‌اتصال (preconnect) دامنه‌های خارجی', 'هر خط یک آدرس، مثلاً https://www.googletagmanager.com — خالی = هیچ.', 3, 'regular-text code');
        self::checkbox('speed', 'speculative', 'فعال‌بودن Speculative Loading وردپرس', 'پیش‌بارگذاری حدسی لینک‌ها؛ در قالب خاموش است تا رفتار صفحه‌ها دقیقاً مثل قالب HTML بماند.');

        self::closeForm();
    }

    protected static function tabSeo(): void
    {
        self::openForm('seo');

        self::checkbox('seo', 'enabled', 'خروجی سئوی قالب (متا، Open Graph، Schema)', 'اگر افزونه‌ی سئوی مستقل (Yoast، Rank Math، …) فعال باشد خودکار خاموش می‌شود.');
        self::checkbox('seo', 'sitemap', 'نقشه‌ی سایت XML در /sitemap.xml');
        self::checkbox('seo', 'llms', 'فایل /llms.txt برای موتورهای هوش مصنوعی');
        self::checkbox('seo', 'manifest', 'فایل manifest وب‌اپ در /manifest.webmanifest');
        self::checkbox('seo', 'site_noindex', 'حالت تعمیر/پیش‌نمایش (noindex کل سایت)', 'برای سایت آزمایشی یا در حال بازطراحی روشن کنید و پیش از انتشار خاموش کنید.');
        self::textarea('seo', 'robots_extra', 'خطوط اضافی robots.txt', 'نمونه: <code>Disallow: /wp-login.php</code> — فقط دستورهای مجاز (Disallow, Allow, User-agent, Sitemap) پذیرفته می‌شوند.', 4);
        self::input('seo', 'og_default', 'تصویر پیش‌فرض Open Graph (آدرس کامل)', 'وقتی صفحه تصویر اختصاصی ندارد از این تصویر استفاده می‌شود. خالی = تصویر پیش‌فرض قالب.', ['dir' => 'ltr']);
        self::input('seo', 'twitter', 'حساب توییتر/X سایت', 'بدون @ — در twitter:site استفاده می‌شود.', ['dir' => 'ltr']);
        self::input('seo', 'verification_google', 'کد تأیید Google Search Console', '', ['dir' => 'ltr']);
        self::input('seo', 'verification_bing', 'کد تأیید Bing Webmaster', '', ['dir' => 'ltr']);
        self::checkbox('seo', 'breadcrumbs', 'Schema مسیر صفحه (BreadcrumbList)', 'آدرس‌های مسیر در نتایج گوگل نمایش داده می‌شود.');
        self::select('seo', 'organization_type', 'نوع سازمان در Schema', [
            'ProfessionalService' => 'ProfessionalService — خدمات حرفه‌ای',
            'LocalBusiness' => 'LocalBusiness — کسب‌وکار محلی',
            'Organization' => 'Organization — سازمان',
            'Store' => 'Store — فروشگاه',
            'Corporation' => 'Corporation — شرکت',
        ]);
        self::number('seo', 'posts_per_page', 'تعداد نوشته در هر صفحه‌ی وبلاگ', 'صفحه‌ی /blog-n/، آرشیوها و نتایج جست‌وجو.', 3, 48);

        self::closeForm();
    }

    protected static function tabHeaderFooter(): void
    {
        self::openForm('header_footer');

        self::checkbox('header_footer', 'topbar', 'نوار بالای هدر (نشانی، ایمیل، تلفن)');
        self::checkbox('header_footer', 'sticky_header', 'هدر چسبان هنگام اسکرول');
        self::input('header_footer', 'cta_label', 'متن دکمه‌ی اصلی هدر');
        self::input('header_footer', 'cta_url', 'لینک دکمه‌ی اصلی هدر', 'مسیر نسبی مثل /contact-us/ یا آدرس کامل.', ['dir' => 'ltr']);
        self::textarea('header_footer', 'announcement', 'نوار اعلان بالای سایت', 'خالی = نمایش داده نمی‌شود. برای چند خط از <code>&lt;br&gt;</code> استفاده کنید.', 2);
        self::input('header_footer', 'announcement_url', 'لینک نوار اعلان', '', ['dir' => 'ltr']);
        self::checkbox('header_footer', 'announcement_dismiss', 'کاربر بتواند نوار اعلان را ببندد', 'انتخاب کاربر در مرورگر ذخیره می‌شود.');
        self::textarea('header_footer', 'footer_about', 'متن معرفی در فوتر', 'خالی = متن پیش‌فرض قالب.', 3);
        self::input('header_footer', 'footer_copyright', 'متن کپی‌رایت (بعد از نام سایت)');
        self::checkbox('header_footer', 'back_to_top', 'دکمه‌ی بازگشت به بالا');
        self::textarea('header_footer', 'head_code', 'کد سفارشی در <head>', 'اسکریپت آنالیتیکس، متای تأیید یا CSS اضافه. برای مدیر کل ذخیره می‌شود؛ سایر نقش‌ها فیلتر می‌شوند.', 4, 'large-text code');
        self::textarea('header_footer', 'body_code', 'کد سفارشی بعد از <body>', 'مثلاً noscript گوگل تگ منیجر.', 3, 'large-text code');
        self::textarea('header_footer', 'footer_code', 'کد سفارشی قبل از </body>', 'چت آنلاین، اسکریپت پیکسل و…', 3, 'large-text code');

        self::closeForm();
    }

    protected static function tabCounters(): void
    {
        self::openForm('counters');

        self::checkbox('counters', 'enabled', 'شمارنده‌های متحرک', 'خاموش = عدد نهایی بدون انیمیشن نمایش داده می‌شود (سریع‌تر و بی‌حرکت برای کاربرانی که انیمیشن را کاهش داده‌اند).');
        self::number('counters', 'duration', 'مدت انیمیشن شمارنده (میلی‌ثانیه)', '', 300, 4000);

        self::textarea('counters', 'home', 'شمارنده‌های صفحه‌ی اصلی', self::counterHelp(), 5, 'large-text code', self::rowsToLines('home'));
        self::textarea('counters', 'portfolio', 'شمارنده‌های صفحه‌ی نمونه‌کارها', self::counterHelp(), 5, 'large-text code', self::rowsToLines('portfolio'));

        $preview = '';

        foreach (Counters::items('home') as $item) {
            $preview .= '<li>' . esc_html($item['value'] . $item['suffix']) . ' — ' . esc_html($item['label']) . '</li>';
        }

        self::row('پیش‌نمایش مقادیر فعلی', '<ul style="margin:0;list-style:disc;padding-inline-start:20px">' . $preview . '</ul>', 'هر خط یک شمارنده: <code>عدد|پسوند|برچسب</code>');

        self::closeForm();
    }

    /** Stored counter rows → editable «value|suffix|label» lines. */
    protected static function rowsToLines(string $set): string
    {
        $lines = [];

        foreach ((array) Options::get('counters', $set, []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lines[] = ($row['value'] ?? '') . '|' . ($row['suffix'] ?? '') . '|' . ($row['label'] ?? '');
        }

        return implode("\n", $lines);
    }

    protected static function counterHelp(): string
    {
        return 'هر خط: <code>عدد|پسوند|برچسب</code> — مثلاً <code>15|+|سال تجربه</code>. متغیرهای پویا: <code>{types}</code> تعداد انواع سایت، <code>{projects}</code> تعداد نمونه‌کارها، <code>{detail}</code> مطالعه‌های موردی، <code>{posts}</code> نوشته‌ها، <code>{years}</code> سال تجربه، <code>{founded}</code> سال تأسیس.';
    }

    protected static function tabForms(): void
    {
        self::openForm('forms');

        self::select('forms', 'captcha', 'کپچا در فرم‌های قالب', [
            'auto' => 'خودکار (وقتی افزونه فعال است)',
            'on' => 'همیشه فعال',
            'off' => 'خاموش',
        ], 'فرم مشاوره‌ی قالب با API رسمی WP Math Captcha ساخته و سمت سرور بررسی می‌شود. اگر افزونه فعال نباشد هیچ فیلدی اضافه نمی‌شود.');

        self::select('forms', 'captcha_comments', 'کپچا در فرم دیدگاه', [
            'auto' => 'خودکار (افزونه خودش مدیریت کند)',
            'on' => 'قالب اضافه کند',
            'off' => 'خاموش',
        ], 'در حالت «خودکار» فقط وقتی کپچای دیدگاهِ خود افزونه خاموش باشد قالب فیلد را اضافه می‌کند تا دو کپچا نمایش داده نشود.');

        self::checkbox('forms', 'honeypot', 'فیلد تله (Honeypot) فرم مشاوره', 'فیلد نامرئی «website»؛ اگر پر شود درخواست بدون ثبت پاسخ موفق تلقی می‌شود.');
        self::number('forms', 'rate_limit', 'فاصله‌ی مجاز بین دو درخواست یک IP (ثانیه)', '۰ = بدون محدودیت.', 0, 3600);
        self::textarea('forms', 'success_message', 'پیام موفقیت فرم', '', 2);
        self::textarea('forms', 'error_message', 'پیام خطای عمومی فرم', '', 2);
        self::textarea('forms', 'captcha_message', 'پیام خطای کپچا', '', 2);

        self::closeForm();
    }

    protected static function tabIntegrations(): void
    {
        // ---- status table
        echo '<div class="amol-card"><h2>وضعیت افزونه‌های همراه</h2><table class="widefat striped amol-status"><thead><tr><th>افزونه</th><th>وضعیت</th><th>نسخه</th><th>کاربرد در قالب</th></tr></thead><tbody>';

        foreach (Plugins::status() as $row) {
            $badge = $row['active']
                ? '<span class="amol-badge amol-badge--ok">فعال</span>'
                : (Plugins::installed($row['plugin'])
                    ? '<span class="amol-badge amol-badge--wait">نصب‌شده، غیرفعال</span>'
                    : '<span class="amol-badge amol-badge--off">نصب نشده</span>');

            echo '<tr><td><strong>' . esc_html($row['name']) . '</strong><br><code>' . esc_html($row['plugin']) . '</code></td><td>' . $badge . '</td><td dir="ltr">' . esc_html($row['active'] ? $row['version'] : '—') . '</td><td>' . esc_html($row['note']) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge built with esc_html().
        }

        echo '</tbody></table></div>';

        // ---- settings
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('amol_save_options');
        echo '<input type="hidden" name="action" value="amol_save_options"><input type="hidden" name="group" value="integrations"><input type="hidden" name="tab" value="integrations">';
        echo '<table class="form-table" role="presentation"><tbody>';

        self::checkbox('integrations', 'snippets', 'پشتیبانی از شورت‌کد قطعه کدها', 'شورت‌کد <code>[my_snippet id="…"]</code> در نوشته‌ها، برگه‌ها، خلاصه‌ها و بخش «قطعه کد سفارشی» کار می‌کند (افزونه‌ی ۳.۰.x).');
        self::checkbox('integrations', 'snippets_scoped_css', 'CSS قطعه‌کدها فقط در ناحیه‌ی محتوا', 'استایل هر قطعه‌کد به همان قطعه‌کد محدود می‌شود: قاعده‌هایی مثل <code>body</code>، <code>h2</code>، <code>.prose h2</code> و همچنین حالت «تحویل فایلی» و «سراسری» به داخل <code>.custom-snippet-container</code> منتقل می‌شوند تا هدر، منو، فوتر و کارت‌های سایت تغییر نکنند. <code>@media</code> بازنویسی می‌شود و <code>@keyframes</code>/<code>@font-face</code> دست‌نخورده می‌ماند.');
        self::checkbox('integrations', 'snippets_edit_bar', 'نوار ویرایش سریع قطعه‌کد (۳.۰.۳+)', 'نوار بالای هر قطعه‌کد برای کاربران واردشده‌ای که اجازه‌ی ویرایش دارند نمایش داده می‌شود؛ با خاموش‌کردن آن، قطعه‌کد فقط برای بازدیدکننده‌ها یکسان دیده می‌شود (فیلتر <code>chs_show_frontend_edit_box</code>).');
        self::select('integrations', 'blog_intro_snippet', 'قطعه کد بالای فهرست وبلاگ', self::snippetChoices());
        self::select('integrations', 'blog_outro_snippet', 'قطعه کد پایین فهرست وبلاگ', self::snippetChoices());
        self::select('integrations', 'store_source', 'منبع صفحه‌ی /wplm-store/', [
            'auto' => 'خودکار — افزونه در صورت فعال‌بودن',
            'plugin' => 'فقط افزونه‌ی فروشگاه',
            'theme' => 'فقط کارت‌های قالب',
        ], 'در حالت افزونه، شورت‌کد <code>[wplm_store]</code> (محصول، سبد، پرداخت و حساب کاربری) نمایش داده می‌شود.');
        self::checkbox('integrations', 'security', 'سازگاری با بسته‌ی امنیتی نوین', 'مسیر عمومی فرم مشاوره و هدرهای امنیتی هماهنگ می‌شوند.');
        self::checkbox('integrations', 'security_rest_exempt', 'باز نگه‌داشتن REST فرم مشاوره', 'وقتی «الزام ورود برای REST» روشن است، فقط مسیر عمومی /wp-json/amolnovin/v1/lead باز می‌ماند.');
        self::select('integrations', 'security_headers', 'هدرهای امنیتی قالب', [
            'auto' => 'خودکار — اگر افزونه امنیتی نفرستد',
            'on' => 'همیشه بفرست',
            'off' => 'نفرست',
        ]);

        echo '</tbody></table>';
        submit_button('ذخیره‌ی تنظیمات', 'primary', 'submit', false);
        echo '</form>';

        // ---- blog page (WordPress «posts page» = /blog-n/)
        $blogId = (int) get_option('page_for_posts');
        $blogReady = $blogId > 0 && get_post_status($blogId) === 'publish';
        echo '<div class="amol-card"><h2>صفحه‌ی وبلاگ</h2>';

        if ($blogReady) {
            echo '<p>صفحه‌ی نوشته‌ها: <code>' . esc_html((string) get_permalink($blogId)) . '</code> — نوشته‌های وردپرس، دسته‌بندی‌ها و صفحه‌بندی از همین صفحه نمایش داده می‌شوند.</p>';
        } else {
            echo '<p>صفحه‌ی نوشته‌ها هنوز انتخاب نشده؛ برای اینکه <code>/blog-n/</code> نوشته‌ها را نشان دهد، در <a href="' . esc_url(admin_url('options-reading.php')) . '">تنظیمات → خواندن</a> برگه‌ی «وبلاگ» را به‌عنوان «برگه‌ی نوشته‌ها» انتخاب کنید. تا آن زمان قالب از صفحه‌ی بایگانی پیش‌فرض وردپرس استفاده می‌کند.</p>';
        }

        echo '</div>';

        // ---- store page sync
        $page = self::storePage();
        $linked = MarketManager::storeUrl();
        $pageUrl = $page ? (string) get_permalink($page) : '';

        echo '<div class="amol-card"><h2>اتصال صفحه‌ی فروشگاه</h2>';
        echo '<p>صفحه‌ای که قالب با قالب‌بندی «فروشگاه افزونه‌ها» نمایش می‌دهد: <code>' . esc_html($pageUrl !== '' ? $pageUrl : '—') . '</code></p>';
        echo '<p>آدرس فروشگاه در افزونه: <code>' . esc_html($linked) . '</code></p>';

        if ($page && Plugins::marketManager()) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="amol-inline">';
            wp_nonce_field('amol_store_sync');
            echo '<input type="hidden" name="action" value="amol_store_sync">';
            echo '<input type="hidden" name="page_id" value="' . (int) $page->ID . '">';
            submit_button('این صفحه را به‌عنوان فروشگاه افزونه ثبت کن', 'secondary', 'submit', false);
            echo '<span class="description">صفحه‌ی حساب کاربری و نتیجه‌ی پرداخت افزونه، اگر تنظیم نشده باشند، به همین صفحه وصل می‌شوند.</span></form>';
        } elseif (! Plugins::marketManager()) {
            echo '<p class="description">افزونه‌ی WP Plugin Market Manager فعال نیست؛ قالب کارت‌های فروشگاه خودش را نمایش می‌دهد.</p>';
        }

        echo '</div>';

        // ---- shortcode diagnostics (a plugin can be active yet register nothing)
        $checks = [];

        if (Plugins::marketManager() && ! shortcode_exists('wplm_store')) {
            $checks[] = ['warn', 'افزونه‌ی فروشگاه فعال است ولی شورت‌کد <code>[wplm_store]</code> ثبت نشده؛ نسخه‌ی افزونه را بازنصب کنید. تا آن زمان قالب کارت‌های خودش را نشان می‌دهد.'];
        }

        if (Plugins::marketManager() && shortcode_exists('wplm_store')) {
            $checks[] = ['ok', 'شورت‌کد <code>[wplm_store]</code> در دسترس است. اگر صفحه هنوز خطا بدهد، پیام خطا فقط برای مدیر نمایش داده می‌شود و بازدیدکننده‌ها کارت‌های قالب را می‌بینند.'];
        }

        if (Plugins::snippets() && ! shortcode_exists('my_snippet')) {
            $checks[] = ['warn', 'افزونه‌ی قطعه کدها فعال است ولی شورت‌کد <code>[my_snippet]</code> ثبت نشده است.'];
        }

        if ($checks) {
            echo '<div class="amol-card"><h2>بررسی شورت‌کدها</h2><ul class="amol-checklist">';

            foreach ($checks as [$kind, $text]) {
                $icon = $kind === 'ok' ? '✓' : '!';
                echo '<li class="amol-checklist__item amol-checklist__item--' . esc_attr($kind) . '"><span class="amol-checklist__icon">' . esc_html($icon) . '</span><span>' . $text . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text built with esc_html()/static markup.
            }

            echo '</ul></div>';
        }

        // ---- security state
        if (Plugins::security()) {
            $state = Security::state();
            echo '<div class="amol-card"><h2>وضعیت بسته‌ی امنیتی</h2><ul style="list-style:disc;padding-inline-start:20px">';
            echo '<li>الزام ورود برای REST: <strong>' . ($state['rest_require_auth'] ? 'روشن' : 'خاموش') . '</strong></li>';
            echo '<li>هدرهای امنیتی توسط افزونه: <strong>' . ($state['headers_by_plugin'] ? 'بله' : 'خیر') . '</strong></li>';
            echo '<li>آدرس ورود مخفی: <strong>' . ($state['stealth_login'] ? 'فعال' : 'خاموش') . '</strong></li>';
            echo '</ul><p class="description">قالب هیچ‌کدام از این تنظیمات را تغییر نمی‌دهد؛ فقط خودش را با آن‌ها هماهنگ می‌کند.</p></div>';
        }
    }

    /** @return array<int,string> */
    protected static function snippetChoices(): array
    {
        $choices = [0 => '— هیچ‌کدام —'];

        foreach (Snippets::choices() as $id => $title) {
            $choices[$id] = $title;
        }

        return $choices;
    }

    protected static function storePage(): ?\WP_Post
    {
        $pages = get_pages([
            'meta_key' => '_wp_page_template',
            'meta_value' => 'template-store.blade.php',
            'number' => 1,
            'post_status' => 'publish',
        ]);

        if ($pages) {
            return $pages[0];
        }

        $page = get_page_by_path('wplm-store');

        return $page instanceof \WP_Post ? $page : null;
    }

    protected static function tabUpdates(): void
    {
        $status = Patcher::status();
        $available = $status['available'];

        echo '<div class="amol-card"><h2>نسخه‌ی نصب‌شده: <span dir="ltr">' . esc_html($status['version']) . '</span></h2>';
        echo '<p>بروزرسانی قالب با «پچ» انجام می‌شود: فقط فایل‌های تغییرکرده جابه‌جا می‌شوند، پیش از نوشتن از همه‌ی فایل‌ها پشتیبان گرفته می‌شود و امکان بازگردانی وجود دارد. ساختار استاندارد در <code>wordpress-theme/updates/</code> و راهنما در <code>docs/UPDATES.md</code> است.</p>';

        if ($status['last_check'] > 0) {
            echo '<p class="description">آخرین بررسی: ' . esc_html(wp_date('Y/m/d H:i', (int) $status['last_check'])) . ' — منبع: <code>' . esc_html($status['manifest_url']) . '</code></p>';
        } else {
            echo '<p class="description">هنوز بررسی‌ای انجام نشده است — منبع: <code>' . esc_html($status['manifest_url']) . '</code></p>';
        }

        if ($status['error'] !== '') {
            echo '<div class="notice notice-warning inline"><p>' . esc_html($status['error']) . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="amol-inline">';
        wp_nonce_field('amol_patch_check');
        echo '<input type="hidden" name="action" value="amol_patch_check">';
        submit_button('بررسی بروزرسانی', 'secondary', 'submit', false);
        echo '</form></div>';

        if ($available) {
            echo '<div class="amol-card"><h2>بروزرسانی‌های موجود</h2><table class="widefat striped amol-status"><thead><tr><th>نسخه</th><th>تاریخ</th><th>توضیحات</th><th>فایل‌ها</th><th></th></tr></thead><tbody>';

            foreach ($available as $release) {
                $notes = trim(wp_strip_all_tags((string) ($release['notes'] ?? '')));
                echo '<tr><td dir="ltr"><strong>' . esc_html((string) $release['_version']) . '</strong></td><td dir="ltr">' . esc_html((string) ($release['date'] ?? '—')) . '</td><td>' . nl2br(esc_html($notes)) . '</td><td>' . (int) count((array) ($release['files'] ?? [])) . '</td><td>';

                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                wp_nonce_field('amol_patch_apply');
                echo '<input type="hidden" name="action" value="amol_patch_apply"><input type="hidden" name="version" value="' . esc_attr((string) $release['_version']) . '">';
                submit_button('اعمال پچ', 'primary small', 'submit', false, ['onclick' => "return confirm('پچ نسخه‌ی " . esc_js((string) $release['_version']) . " اعمال شود؟ از فایل‌های فعلی پشتیبان گرفته می‌شود.');"]);
                echo '</form></td></tr>';
            }

            echo '</tbody></table></div>';
        } elseif ($status['cached']) {
            echo '<div class="notice notice-success inline"><p>قالب به‌روز است؛ نسخه‌ی جدیدی برای نسخه‌ی فعلی شما در فهرست نیست.</p></div>';
        }

        // applied patches
        if ($status['applied']) {
            echo '<div class="amol-card"><h2>پچ‌های اعمال‌شده</h2><table class="widefat striped amol-status"><thead><tr><th>نسخه</th><th>زمان</th><th>فایل‌ها</th><th>پشتیبان</th><th></th></tr></thead><tbody>';

            foreach ($status['applied'] as $version => $info) {
                echo '<tr><td dir="ltr"><strong>' . esc_html((string) $version) . '</strong></td><td dir="ltr">' . esc_html(! empty($info['date']) ? wp_date('Y/m/d H:i', (int) $info['date']) : '—') . '</td><td>' . (int) ($info['files'] ?? 0) . '</td><td><code style="font-size:11px">' . esc_html((string) ($info['backup'] ?? '')) . '</code></td><td>';

                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                wp_nonce_field('amol_patch_rollback');
                echo '<input type="hidden" name="action" value="amol_patch_rollback"><input type="hidden" name="version" value="' . esc_attr((string) $version) . '">';
                submit_button('بازگردانی', 'secondary small', 'submit', false, ['onclick' => "return confirm('فایل‌های این پچ به حالت قبل برگردند؟');"]);
                echo '</form></td></tr>';
            }

            echo '</tbody></table></div>';
        }

        // manifest settings
        echo '<div class="amol-card"><h2>منبع بروزرسانی</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('amol_save_options');
        echo '<input type="hidden" name="action" value="amol_save_options"><input type="hidden" name="group" value="updates"><input type="hidden" name="tab" value="updates">';
        echo '<table class="form-table" role="presentation"><tbody>';

        self::input('updates', 'manifest_url', 'آدرس فهرست بروزرسانی (manifest.json)', 'پیش‌فرض: فایل <code>wordpress-theme/updates/manifest.json</code> در مخزن گیت‌هاب. می‌توانید آن را به سرور خودتان هم منتقل کنید.', ['dir' => 'ltr']);
        self::select('updates', 'channel', 'کانال انتشار', ['stable' => 'پایدار', 'beta' => 'آزمایشی']);
        self::checkbox('updates', 'auto_check', 'بررسی خودکار روزانه در پیشخوان', 'فقط برای کاربرانی که اجازه‌ی بروزرسانی دارند و حداکثر هر ۱۲ ساعت یک‌بار.');

        echo '</tbody></table>';
        submit_button('ذخیره‌ی تنظیمات', 'primary', 'submit', false);
        echo '</form></div>';

        // manual upload
        echo '<div class="amol-card"><h2>اعمال پچ آپلودی (سایت بدون اینترنت)</h2><p>فایل ZIP پچ را که از مخزن یا نسخه‌ی انتشار دانلود کرده‌اید بارگذاری کنید. بسته باید در ریشه‌ی خود <code>manifest.json</code> با نسخه و sha256 هر فایل داشته باشد.</p>';
        echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('amol_patch_upload');
        echo '<input type="hidden" name="action" value="amol_patch_upload"><input type="file" name="amol_patch" accept=".zip,application/zip" required> ';
        submit_button('بارگذاری و اعمال', 'secondary', 'submit', false);
        echo '</form></div>';

        if ($status['history']) {
            echo '<div class="amol-card"><h2>تاریخچه</h2><table class="widefat striped amol-status"><thead><tr><th>عملیات</th><th>نسخه</th><th>زمان</th><th>فایل‌ها</th></tr></thead><tbody>';

            foreach ($status['history'] as $event) {
                echo '<tr><td>' . esc_html(($event['type'] ?? '') === 'rollback' ? 'بازگردانی' : 'اعمال پچ') . '</td><td dir="ltr">' . esc_html((string) ($event['version'] ?? '')) . '</td><td dir="ltr">' . esc_html(! empty($event['date']) ? wp_date('Y/m/d H:i', (int) $event['date']) : '') . '</td><td>' . (int) ($event['files'] ?? 0) . '</td></tr>';
            }

            echo '</tbody></table></div>';
        }
    }

    protected static function tabTools(): void
    {
        $blogPage = (int) get_option('page_for_posts');
        $frontPage = (int) get_option('page_on_front');
        $permalink = (string) get_option('permalink_structure');
        $uploads = wp_upload_dir();
        $indexFile = trailingslashit((string) $uploads['basedir']) . 'amolnovin/search-index.js';

        echo '<div class="amol-card"><h2>وضعیت سایت</h2><table class="widefat striped amol-status"><tbody>';

        $rows = [
            ['نسخه‌ی قالب', Patcher::currentVersion()],
            ['وردپرس', get_bloginfo('version')],
            ['PHP', PHP_VERSION],
            ['ساختار پیوندهای یکتا', $permalink !== '' ? $permalink : 'ساده (بدون پیوند یکتا)'],
            ['برگه‌ی صفحه‌ی اصلی', $frontPage ? (string) get_permalink($frontPage) : 'آخرین نوشته‌ها'],
            ['برگه‌ی نوشته‌ها (وبلاگ)', $blogPage ? (string) get_permalink($blogPage) : 'انتخاب نشده'],
            ['صفحه‌ی فروشگاه', MarketManager::storeUrl()],
            ['فهرست جست‌وجوی محلی', is_file($indexFile) ? 'ساخته شده' : 'ساخته نشده (با دکمه‌ی زیر بسازید)'],
        ];

        foreach ($rows as [$label, $value]) {
            echo '<tr><th scope="row" style="width:260px">' . esc_html($label) . '</th><td dir="auto"><code>' . esc_html($value) . '</code></td></tr>';
        }

        echo '</tbody></table>';

        if ($permalink === '') {
            echo '<div class="notice notice-warning inline"><p>پیوندهای یکتا روی «ساده» است؛ برای کار کردن <code>/blog-n/</code> و <code>/sitemap.xml</code> در <a href="' . esc_url(admin_url('options-permalink.php')) . '">تنظیمات ← پیوندهای یکتا</a> یکی از حالت‌های «نام نوشته» را انتخاب کنید.</p></div>';
        }

        echo '</div>';

        // quick actions
        echo '<div class="amol-card"><h2>کارهای سریع</h2><div class="amol-inline">';
        echo '<a class="button" href="' . esc_url(admin_url('options-reading.php')) . '">تنظیمات خواندن (وبلاگ)</a>';
        echo '<a class="button" href="' . esc_url(admin_url('options-permalink.php')) . '">پیوندهای یکتا</a>';
        echo '<a class="button" href="' . esc_url(admin_url('customize.php?autofocus[panel]=amol')) . '">سفارشی‌ساز (برند و تماس)</a>';
        echo '<a class="button" href="' . esc_url(admin_url('themes.php?page=amol-data')) . '">داده‌های اشتراکی قالب</a>';
        echo '<a class="button" href="' . esc_url(admin_url('tools.php?page=amol-import')) . '">ورود محتوای نمونه</a>';
        echo '</div><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="amol-inline" style="margin-top:10px">';
        wp_nonce_field('amol_rebuild_index');
        echo '<input type="hidden" name="action" value="amol_rebuild_index">';
        submit_button('بازسازی فهرست جست‌وجو و نقشه‌ی سایت', 'secondary', 'submit', false);
        echo '<span class="description">فایل <code>search-index.js</code> و کش نقشه‌ی سایت از محتوای فعلی بازسازی می‌شوند.</span></form></div>';

        // snippets
        if (Plugins::snippets()) {
            $choices = Snippets::choices();
            echo '<div class="amol-card"><h2>قطعه کدهای سفارشی</h2>';

            if ($choices) {
                echo '<p>' . count($choices) . ' قطعه کد منتشرشده. برای نمایش، شورت‌کد را در محتوا بگذارید یا از بخش «قطعه کد سفارشی» استفاده کنید.</p><ul style="list-style:disc;padding-inline-start:20px">';

                foreach (array_slice($choices, 0, 10, true) as $id => $title) {
                    echo '<li>' . esc_html($title) . ' — <code>[my_snippet id="' . (int) $id . '"]</code></li>';
                }

                echo '</ul>';
            } else {
                echo '<p class="description">هنوز قطعه کدی ساخته نشده است.</p>';
            }

            echo '<p><a class="button" href="' . esc_url(admin_url('edit.php?post_type=custom_snippet')) . '">مدیریت قطعه کدها</a></p></div>';
        }

        // the theme's own docs
        echo '<div class="amol-card"><h2>راهنما</h2><ul style="list-style:disc;padding-inline-start:20px">';
        echo '<li><code>wordpress-theme/docs/THEME-SETTINGS.md</code> — همین پنل</li>';
        echo '<li><code>wordpress-theme/docs/UPDATES.md</code> — انتشار و بروزرسانی با پچ</li>';
        echo '<li><code>wordpress-theme/amolnovin/README.md</code> — مدیریت محتوا و توسعه</li>';
        echo '</ul></div>';
    }

    /** Rebuild the client-side search index and flush the sitemap cache. */
    public static function rebuildIndex(): void
    {
        check_admin_referer('amol_rebuild_index');
        self::requireUpdater();

        \App\Seo\Feeds::writeSearchIndex();
        self::redirect('tools', 'success', 'فهرست جست‌وجو از محتوای فعلی بازسازی شد.');
    }

    // ---------------------------------------------------------------- save

    public static function save(): void
    {
        check_admin_referer('amol_save_options');

        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('دسترسی ندارید.', 'amolnovin'));
        }

        $group = isset($_POST['group']) ? sanitize_key((string) wp_unslash($_POST['group'])) : '';

        if (! array_key_exists($group, Options::defaults())) {
            self::redirect('speed', 'error', 'بخش تنظیمات ناشناخته است.');
        }

        $raw = isset($_POST[self::OPTION_GROUP][$group]) && is_array($_POST[self::OPTION_GROUP][$group])
            ? wp_unslash($_POST[self::OPTION_GROUP][$group]) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field by field in sanitize().
            : [];

        Options::saveSection($group, self::sanitize($group, (array) $raw));

        self::redirect($group, 'success', 'تنظیمات ذخیره شد.');
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    protected static function sanitize(string $group, array $in): array
    {
        $defaults = Options::defaults()[$group] ?? [];
        $out = [];

        $enums = [
            'heartbeat' => ['default', 'slow', 'disabled'],
            'captcha' => ['auto', 'on', 'off'],
            'captcha_comments' => ['auto', 'on', 'off'],
            'store_source' => ['auto', 'plugin', 'theme'],
            'security_headers' => ['auto', 'on', 'off'],
            'channel' => ['stable', 'beta'],
        ];

        // values that only the updater writes are never posted by the form
        $internal = ['last_check', 'last_version', 'store_page_id'];

        foreach ($defaults as $key => $default) {
            if (in_array($key, $internal, true)) {
                $out[$key] = Options::get($group, $key, $default);
                continue;
            }

            if ($key === 'home' || $key === 'portfolio') {
                $out[$key] = self::rows((string) ($in[$key] ?? ''));
                continue;
            }

            $value = $in[$key] ?? null;

            if (is_bool($default)) {
                $out[$key] = ! empty($value);
                continue;
            }

            if (is_int($default)) {
                $out[$key] = max(0, (int) $value);
                continue;
            }

            $value = is_scalar($value) ? (string) $value : '';

            if (isset($enums[$key])) {
                $out[$key] = in_array($value, $enums[$key], true) ? $value : (string) $default;
                continue;
            }

            if ($key === 'organization_type') {
                $allowed = ['ProfessionalService', 'LocalBusiness', 'Organization', 'Store', 'Corporation'];
                $out[$key] = in_array($value, $allowed, true) ? $value : 'ProfessionalService';
                continue;
            }

            $out[$key] = self::text($key, $value);
        }

        return $out;
    }

    protected static function text(string $key, string $value): string
    {
        $code = ['head_code', 'body_code', 'footer_code'];
        $urls = ['cta_url', 'announcement_url', 'og_default', 'manifest_url'];

        if (in_array($key, $code, true)) {
            return current_user_can('unfiltered_html') ? trim($value) : (string) wp_kses_post(trim($value));
        }

        if (in_array($key, $urls, true)) {
            return (string) esc_url_raw(trim($value));
        }

        $textarea = ['robots_extra', 'preconnect', 'announcement', 'footer_about', 'success_message', 'error_message', 'captcha_message'];

        if (in_array($key, $textarea, true)) {
            return (string) sanitize_textarea_field($value);
        }

        return (string) sanitize_text_field($value);
    }

    /**
     * «value|suffix|label» lines → repeatable rows (max 8).
     *
     * @return array<int,array{value:string,suffix:string,label:string}>
     */
    protected static function rows(string $raw): array
    {
        $rows = [];

        foreach (preg_split('/[\r\n]+/', $raw) ?: [] as $line) {
            $line = trim((string) preg_replace('/\s*\|\s*/', '|', $line));

            if ($line === '') {
                continue;
            }

            $parts = explode('|', $line);
            $value = sanitize_text_field($parts[0] ?? '');
            $suffix = sanitize_text_field($parts[1] ?? '');
            $label = sanitize_text_field($parts[2] ?? '');

            if ($value === '' || $label === '') {
                continue;
            }

            $rows[] = ['value' => $value, 'suffix' => $suffix, 'label' => $label];

            if (count($rows) >= 8) {
                break;
            }
        }

        return $rows;
    }

    // ---------------------------------------------------------------- integrations action

    public static function syncStore(): void
    {
        check_admin_referer('amol_store_sync');

        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('دسترسی ندارید.', 'amolnovin'));
        }

        $pageId = isset($_POST['page_id']) ? absint($_POST['page_id']) : 0;

        if (! $pageId || ! MarketManager::syncStorePage($pageId)) {
            self::redirect('integrations', 'error', 'اتصال صفحه‌ی فروشگاه انجام نشد. افزونه‌ی فروشگاه فعال است؟');
        }

        $group = Options::section('integrations');
        $group['store_page_id'] = $pageId;
        Options::saveSection('integrations', $group);

        self::redirect('integrations', 'success', 'صفحه‌ی فروشگاه به افزونه معرفی شد.');
    }

    // ---------------------------------------------------------------- updater actions

    public static function patchCheck(): void
    {
        check_admin_referer('amol_patch_check');
        self::requireUpdater();

        $manifest = Patcher::fetchManifest(true);

        if (is_wp_error($manifest)) {
            self::redirect('updates', 'error', $manifest->get_error_message());
        }

        $available = (int) count(array_filter((array) ($manifest['releases'] ?? []), static function ($release) {
            return is_array($release) && ! empty($release['version']) && Patcher::isNewer((string) $release['version'], Patcher::currentVersion());
        }));

        self::redirect('updates', 'success', $available > 0
            ? sprintf('%d بروزرسانی در دسترس است.', $available)
            : 'قالب به‌روز است؛ نسخه‌ی جدیدی منتشر نشده است.');
    }

    public static function patchApply(): void
    {
        check_admin_referer('amol_patch_apply');
        self::requireUpdater();

        $version = isset($_POST['version']) ? Patcher::cleanVersion((string) wp_unslash($_POST['version'])) : '';
        $result = Patcher::apply($version);

        if (is_wp_error($result)) {
            self::redirect('updates', 'error', $result->get_error_message());
        }

        self::redirect('updates', 'success', sprintf(
            'نسخه‌ی %s اعمال شد (%d فایل، %d حذف). پشتیبان: %s',
            $result['version'],
            (int) $result['files'],
            (int) $result['deleted'],
            $result['backup']
        ));
    }

    public static function patchRollback(): void
    {
        check_admin_referer('amol_patch_rollback');
        self::requireUpdater();

        $version = isset($_POST['version']) ? Patcher::cleanVersion((string) wp_unslash($_POST['version'])) : '';
        $result = Patcher::rollback($version);

        if (is_wp_error($result)) {
            self::redirect('updates', 'error', $result->get_error_message());
        }

        self::redirect('updates', 'success', sprintf('نسخه‌ی %s بازگردانی شد (%d فایل).', $result['version'], (int) $result['files'])) ;
    }

    public static function patchUpload(): void
    {
        check_admin_referer('amol_patch_upload');
        self::requireUpdater();

        if (! isset($_FILES['amol_patch']) || ! is_array($_FILES['amol_patch'])) {
            self::redirect('updates', 'error', 'فایلی انتخاب نشده است.');
        }

        $file = $_FILES['amol_patch']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- uploaded file, validated below.

        if (! empty($file['error'])) {
            self::redirect('updates', 'error', 'بارگذاری فایل ناموفق بود (کد ' . (int) $file['error'] . ').');
        }

        $name = sanitize_file_name((string) ($file['name'] ?? ''));

        if (! str_ends_with(strtolower($name), '.zip')) {
            self::redirect('updates', 'error', 'فقط بسته‌ی ZIP پچ پذیرفته می‌شود.');
        }

        if (! is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            self::redirect('updates', 'error', 'مسیر فایل بارگذاری‌شده معتبر نیست.');
        }

        $result = Patcher::applyUpload((string) $file['tmp_name']);

        if (is_wp_error($result)) {
            self::redirect('updates', 'error', $result->get_error_message());
        }

        self::redirect('updates', 'success', sprintf('نسخه‌ی %s از بسته‌ی آپلودی اعمال شد (%d فایل).', $result['version'], (int) $result['files']));
    }

    protected static function requireUpdater(): void
    {
        if (! current_user_can('update_themes') && ! current_user_can('manage_options')) {
            wp_die(esc_html__('برای بروزرسانی قالب دسترسی کافی ندارید.', 'amolnovin'));
        }
    }

    /** Redirect back to a tab with a flash message (kept in a transient so long paths/messages never break). */
    protected static function redirect(string $tab, string $type, string $message): void
    {
        set_transient('amol_settings_notice', ['type' => $type, 'message' => $message], 120);
        wp_safe_redirect(self::url($tab));
        exit;
    }

    // ---------------------------------------------------------------- notices

    /** Daily, non-blocking check for a newer patch (only for users who can update the theme). */
    public static function autoCheck(): void
    {
        if (! Options::on('updates', 'auto_check') || ! current_user_can('update_themes') || wp_doing_ajax()) {
            return;
        }

        $last = (int) Options::get('updates', 'last_check', 0);

        if ($last > 0 && (time() - $last) < 12 * HOUR_IN_SECONDS) {
            return;
        }

        $manifest = Patcher::fetchManifest(true);

        if (is_wp_error($manifest)) {
            // remember the attempt so a broken URL is not retried on every page load
            $group = Options::section('updates');
            $group['last_check'] = time();
            Options::saveSection('updates', $group);

            return;
        }
    }

    public static function updateNotice(): void
    {
        if (! current_user_can('update_themes') || ! Options::on('updates', 'auto_check')) {
            return;
        }

        // only use the cached catalogue: an admin page must never wait for a network request
        if (! is_array(get_transient(Patcher::CACHE))) {
            return;
        }

        $available = Patcher::available(false);

        if (is_wp_error($available) || ! $available) {
            return;
        }

        $latest = $available[0];

        echo '<div class="notice notice-warning"><p><strong>قالب آمل نوین:</strong> نسخه‌ی ' . esc_html((string) $latest['_version']) . ' با پچ در دسترس است. <a href="' . esc_url(self::url('updates')) . '">مشاهده و اعمال بروزرسانی</a></p></div>';
    }
}
