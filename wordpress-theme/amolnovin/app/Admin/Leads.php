<?php

namespace App\Admin;

/**
 * Admin list of the consultation requests (phone, service, source page) — read-only.
 */
class Leads
{
    public static function boot(): void
    {
        add_filter('manage_amol_lead_posts_columns', function ($cols) {
            return ['cb' => $cols['cb'], 'title' => 'نام و شماره', 'amol_phone' => 'موبایل', 'amol_service' => 'نوع درخواست', 'amol_page' => 'از صفحه', 'date' => 'تاریخ'];
        });
        add_action('manage_amol_lead_posts_custom_column', function ($col, $id) {
            switch ($col) {
                case 'amol_phone':
                    $phone = (string) get_post_meta($id, '_amol_lead_phone', true);
                    echo '<a href="' . esc_url('tel:' . $phone) . '" dir="ltr">' . esc_html($phone) . '</a>';
                    break;

                case 'amol_service':
                    echo esc_html((string) get_post_meta($id, '_amol_lead_service', true));
                    break;

                case 'amol_page':
                    echo esc_html((string) get_post_meta($id, '_amol_lead_page', true));
                    break;
            }
        }, 10, 2);
    }
}
