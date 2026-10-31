<?php

namespace App\Support;

/**
 * Security headers (what the static template set in .htaccess) — sent by WordPress itself so they work on any server.
 */
class Headers
{
    public static function boot(): void
    {
        add_action('send_headers', function () {
            if (is_admin()) {
                return;
            }

            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        });
    }
}
