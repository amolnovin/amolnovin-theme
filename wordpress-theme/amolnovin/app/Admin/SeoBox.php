<?php

namespace App\Admin;

use App\Seo\Head;

/**
 * «سئو» meta box: title, description, H1, focus keyword, noindex — for pages, posts, website types and projects.
 * Hidden when a dedicated SEO plugin is active (the plugin owns those fields then).
 */
class SeoBox
{
    protected const TYPES = ['page', 'post', 'website_type', 'project'];

    protected const FIELDS = ['_amol_seo_title', '_amol_seo_desc', '_amol_h1', '_amol_kw'];

    public static function boot(): void
    {
        add_action('add_meta_boxes', function () {
            if (Head::pluginActive()) {
                return;
            }

            foreach (self::TYPES as $type) {
                add_meta_box('amol-seo', 'سئو — آمل نوین', [self::class, 'render'], $type, 'normal', 'high');
            }
        });
        add_action('save_post', [self::class, 'save'], 10, 2);
    }

    public static function render(\WP_Post $post): void
    {
        wp_nonce_field('amol_seo', 'amol_seo_nonce');
        ?>
        <style>.amol-seo label{display:block;font-weight:600;margin:12px 0 4px}.amol-seo input[type=text],.amol-seo textarea{width:100%}.amol-seo small{color:#646970}</style>
        <div class="amol-seo">
            <label for="amol_seo_title">عنوان سئو (تگ title)</label>
            <input type="text" id="amol_seo_title" name="amol_seo_title" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_amol_seo_title', true)); ?>" maxlength="120">
            <small>پیشنهاد: ۴۰ تا ۶۰ نویسه؛ خالی = عنوان خودکار.</small>

            <label for="amol_seo_desc">توضیح متا (description)</label>
            <textarea id="amol_seo_desc" name="amol_seo_desc" rows="3" maxlength="320"><?php echo esc_textarea((string) get_post_meta($post->ID, '_amol_seo_desc', true)); ?></textarea>
            <small>پیشنهاد: ۱۲۰ تا ۱۶۰ نویسه.</small>

            <label for="amol_h1">عنوان اصلی صفحه (H1) — می‌تواند <code>&lt;em&gt;</code> داشته باشد</label>
            <input type="text" id="amol_h1" name="amol_h1" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_amol_h1', true)); ?>">

            <label for="amol_kw">کلمه‌ی کلیدی اصلی</label>
            <input type="text" id="amol_kw" name="amol_kw" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_amol_kw', true)); ?>">

            <label><input type="checkbox" name="amol_noindex" value="1" <?php checked((bool) get_post_meta($post->ID, '_amol_noindex', true)); ?>> این صفحه در گوگل ایندکس نشود (noindex)</label>
        </div>
        <?php
    }

    public static function save(int $id, \WP_Post $post): void
    {
        if (! isset($_POST['amol_seo_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['amol_seo_nonce'])), 'amol_seo')) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ! current_user_can('edit_post', $id)) {
            return;
        }

        $map = ['amol_seo_title' => '_amol_seo_title', 'amol_seo_desc' => '_amol_seo_desc', 'amol_h1' => '_amol_h1', 'amol_kw' => '_amol_kw'];

        foreach ($map as $field => $meta) {
            $raw = isset($_POST[$field]) ? wp_unslash($_POST[$field]) : '';
            $val = $field === 'amol_h1' ? wp_kses($raw, ['em' => [], 'strong' => [], 'br' => []]) : sanitize_text_field($raw);
            $val === '' ? delete_post_meta($id, $meta) : update_post_meta($id, $meta, wp_slash($val));
        }

        empty($_POST['amol_noindex']) ? delete_post_meta($id, '_amol_noindex') : update_post_meta($id, '_amol_noindex', '1');
    }
}
