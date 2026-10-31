<?php

namespace App\Support;

use App\Integrations\Security;

/**
 * Security headers (what the static template set in .htaccess) — sent by WordPress itself so they work on any server.
 */
class Headers
{
    public static function boot(): void
    {
        add_action('send_headers', function () {
            if (is_admin() || ! Security::themeSendsHeaders()) {
                return; // a security plugin (Novin Security Suite) already sends them
            }

            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        });
    }
}
