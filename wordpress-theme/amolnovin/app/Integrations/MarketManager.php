<?php

namespace App\Integrations;

use App\Support\Options;

/**
 * WP Plugin Market Manager integration — the /wplm-store/ page.
 *
 * When the plugin is active the store template renders its own `[wplm_store]` shortcode (product grid, product
 * pages, cart, checkout, gateway callbacks and the customer account), so prices, licenses and orders always come
 * from the plugin's tables. Without the plugin the theme falls back to its own `wplm_product` cards.
 */
class MarketManager
{
    /** Last error of the plugin's shortcode (empty when it rendered fine). */
    protected static string $error = '';

    public static function active(): bool
    {
        return self::usesPlugin();
    }

    /** Should the /wplm-store/ page be served by the plugin? (تنظیمات → فروشگاه: auto | plugin | theme) */
    public static function usesPlugin(): bool
    {
        $mode = (string) Options::get('integrations', 'store_source', 'auto');

        if ($mode === 'theme') {
            return false;
        }

        return Plugins::marketManager();
    }

    public static function boot(): void
    {
        // The plugin resolves its own pages via WPLM\Options; expose them to the theme helpers.
        add_action('wp', function () {
            if (self::usesPlugin()) {
                remove_action('wp_head', 'wp_generator');
            }
        });
    }

    /**
     * Render the plugin store.
     *
     * A broken or half-installed plugin must never take the page down (its shortcode can throw an Error or a
     * TypeError), so the output is guarded: on failure this returns '' and the caller falls back to the theme's
     * own product cards, while {@see self::error()} keeps the message for the settings panel and admins.
     */
    public static function render(string $shortcode = 'wplm_store'): string
    {
        self::$error = '';

        if (! self::usesPlugin() || ! shortcode_exists($shortcode)) {
            return '';
        }

        try {
            $html = do_shortcode('[' . $shortcode . ']');
        } catch (\Throwable $e) {
            self::fail($e);

            return '';
        }

        if (! is_string($html) || trim($html) === '') {
            if (! is_string($html)) {
                self::fail(new \RuntimeException('shortcode returned ' . gettype($html)));
            }

            return '';
        }

        return $html;
    }

    /** Message of the last failed shortcode render ('' when nothing failed). */
    public static function error(): string
    {
        return self::$error;
    }

    protected static function fail(\Throwable $e): void
    {
        self::$error = $e->getMessage();

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AmolNovin market-manager shortcode failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    /** URL of the store page: the plugin's configured page, else the theme's /wplm-store/ page. */
    public static function storeUrl(): string
    {
        $plugin = self::pluginPageUrl('store_page_id');

        if ($plugin !== '') {
            return $plugin;
        }

        $id = (int) Options::get('integrations', 'store_page_id', 0);

        if ($id && get_post_status($id) === 'publish') {
            return (string) get_permalink($id);
        }

        $page = get_page_by_path('wplm-store');

        return $page ? (string) get_permalink($page) : home_url('/wplm-store/');
    }

    public static function accountUrl(): string
    {
        $plugin = self::pluginPageUrl('account_page_id');

        return $plugin !== '' ? $plugin : self::storeUrl();
    }

    public static function paymentResultUrl(): string
    {
        $plugin = self::pluginPageUrl('payment_result_page_id');

        return $plugin !== '' ? $plugin : self::storeUrl();
    }

    /** Number of active products (shown in the settings panel). */
    public static function productCount(): int
    {
        if (! self::usesPlugin() || ! class_exists('WPLM\\Repositories\\ProductRepository')) {
            return 0;
        }

        try {
            return count((new \WPLM\Repositories\ProductRepository())->active());
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Point the market manager at the given page (so its account/payment links and canonical URLs use this theme's
     * store page instead of the page the plugin created on activation).
     */
    public static function syncStorePage(int $pageId): bool
    {
        if (! $pageId || get_post_type($pageId) !== 'page' || ! self::usesPlugin()) {
            return false;
        }

        $settings = get_option('wplm_settings', []);
        $settings = is_array($settings) ? $settings : [];
        $settings['store_page_id'] = $pageId;

        // the account / payment pages keep their own value; fall back to the store page only when unset
        foreach (['account_page_id', 'payment_result_page_id'] as $key) {
            if (empty($settings[$key])) {
                $settings[$key] = $pageId;
            }
        }

        update_option('wplm_settings', $settings, false);

        return true;
    }

    protected static function pluginPageUrl(string $key): string
    {
        if (! class_exists('WPLM\\Options')) {
            return '';
        }

        try {
            // Options::page_url() falls back to the home page, which would hide the unset state
            $settings = \WPLM\Options::get();
            $id = isset($settings[$key]) ? absint($settings[$key]) : 0;
            $permalink = $id ? get_permalink($id) : '';

            return is_string($permalink) ? $permalink : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
