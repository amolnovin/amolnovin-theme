<?php

namespace App\Integrations;

/**
 * Detection of the WordPress plugins the theme is aligned with.
 *
 * Every integration (snippets, market manager, math captcha, security suite) asks this class instead of calling
 * plugin classes directly, so the theme keeps working when a plugin is missing, deactivated or updated.
 */
class Plugins
{
    /** Custom HTML Snippets — https://wordpress.org/plugins/custom-html-snippets/ (v3.0.0 in this project). */
    public static function snippets(): bool
    {
        return defined('AmolNovin\\CHS\\VERSION')
            || post_type_exists('custom_snippet')
            || shortcode_exists('my_snippet');
    }

    /** WP Plugin Market Manager — سامانه فروش و لایسنس افزونه و قالب. */
    public static function marketManager(): bool
    {
        return defined('WPLM_VERSION')
            || class_exists('WPLM\\Plugin')
            || shortcode_exists('wplm_store');
    }

    /** WP Math Captcha — کپچای ریاضی برای فرم‌های سفارشی. */
    public static function captcha(): bool
    {
        return class_exists('WP_MC_Captcha');
    }

    /** Novin Security Suite — سخت‌سازی امنیتی وردپرس. */
    public static function security(): bool
    {
        return defined('NSH_VERSION')
            || function_exists('nsh')
            || class_exists('NSH_Settings');
    }

    /** Version of an integration (empty when the plugin is not installed). */
    public static function version(string $plugin): string
    {
        switch ($plugin) {
            case 'snippets':
                return defined('AmolNovin\\CHS\\VERSION') ? (string) constant('AmolNovin\\CHS\\VERSION') : '';
            case 'market_manager':
                return defined('WPLM_VERSION') ? (string) constant('WPLM_VERSION') : '';
            case 'captcha':
                return defined('WPMC_VERSION') ? (string) constant('WPMC_VERSION') : '';
            case 'security':
                return defined('NSH_VERSION') ? (string) constant('NSH_VERSION') : '';
        }

        return '';
    }

    /**
     * State of every integration for the settings panel.
     *
     * @return array<int,array{key:string,name:string,version:string,active:bool,plugin:string,note:string}>
     */
    public static function status(): array
    {
        $rows = [
            [
                'key' => 'snippets',
                'name' => 'قطعه کدهای سفارشی (Custom HTML Snippets)',
                'plugin' => 'custom-html-snippets',
                'note' => 'شورت‌کد [my_snippet id="…"] در نوشته‌ها، برگه‌ها و بخش‌های قالب.',
            ],
            [
                'key' => 'market_manager',
                'name' => 'سامانه فروش و لایسنس افزونه و قالب (WP Plugin Market Manager)',
                'plugin' => 'wp-plugin-market-manager',
                'note' => 'صفحه‌ی /wplm-store/ با شورت‌کد [wplm_store] نمایش داده می‌شود.',
            ],
            [
                'key' => 'captcha',
                'name' => 'کپچای ریاضی (WP Math Captcha)',
                'plugin' => 'wp-math-captcha',
                'note' => 'فرم‌های مشاوره‌ی قالب به‌صورت سمت‌سرور اعتبارسنجی می‌شوند.',
            ],
            [
                'key' => 'security',
                'name' => 'بسته‌ی امنیتی نوین (Novin Security Suite)',
                'plugin' => 'novin-security-suite',
                'note' => 'سازگاری REST API، هدرهای امنیتی و فرم دیدگاه‌ها.',
            ],
        ];

        foreach ($rows as $i => $row) {
            $active = match ($row['key']) {
                'snippets' => self::snippets(),
                'market_manager' => self::marketManager(),
                'captcha' => self::captcha(),
                'security' => self::security(),
                default => false,
            };

            $rows[$i]['active'] = $active;
            $rows[$i]['version'] = $active ? (self::version($row['key']) ?: '—') : '';
        }

        return (array) apply_filters('amol/integrations/status', $rows);
    }

    /** Installed? (active or simply present in the plugins folder) */
    public static function installed(string $plugin): bool
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all = get_plugins();

        foreach (array_keys($all) as $file) {
            if (str_starts_with((string) $file, $plugin . '/')) {
                return true;
            }
        }

        return false;
    }
}
