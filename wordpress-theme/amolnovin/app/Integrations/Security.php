<?php

namespace App\Integrations;

use App\Support\Options;

/**
 * Novin Security Suite compatibility layer.
 *
 * Two things can break a theme when a security plugin hardens a site:
 *   1. «الزام ورود برای REST» — the consultation form posts to a public REST route and would stop working.
 *   2. duplicated security headers — the theme and the plugin would both send them.
 *
 * Both are handled here (and can be switched off in تنظیمات قالب → افزونه‌ها).
 */
class Security
{
    public static function active(): bool
    {
        return Plugins::security();
    }

    public static function boot(): void
    {
        if (! self::active()) {
            return;
        }

        add_filter('rest_authentication_errors', [self::class, 'exemptPublicRoutes'], 95);
    }

    /** Public REST routes of the theme that must stay reachable for visitors. */
    public static function publicRoutes(): array
    {
        $routes = ['/amolnovin/v1/lead'];

        return (array) apply_filters('amol/security/public_rest_routes', $routes);
    }

    /**
     * Keep the public form endpoint open when the security plugin requires authentication for the REST API.
     *
     * @param \WP_Error|true|null $result
     * @return \WP_Error|true|null
     */
    public static function exemptPublicRoutes($result)
    {
        if (empty($result) || ! is_wp_error($result) || is_user_logged_in()) {
            return $result;
        }

        if (! Options::on('integrations', 'security') || ! Options::on('integrations', 'security_rest_exempt')) {
            return $result;
        }

        $route = self::currentRoute();

        foreach (self::publicRoutes() as $public) {
            if ($route === untrailingslashit((string) $public)) {
                return null; // no authentication error: the route has its own validation (nonce-less by design)
            }
        }

        return $result;
    }

    /** The REST route of the current request, normalised to `/namespace/route`. */
    protected static function currentRoute(): string
    {
        $route = '';

        if (isset($GLOBALS['wp']) && isset($GLOBALS['wp']->query_vars['rest_route'])) {
            $route = (string) $GLOBALS['wp']->query_vars['rest_route'];
        }

        if ($route === '' && isset($_SERVER['REQUEST_URI'])) {
            $uri = (string) wp_unslash($_SERVER['REQUEST_URI']);
            $pos = strpos($uri, '/wp-json/');

            if ($pos !== false) {
                $route = substr($uri, $pos + strlen('/wp-json/'));
                $route = (string) strtok($route, '?');
            }
        }

        $route = '/' . trim($route, '/');

        return untrailingslashit($route);
    }

    /** Does the security plugin send its own security headers? */
    public static function pluginSendsHeaders(): bool
    {
        if (! self::active() || ! class_exists('NSH_Settings')) {
            return false;
        }

        try {
            return (bool) \NSH_Settings::enabled('security_headers');
        } catch (\Throwable) {
            return false;
        }
    }

    /** Should the theme send its own security headers? (auto | on | off) */
    public static function themeSendsHeaders(): bool
    {
        $mode = (string) Options::get('integrations', 'security_headers', 'auto');

        if ($mode === 'off') {
            return false;
        }

        if ($mode === 'on') {
            return true;
        }

        return ! self::pluginSendsHeaders();
    }

    /** Compact state for the settings panel. */
    public static function state(): array
    {
        return [
            'active' => self::active(),
            'rest_require_auth' => class_exists('NSH_Settings') ? (bool) \NSH_Settings::enabled('rest_require_auth') : false,
            'stealth_login' => class_exists('NSH_Stealth') ? (string) \NSH_Stealth::login_slug() !== '' : false,
            'headers_by_plugin' => self::pluginSendsHeaders(),
        ];
    }
}
