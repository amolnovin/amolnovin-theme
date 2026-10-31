<?php

namespace App\Admin;

use App\Support\Icons;

/**
 * Appearance → Menus: every item gets an icon and a colour (used by the mega menus), plus a short help box.
 *
 *   CSS class  mega-services   → opens the wide «services» panel with the promo box
 *   CSS class  mega-types      → opens the wide «website types» panel
 *   (no class) + child items   → compact dropdown (like «بیشتر»)
 *   CSS class  is-strong       → bold link (footer / mobile menu)
 */
class MenuFields
{
    public static function boot(): void
    {
        add_action('wp_nav_menu_item_custom_fields', [self::class, 'fields'], 10, 2);
        add_action('wp_update_nav_menu_item', [self::class, 'save'], 10, 2);
        add_action('admin_notices', [self::class, 'help']);
    }

    public static function fields(int $itemId, \WP_Post $item): void
    {
        $itemId = (int) $itemId;
        $icon = (string) get_post_meta($itemId, '_amol_menu_icon', true);
        $color = (string) get_post_meta($itemId, '_amol_menu_color', true);
        ?>
        <p class="field-amol-icon description description-wide">
            <label for="edit-menu-item-amol-icon-<?php echo (int) $itemId; ?>">آیکن (نام آیکن، مثل <code>chart-line</code>)<br>
                <input type="text" id="edit-menu-item-amol-icon-<?php echo (int) $itemId; ?>" class="widefat" name="amol_menu_icon[<?php echo (int) $itemId; ?>]" value="<?php echo esc_attr($icon); ?>" list="amol-icon-list" dir="ltr">
            </label>
        </p>
        <p class="field-amol-color description description-wide">
            <label for="edit-menu-item-amol-color-<?php echo (int) $itemId; ?>">رنگ آیکن (مثل <code>#2f7cf6</code>)<br>
                <input type="text" id="edit-menu-item-amol-color-<?php echo (int) $itemId; ?>" class="widefat" name="amol_menu_color[<?php echo (int) $itemId; ?>]" value="<?php echo esc_attr($color); ?>" dir="ltr">
            </label>
        </p>
        <?php
        static $printed = false;
        if (! $printed) {
            $printed = true;
            echo '<datalist id="amol-icon-list">';
            foreach (array_keys(Icons::all()) as $name) {
                echo '<option value="' . esc_attr($name) . '">';
            }
            echo '</datalist>';
        }
    }

    public static function save(int $menuId, int $itemId): void
    {
        if (! current_user_can('edit_theme_options')) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- core verifies the nav-menus nonce before firing wp_update_nav_menu_item.
        foreach (['icon' => '_amol_menu_icon', 'color' => '_amol_menu_color'] as $field => $meta) {
            $key = "amol_menu_{$field}";

            if (isset($_POST[$key][$itemId])) {
                $val = sanitize_text_field(wp_unslash($_POST[$key][$itemId]));
                $val === '' ? delete_post_meta($itemId, $meta) : update_post_meta($itemId, $meta, $val);
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    public static function help(): void
    {
        $screen = get_current_screen();

        if (! $screen || $screen->id !== 'nav-menus') {
            return;
        }

        echo '<div class="notice notice-info"><p><strong>راهنمای منوهای آمل نوین:</strong> برای زیرمنوی بزرگ «خدمات» به آیتم والد کلاس CSS <code>mega-services</code> و برای «انواع سایت» کلاس <code>mega-types</code> بدهید (گزینه‌ی «کلاس‌های CSS» را در «تنظیمات صفحه» فعال کنید). والدی که کلاس ندارد و زیرمنو دارد، به‌صورت منوی کوچک (مثل «بیشتر») نمایش داده می‌شود. کلاس <code>is-strong</code> لینک را پررنگ می‌کند. جای منوها: منوی اصلی، منوی موبایل و سه ستون فوتر.</p></div>';
    }
}
