<?php

namespace App\Admin;

use App\Content\PageData;
use App\Support\Icons;
use Illuminate\Support\Facades\Vite;

/**
 * Structured data editor.
 *
 * Every page, website type, project, plugin and post keeps its structured content (hero, sections, FAQ, features …)
 * as JSON in post meta. This module renders a form for that JSON (fields, repeatable cards, reordering, «add section»)
 * so editors never touch raw JSON — a raw-JSON tab is there for power users.
 */
class DataEditor
{
    public const KINDS = [
        'prose' => 'متن ساده (Prose)', 'split' => 'متن + کارت یا تصویر', 'cards' => 'کارت‌ها', 'steps' => 'مراحل', 'checklist' => 'چک‌لیست', 'table' => 'جدول',
        'stats' => 'آمار', 'faq' => 'پرسش‌های متداول', 'links' => 'لینک‌کارت‌ها', 'tech' => 'فناوری‌ها', 'plans' => 'پلن‌ها و قیمت', 'cta' => 'دعوت به اقدام',
        'types' => 'انواع سایت', 'projects' => 'نمونه‌کارها', 'vs' => 'مقایسه', 'form' => 'فرم مشاوره', 'posts' => 'مقالات', 'callout' => 'اطلاعیه',
        'modules' => 'سرفصل‌ها (آکاردئون)', 'team' => 'تیم', 'license' => 'پروانه‌ی کسب', 'facts' => 'واقعیت‌ها', 'timeline' => 'خط زمان', 'calc' => 'ماشین‌حساب هزینه',
        'contact' => 'راه‌های تماس', 'chooser' => 'پیشنهاد هوشمند', 'html' => 'HTML خام', 'snippet' => 'قطعه کد سفارشی (Custom HTML Snippets)',
    ];

    protected const PAGE_TYPES = ['page', 'website_type', 'project', 'wplm_product', 'post'];

    public static function skeletons(): array
    {
        $head = ['eyebrow' => '', 'title' => '', 'desc' => '', 'id' => '', 'nav' => '', 'alt' => false, 'center' => false];

        return [
            'prose' => ['kind' => 'prose', 'eyebrow' => '', 'title' => '', 'html' => '', 'alt' => false, 'id' => ''],
            'split' => ['kind' => 'split', 'id' => '', 'nav' => '', 'alt' => false, 'eyebrow' => '', 'title' => '', 'cls' => 'split--wide-left', 'html' => '', 'points_title' => '', 'points' => [''], 'cta' => ['', '/contact-us/']],
            'cards' => ['kind' => 'cards'] + $head + ['cols' => 3, 'items' => [['icon' => 'check', 'title' => '', 'text' => '', 'color' => '', 'href' => '', 'cta' => '']]],
            'steps' => ['kind' => 'steps'] + $head + ['items' => [['title' => '', 'text' => '', 'time' => '']]],
            'checklist' => ['kind' => 'checklist'] + $head + ['items' => ['']],
            'table' => ['kind' => 'table'] + $head + ['hl' => 1, 'head' => ['', ''], 'rows' => [['', '']], 'note' => ''],
            'stats' => ['kind' => 'stats', 'dark' => false, 'items' => [['n' => 0, 'suffix' => '', 'label' => '', 'small' => '']]],
            'faq' => ['kind' => 'faq', 'id' => 'faq', 'nav' => '', 'alt' => false, 'eyebrow' => '', 'title' => '', 'desc' => '', 'open_first' => false, 'items' => [['', '']]],
            'links' => ['kind' => 'links'] + $head + ['items' => [['href' => '/', 'title' => '', 'text' => '', 'icon' => '', 'color' => '']]],
            'tech' => ['kind' => 'tech'] + $head + ['items' => [['', 'check', '#13a89e']]],
            'plans' => ['kind' => 'plans'] + $head + ['note' => '', 'items' => [['name' => '', 'desc' => '', 'price' => '', 'unit' => '', 'features' => [''], 'href' => '/contact-us/', 'cta' => '', 'pop' => false, 'flag' => '']]],
            'cta' => ['kind' => 'cta', 'title' => '', 'text' => '', 'primary' => ['دریافت مشاوره‌ی رایگان', '/contact-us/'], 'secondary' => ['', '']],
            'types' => ['kind' => 'types'] + $head + ['limit' => 0, 'link' => true],
            'projects' => ['kind' => 'projects'] + $head + ['slugs' => ['']],
            'vs' => ['kind' => 'vs'] + $head + ['cols' => [['title' => '', 'text' => '', 'icon' => 'check', 'win' => false, 'points' => ['']]]],
            'form' => ['kind' => 'form', 'id' => 'consult', 'eyebrow' => '', 'title' => '', 'desc' => '', 'service' => '', 'form_title' => '', 'fid' => '', 'points' => ['']],
            'posts' => ['kind' => 'posts'] + $head + ['slugs' => ['']],
            'callout' => ['kind' => 'callout', 'icon' => 'info', 'text' => '', 'warn' => false],
            'modules' => ['kind' => 'modules'] + $head + ['items' => [['', '', ['']]]],
            'team' => ['kind' => 'team'] + $head,
            'license' => ['kind' => 'license', 'id' => '', 'nav' => '', 'eyebrow' => '', 'title' => '', 'text' => '', 'points' => ['']],
            'facts' => ['kind' => 'facts', 'alt' => false, 'items' => [['info', '', '']]],
            'timeline' => ['kind' => 'timeline'] + $head + ['items' => [['', '', '']]],
            'calc' => ['kind' => 'calc', 'nav' => ''],
            'contact' => ['kind' => 'contact'],
            'chooser' => ['kind' => 'chooser', 'id' => 'chooser', 'nav' => ''],
            'html' => ['kind' => 'html', 'html' => ''],
            'snippet' => ['kind' => 'snippet', 'id' => '', 'nav' => '', 'alt' => false, 'eyebrow' => '', 'title' => '', 'desc' => '', 'center' => false, 'snippet' => 0],
        ];
    }

    public static function boot(): void
    {
        add_action('add_meta_boxes', [self::class, 'boxes']);
        add_action('save_post', [self::class, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
    }

    // ---------------------------------------------------------------- assets
    public static function config(): array
    {
        return ['kinds' => self::KINDS, 'skeletons' => self::skeletons(), 'icons' => array_keys(Icons::all())];
    }

    public static function assets(string $hook): void
    {
        $screen = get_current_screen();
        $ours = in_array($hook, ['post.php', 'post-new.php'], true) && $screen && in_array($screen->post_type, self::PAGE_TYPES, true);
        $ours = $ours || $hook === 'appearance_page_amol-data';

        if (! $ours) {
            return;
        }

        try {
            wp_enqueue_style('amol-admin', Vite::asset('resources/css/admin.css'), [], (string) wp_get_theme()->get('Version'));
            wp_enqueue_script_module('amol-admin', Vite::asset('resources/js/admin.js'), [], null);
        } catch (\Throwable) {
            return; // assets not built yet
        }

        wp_add_inline_script('jquery-core', 'window.AMOL_ADMIN=' . wp_json_encode(self::config(), JSON_UNESCAPED_UNICODE) . ';', 'before');
    }

    // ---------------------------------------------------------------- meta boxes
    public static function boxes(): void
    {
        foreach (self::PAGE_TYPES as $type) {
            add_meta_box('amol-data', 'محتوای ساختاری — آمل نوین', [self::class, 'render'], $type, 'normal', 'default');
        }
    }

    /** Which JSON documents does a post type have? */
    protected static function documents(\WP_Post $post): array
    {
        return match ($post->post_type) {
            'page' => ['_amol_page' => 'تعریف صفحه: Hero، بخش‌ها، پرسش‌های متداول و …'],
            'website_type', 'project' => ['_amol_entity' => 'اطلاعات اصلی', '_amol_page' => 'تنظیمات صفحه (Hero، متن خلاصه، Schema)'],
            default => ['_amol_entity' => 'اطلاعات تکمیلی'],
        };
    }

    public static function render(\WP_Post $post): void
    {
        wp_nonce_field('amol_data', 'amol_data_nonce');
        echo '<p class="description">این بخش‌ها همان داده‌هایی هستند که صفحه از آن‌ها ساخته می‌شود. ورودی‌ها را مستقیم ویرایش کنید؛ ردیف‌ها را با پیکان‌ها جابه‌جا و با «×» حذف کنید.</p>';

        foreach (self::documents($post) as $key => $label) {
            $raw = get_post_meta($post->ID, $key, true);
            $json = is_string($raw) && $raw !== '' ? $raw : ($key === '_amol_page' ? '{"kind":"generic","eyebrow":"","lead":"","sections":[]}' : '{}');
            echo '<h3 class="amol-ed__title">' . esc_html($label) . '</h3>';
            echo '<div class="amol-ed" data-key="' . esc_attr($key) . '"><textarea class="amol-ed__json" name="amol_json[' . esc_attr($key) . ']" hidden>' . esc_textarea($json) . '</textarea><div class="amol-ed__ui"></div></div>';
        }
    }

    // ---------------------------------------------------------------- saving
    /** @return mixed sanitised copy (no raw HTML for editors without unfiltered_html) */
    public static function clean(mixed $data): mixed
    {
        if (current_user_can('unfiltered_html')) {
            return $data;
        }

        if (is_string($data)) {
            return wp_kses_post($data);
        }

        return is_array($data) ? array_map([self::class, 'clean'], $data) : $data;
    }

    public static function save(int $id, \WP_Post $post): void
    {
        if (! isset($_POST['amol_data_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['amol_data_nonce'])), 'amol_data')) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ! current_user_can('edit_post', $id) || empty($_POST['amol_json']) || ! is_array($_POST['amol_json'])) {
            return;
        }

        foreach (array_keys(self::documents($post)) as $key) {
            if (! isset($_POST['amol_json'][$key])) {
                continue;
            }

            $data = json_decode((string) wp_unslash($_POST['amol_json'][$key]), true);

            if (! is_array($data)) {
                continue; // invalid JSON: keep the stored version
            }

            $data = self::clean($data);

            if ($key === '_amol_page' && in_array($data['kind'] ?? 'generic', ['generic', 'service'], true)) {
                $faq = PageData::faqFromSections($data['sections'] ?? []);
                $faq ? $data['faq'] = $faq : null;
            }

            update_post_meta($id, $key, wp_slash((string) wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        }

        \App\Content\Repo::flush();
    }
}
