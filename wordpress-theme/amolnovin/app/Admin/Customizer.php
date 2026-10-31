<?php

namespace App\Admin;

use App\Support\Site;

/**
 * Appearance → Customize → «آمل نوین»: brand, contact, prices, social links, footer cities, tracking.
 * All values are stored in one theme_mod array (`amol_site`) read by App\Support\Site.
 */
class Customizer
{
    public static function boot(): void
    {
        add_action('customize_register', [self::class, 'register']);
        add_action('customize_save_after', [Site::class, 'flush']);
        add_action('wp_head', [self::class, 'gtmHead'], 3);
        add_action('wp_body_open', [self::class, 'gtmBody'], 1);
    }

    protected static function field(\WP_Customize_Manager $wp, string $section, string $key, string $label, string $type = 'text', array $extra = [], string $sanitize = 'sanitize_text_field', ?string $setting = null): void
    {
        $id = $setting ?? "amol_site[{$key}]";
        $wp->add_setting($id, ['type' => 'theme_mod', 'default' => $extra['default'] ?? '', 'sanitize_callback' => $sanitize, 'transport' => 'refresh']);
        $wp->add_control($id, ['label' => $label, 'section' => $section, 'type' => $type, 'description' => $extra['description'] ?? '', 'input_attrs' => $extra['input_attrs'] ?? []]);
    }

    public static function register(\WP_Customize_Manager $wp): void
    {
        $d = Site::defaults();
        $wp->add_panel('amol', ['title' => 'آمل نوین', 'priority' => 30, 'description' => 'اطلاعات برند و تماس که در سراسر سایت (هدر، فوتر، فرم‌ها، Schema و سئو) استفاده می‌شود.']);

        // ---- brand
        $wp->add_section('amol_brand', ['title' => 'برند', 'panel' => 'amol', 'priority' => 10]);
        self::field($wp, 'amol_brand', 'name', 'نام کامل شرکت', 'text', ['default' => $d['name'] ?? '']);
        self::field($wp, 'amol_brand', 'short', 'نام کوتاه (لوگو)', 'text', ['default' => $d['short'] ?? '']);
        self::field($wp, 'amol_brand', 'en_name', 'نام انگلیسی', 'text', ['default' => $d['en_name'] ?? '']);
        self::field($wp, 'amol_brand', 'tagline', 'شعار', 'text', ['default' => $d['tagline'] ?? '']);
        self::field($wp, 'amol_brand', 'founder', 'مدیر مجموعه', 'text', ['default' => $d['founder'] ?? '']);
        self::field($wp, 'amol_brand', 'founded_j', 'سال تأسیس (شمسی)', 'number', ['default' => $d['founded_j'] ?? ''], 'absint');
        self::field($wp, 'amol_brand', 'founded', 'سال تأسیس (میلادی)', 'number', ['default' => $d['founded'] ?? ''], 'absint');
        self::field($wp, 'amol_brand', 'support_months', 'ماه‌های پشتیبانی رایگان', 'number', ['default' => $d['support_months'] ?? ''], 'absint');

        // ---- contact
        $wp->add_section('amol_contact', ['title' => 'تماس و نشانی', 'panel' => 'amol', 'priority' => 20]);
        self::field($wp, 'amol_contact', 'phone', 'شماره‌ی تماس (موبایل)', 'text', ['default' => $d['phone'] ?? '', 'description' => 'مثل 09332021365 — قالب خودش نمای 0933 202 1365، لینک tel: و واتساپ را می‌سازد.', 'input_attrs' => ['dir' => 'ltr']]);
        self::field($wp, 'amol_contact', 'whatsapp', 'لینک واتساپ (اختیاری)', 'url', ['description' => 'خالی بگذارید تا از روی شماره ساخته شود.', 'input_attrs' => ['dir' => 'ltr']], 'esc_url_raw');
        self::field($wp, 'amol_contact', 'email', 'ایمیل', 'email', ['default' => $d['email'] ?? '', 'input_attrs' => ['dir' => 'ltr']], 'sanitize_email');
        self::field($wp, 'amol_contact', 'region', 'استان', 'text', ['default' => $d['region'] ?? '']);
        self::field($wp, 'amol_contact', 'city', 'شهر', 'text', ['default' => $d['city'] ?? '']);
        self::field($wp, 'amol_contact', 'street', 'خیابان و پلاک', 'text', ['default' => $d['street'] ?? '']);
        self::field($wp, 'amol_contact', 'geo_lat', 'عرض جغرافیایی', 'text', ['default' => $d['geo'][0] ?? '', 'input_attrs' => ['dir' => 'ltr']]);
        self::field($wp, 'amol_contact', 'geo_lng', 'طول جغرافیایی', 'text', ['default' => $d['geo'][1] ?? '', 'input_attrs' => ['dir' => 'ltr']]);
        self::field($wp, 'amol_contact', 'response_time', 'زمان پاسخ‌گویی', 'text', ['default' => $d['response_time'] ?? '']);
        self::field($wp, 'amol_contact', 'lead_email', 'ایمیل دریافت درخواست‌های فرم', 'email', ['description' => 'خالی = ایمیل بالا. درخواست‌ها همیشه در پیشخوان (درخواست‌ها) هم ذخیره می‌شوند.', 'input_attrs' => ['dir' => 'ltr']], 'sanitize_email', 'amol_lead_email');

        // ---- prices
        $wp->add_section('amol_prices', ['title' => 'قیمت‌ها و آمار', 'panel' => 'amol', 'priority' => 30, 'description' => 'قیمت‌ها به میلیون تومان؛ در تیتر خدمات، جدول‌ها، Schema و صفحه‌ی اصلی نمایش داده می‌شوند.']);
        self::field($wp, 'amol_prices', 'price_design', 'طراحی سایت از', 'number', ['default' => $d['price_design'] ?? ''], 'absint');
        self::field($wp, 'amol_prices', 'price_support', 'پشتیبانی از (در ماه)', 'number', ['default' => $d['price_support'] ?? ''], 'absint');
        self::field($wp, 'amol_prices', 'price_seo', 'سئو از (در ماه)', 'number', ['default' => $d['price_seo'] ?? ''], 'absint');

        // ---- social
        $wp->add_section('amol_social', ['title' => 'شبکه‌های اجتماعی', 'panel' => 'amol', 'priority' => 40, 'description' => 'آدرس کامل؛ فقط موارد پرشده در فوتر و Schema نمایش داده می‌شوند.']);
        foreach (['instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'youtube' => 'یوتیوب', 'x' => 'X (توییتر)', 'github' => 'گیت‌هاب', 'eitaa' => 'ایتا', 'bale' => 'بله'] as $k => $l) {
            self::field($wp, 'amol_social', "social_{$k}", $l, 'url', ['input_attrs' => ['dir' => 'ltr']], 'esc_url_raw', "amol_site[social][{$k}]");
        }

        // ---- layout
        $wp->add_section('amol_layout', ['title' => 'چیدمان', 'panel' => 'amol', 'priority' => 45]);
        $wp->add_setting('amol_tablet_menu', ['type' => 'theme_mod', 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean', 'transport' => 'refresh']);
        $wp->add_control('amol_tablet_menu', [
            'label' => 'منوی همبرگری روی تبلت و لپ‌تاپ‌های کوچک (۱۰۲۱ تا ۱۲۳۵ پیکسل)',
            'description' => 'در قالب HTML اصلی، در این بازه منوی کامل از عرض صفحه بیرون می‌زند و اسکرول افقی ایجاد می‌شود (مثلاً iPad افقی). با روشن بودن این گزینه همان منوی همبرگری موبایل نمایش داده می‌شود. برای نمایش دقیقاً مثل قالب HTML خاموشش کنید.',
            'section' => 'amol_layout', 'type' => 'checkbox',
        ]);

        // ---- footer + tracking
        $wp->add_section('amol_footer', ['title' => 'فوتر و ردیابی', 'panel' => 'amol', 'priority' => 50]);
        self::field($wp, 'amol_footer', 'cities', 'شهرهای فوتر (هر خط یک شهر)', 'textarea', ['default' => implode("\n", \App\Support\Data::seed('site')['cities'] ?? []), 'input_attrs' => ['rows' => 6]], 'sanitize_textarea_field', 'amol_cities');
        self::field($wp, 'amol_footer', 'gtm', 'شناسه‌ی Google Tag Manager (اختیاری)', 'text', ['description' => 'مثل GTM-XXXXXXX — رویدادهای کلیک تماس و واتساپ از قبل در dataLayer ارسال می‌شوند.', 'input_attrs' => ['dir' => 'ltr']], 'sanitize_text_field', 'amol_gtm_id');
    }

    protected static function gtm(): string
    {
        $id = trim((string) get_theme_mod('amol_gtm_id', ''));

        return preg_match('/^GTM-[A-Z0-9]+$/', $id) ? $id : '';
    }

    public static function gtmHead(): void
    {
        if ($id = self::gtm()) {
            echo "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . esc_js($id) . "');</script>\n";
        }
    }

    public static function gtmBody(): void
    {
        if ($id = self::gtm()) {
            echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr($id) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
        }
    }
}
