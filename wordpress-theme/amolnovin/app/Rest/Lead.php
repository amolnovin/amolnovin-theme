<?php

namespace App\Rest;

use App\Support\Site;

/**
 * Consultation-form endpoint (replaces api/lead.php of the static template).
 *
 *   POST /wp-json/amolnovin/v1/lead   { name, phone, service, message, page, website(honeypot) }
 *
 * The request is stored as a private "درخواست مشاوره" entry (admin menu) and e-mailed to the site owner.
 * If the endpoint is unreachable the form falls back to WhatsApp (handled by main.js).
 */
class Lead
{
    public static function boot(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function url(): string
    {
        return rest_url('amolnovin/v1/lead');
    }

    public static function routes(): void
    {
        register_rest_route('amolnovin/v1', '/lead', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    protected static function clean(mixed $v, int $max = 600): string
    {
        $v = trim(wp_strip_all_tags((string) $v));
        $v = (string) preg_replace('/[\r\n]+/', ' ', $v);

        return mb_substr($v, 0, $max);
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        $in = $request->get_json_params() ?: $request->get_body_params();
        $in = is_array($in) ? $in : [];

        // honeypot: pretend success
        if (! empty($in['website'])) {
            return new \WP_REST_Response(['ok' => true]);
        }

        $name = self::clean($in['name'] ?? '', 120);
        $phone = (string) preg_replace('/[^0-9+]/', '', (string) ($in['phone'] ?? ''));

        if (mb_strlen($name) < 2 || ! preg_match('/^(\+98|0)?9[0-9]{9}$/', $phone)) {
            return new \WP_REST_Response(['ok' => false, 'error' => 'invalid'], 422);
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0');
        $key = 'amol_lead_' . md5($ip);

        if (get_transient($key)) {
            return new \WP_REST_Response(['ok' => false, 'error' => 'rate'], 429);
        }
        set_transient($key, 1, 30);

        $service = self::clean($in['service'] ?? '');
        $message = self::clean($in['message'] ?? '', 1500);
        $page = self::clean($in['page'] ?? '');

        $body = "نام: {$name}\nموبایل: {$phone}\nخدمت: {$service}\nپیام: {$message}\nصفحه: {$page}\nIP: {$ip}\n";

        $id = wp_insert_post([
            'post_type' => 'amol_lead', 'post_status' => 'publish', 'post_title' => $name . ' — ' . $phone, 'post_content' => $body,
            'meta_input' => ['_amol_lead_phone' => $phone, '_amol_lead_service' => $service, '_amol_lead_page' => $page, '_amol_lead_ip' => $ip],
        ]);

        $to = get_theme_mod('amol_lead_email') ?: (Site::all()['email'] ?? get_option('admin_email'));
        $sent = wp_mail($to, 'درخواست مشاوره‌ی جدید از سایت', $body, ['Content-Type: text/plain; charset=UTF-8']);

        // the request is safely stored even when the server cannot send mail
        return new \WP_REST_Response(['ok' => (bool) ($id && ! is_wp_error($id)) || (bool) $sent]);
    }
}
