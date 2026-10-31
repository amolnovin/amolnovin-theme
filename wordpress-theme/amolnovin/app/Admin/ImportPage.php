<?php

namespace App\Admin;

/**
 * Tools → «ورود محتوای نمونه»: one click creates every page, website type, project, plugin, post and menu of the template.
 * An admin notice invites the site owner to do it right after the theme is activated.
 */
class ImportPage
{
    public static function boot(): void
    {
        add_action('admin_menu', function () {
            add_management_page('ورود محتوای نمونه آمل نوین', 'ورود محتوای نمونه آمل نوین', 'manage_options', 'amol-import', [self::class, 'render']);
        });
        add_action('admin_post_amol_import', [self::class, 'handle']);
        add_action('after_switch_theme', fn () => update_option('amol_show_import_notice', 1, false));
        add_action('admin_notices', [self::class, 'notice']);
    }

    public static function notice(): void
    {
        if (! get_option('amol_show_import_notice') || get_option('amol_imported') || ! current_user_can('manage_options')) {
            return;
        }

        $url = admin_url('tools.php?page=amol-import');
        echo '<div class="notice notice-info"><p><strong>قالب آمل نوین فعال شد.</strong> برای ساخت خودکار صفحه‌ها، نمونه‌کارها، مقاله‌ها و منوها (دقیقاً مثل قالب HTML) <a class="button button-primary" href="' . esc_url($url) . '">محتوای نمونه را وارد کنید</a></p></div>';
    }

    public static function render(): void
    {
        $done = get_transient('amol_import_result');
        delete_transient('amol_import_result');
        ?>
        <div class="wrap">
            <h1>ورود محتوای نمونه‌ی آمل نوین</h1>
            <?php if ($done) : ?>
                <div class="notice notice-success"><p>انجام شد: <?php echo esc_html(implode('، ', array_map(fn ($k, $v) => "{$v} {$k}", array_keys($done), $done))); ?></p></div>
            <?php endif; ?>
            <p>این ابزار ۲۰ صفحه، ۲۰ نوع سایت، ۲۰ نمونه‌کار، ۱۴ افزونه‌ی فروشگاه، ۵ مقاله و ۵ منو را می‌سازد و تنظیمات خواندن (صفحه‌ی اصلی، صفحه‌ی وبلاگ، حریم خصوصی) و پیوندهای یکتا را تنظیم می‌کند. اجرای دوباره امن است: موارد موجود با نامک (slug) تطبیق داده و به‌روزرسانی می‌شوند.</p>
            <p><strong>هشدار:</strong> اگر متن این صفحه‌ها را ویرایش کرده‌اید، اجرای دوباره آن‌ها را به نسخه‌ی اولیه‌ی قالب برمی‌گرداند.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('amol_import'); ?>
                <input type="hidden" name="action" value="amol_import">
                <label><input type="checkbox" name="reset" value="1" checked> نوشته‌ی نمونه‌ی «سلام دنیا» و برگه‌ی نمونه‌ی وردپرس حذف شود</label>
                <p class="submit"><button class="button button-primary button-hero">وارد کردن محتوا</button></p>
            </form>
            <p class="description">معادل خط فرمان: <code>wp amol import --reset</code></p>
        </div>
        <?php
    }

    public static function handle(): void
    {
        check_admin_referer('amol_import');

        if (! current_user_can('manage_options')) {
            wp_die('دسترسی ندارید.');
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(300);
        }
        $stats = Importer::run(['reset' => ! empty($_POST['reset'])]);
        update_option('amol_imported', time(), false);
        delete_option('amol_show_import_notice');
        set_transient('amol_import_result', $stats, 120);
        wp_safe_redirect(admin_url('tools.php?page=amol-import'));
        exit;
    }
}
