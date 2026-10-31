<?php

namespace App\Admin;

use App\Support\Data;

/**
 * Appearance → «داده‌های اشتراکی قالب»: lists shared by several pages (home page: technologies, why-us, process,
 * pricing cards, FAQ …; calculator; team; form options) edited with the structured editor.
 */
class ThemeData
{
    public const KEYS = [
        'rotator' => 'متن‌های متحرک هیرو', 'tech' => 'فناوری‌ها (نوار فناوری و …)', 'why' => 'چرا آمل نوین؟', 'process' => 'مراحل همکاری', 'plans' => 'کارت‌های قیمت',
        'home_faq' => 'پرسش‌های متداول صفحه‌ی اصلی', 'home_projects' => 'نمونه‌کارهای صفحه‌ی اصلی (slug)', 'home_posts' => 'مقالات صفحه‌ی اصلی (slug)',
        'calc' => 'ماشین‌حساب هزینه', 'service_options' => 'گزینه‌های «نوع درخواست» فرم‌ها', 'chooser_labels' => 'برچسب‌های پیشنهاد هوشمند', 'team' => 'اعضای تیم',
    ];

    public static function boot(): void
    {
        add_action('admin_menu', function () {
            add_theme_page('داده‌های اشتراکی قالب', 'داده‌های اشتراکی قالب', 'edit_theme_options', 'amol-data', [self::class, 'render']);
        });
        add_action('admin_post_amol_save_data', [self::class, 'save']);
    }

    public static function render(): void
    {
        $common = Data::common();
        $common['team'] = $common['team'] ?? (Data::seed('site')['team'] ?? []);
        ?>
        <div class="wrap amol-wrap">
            <h1>داده‌های اشتراکی قالب</h1>
            <?php if (! empty($_GET['saved'])) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag only ?><div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div><?php endif; ?>
            <p>این فهرست‌ها در چند صفحه (مخصوصاً صفحه‌ی اصلی) استفاده می‌شوند. هر بخش را باز کنید، ویرایش کنید و در پایان «ذخیره» را بزنید.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('amol_save_data'); ?>
                <input type="hidden" name="action" value="amol_save_data">
                <?php foreach (self::KEYS as $key => $label) : ?>
                    <details class="amol-acc"><summary><?php echo esc_html($label); ?> <code><?php echo esc_html($key); ?></code></summary>
                        <div class="amol-ed" data-key="<?php echo esc_attr($key); ?>"><textarea class="amol-ed__json" name="amol_json[<?php echo esc_attr($key); ?>]" hidden><?php echo esc_textarea((string) wp_json_encode($common[$key] ?? [], JSON_UNESCAPED_UNICODE)); ?></textarea><div class="amol-ed__ui"></div></div>
                    </details>
                <?php endforeach; ?>
                <p class="submit"><button class="button button-primary button-large">ذخیره‌ی تغییرات</button>
                    <button class="button" name="amol_reset" value="1" onclick="return confirm('همه‌ی داده‌ها به مقدار پیش‌فرض قالب برگردد؟')">بازگشت به پیش‌فرض</button></p>
            </form>
        </div>
        <?php
    }

    public static function save(): void
    {
        check_admin_referer('amol_save_data');

        if (! current_user_can('edit_theme_options')) {
            wp_die('دسترسی ندارید.');
        }

        if (! empty($_POST['amol_reset'])) {
            delete_option('amol_common');
        } else {
            $out = [];
            foreach (array_keys(self::KEYS) as $key) {
                if (isset($_POST['amol_json'][$key])) {
                    $data = json_decode((string) wp_unslash($_POST['amol_json'][$key]), true);
                    if (is_array($data)) {
                        $out[$key] = DataEditor::clean($data);
                    }
                }
            }
            update_option('amol_common', $out, false);
        }

        Data::flush();
        do_action('amol/content_imported');
        wp_safe_redirect(add_query_arg(['page' => 'amol-data', 'saved' => 1], admin_url('themes.php')));
        exit;
    }
}
