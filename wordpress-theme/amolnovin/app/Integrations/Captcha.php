<?php

namespace App\Integrations;

use App\Support\Options;

/**
 * WP Math Captcha integration.
 *
 * The theme's consultation form is a REST form (fetch + JSON), so the captcha is rendered inside the form and
 * verified server-side in {@see \App\Rest\Lead} through the plugin's public API (`WP_MC_Captcha::output()` /
 * `WP_MC_Captcha::verify()`). Comment forms are handled by the plugin itself unless the site owner asks the theme
 * to do it (تنظیمات → فرم‌ها و کپچا).
 */
class Captcha
{
    public static function active(): bool
    {
        return Plugins::captcha();
    }

    /** Should the theme protect its own forms (consultation / contact)? */
    public static function shouldProtect(): bool
    {
        $mode = (string) Options::get('forms', 'captcha', 'auto');

        if ($mode === 'off') {
            return false;
        }

        if (! self::active()) {
            return false;
        }

        return $mode === 'on' || $mode === 'auto';
    }

    public static function boot(): void
    {
        if (! self::active() || ! self::shouldProtectComments()) {
            return;
        }

        add_filter('comment_form_submit_field', [self::class, 'commentField'], 20, 2);
        add_filter('preprocess_comment', [self::class, 'checkComment'], 5);
    }

    /**
     * Should the theme add the captcha to the core comment form?
     *
     * "auto" adds it only when the plugin's own comment protection is switched off, so visitors never see two
     * captchas on the same form.
     */
    public static function shouldProtectComments(): bool
    {
        if (! self::active()) {
            return false;
        }

        $mode = (string) Options::get('forms', 'captcha_comments', 'auto');

        if ($mode === 'off') {
            return false;
        }

        if ($mode === 'on') {
            return true;
        }

        return ! self::pluginProtectsComments();
    }

    protected static function pluginProtectsComments(): bool
    {
        if (! class_exists('WP_MC_Core')) {
            return false;
        }

        try {
            return (bool) \WP_MC_Core::get('apply_comments', 1);
        } catch (\Throwable) {
            return true;
        }
    }

    /** The captcha markup of the plugin (fields: wpmc_answer, wpmc_token, wpmc_website). */
    public static function field(): string
    {
        if (! self::shouldProtect()) {
            return '';
        }

        try {
            return (string) \WP_MC_Captcha::output();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Server-side check of the captcha fields of $source ($_POST or a REST payload).
     *
     * @return true|\WP_Error
     */
    public static function verify(array $source, string $context = 'amol-form'): true|\WP_Error
    {
        if (! self::shouldProtect()) {
            return true;
        }

        $answer = isset($source['wpmc_answer']) ? wp_unslash((string) $source['wpmc_answer']) : '';
        $token = isset($source['wpmc_token']) ? wp_unslash((string) $source['wpmc_token']) : '';
        $honeypot = isset($source['wpmc_website']) ? wp_unslash((string) $source['wpmc_website']) : '';

        try {
            return \WP_MC_Captcha::verify($answer, $token, $honeypot, $context);
        } catch (\Throwable $e) {
            // fail closed: a broken captcha must never let spam through
            return new \WP_Error('captcha_internal', Options::get('forms', 'captcha_message'));
        }
    }

    /** Comment form: inject the captcha right above the submit button. */
    public static function commentField(string $submitField, array $args = []): string
    {
        $field = self::field();

        return $field === '' ? $submitField : '<div class="comment-form-wpmc">' . $field . '</div>' . $submitField;
    }

    /** Comment form: verify before WordPress stores the comment. */
    public static function checkComment(array $commentdata): array
    {
        if (empty($_POST)) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the core comment form carries its own checks.
            return $commentdata;
        }

        $result = self::verify($_POST, 'amol-comment'); // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if (is_wp_error($result)) {
            wp_die(
                esc_html($result->get_error_message()),
                esc_html__('دیدگاه ثبت نشد', 'amolnovin'),
                ['response' => 403, 'back_link' => true]
            );
        }

        return $commentdata;
    }
}
