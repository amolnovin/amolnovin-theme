<?php

use App\Providers\ThemeServiceProvider;
use Roots\Acorn\Application;

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our theme. We will simply require it into the script here so that we
| don't have to worry about manually loading any of our classes later on.
|
*/

$amol_autoloader = __DIR__ . '/vendor/autoload.php';

if (! file_exists($amol_autoloader)) {
    wp_die(wp_kses_post(
        '<strong>قالب آمل نوین:</strong> کتابخانه‌های Composer پیدا نشد. نسخه‌ی آماده‌ی production (شامل پوشه‌ی <code>vendor</code>) را نصب کنید، یا در پوشه‌ی قالب دستور <code>composer install --no-dev</code> را اجرا کنید.<br>'
        . 'Error locating the Composer autoloader — run <code>composer install --no-dev</code> in the theme folder.'
    ));
}

require $amol_autoloader;

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
|
| The first thing we will do is schedule a new Acorn application container
| to boot when WordPress is finished loading the theme. The application
| serves as the "glue" for all the components of Laravel and is
| the IoC container for the system binding all of the various parts.
|
*/

Application::configure()
    ->withProviders([
        ThemeServiceProvider::class,
    ])
    ->boot();

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

collect(['setup', 'filters'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(wp_kses_post(
                /* translators: %s is replaced with the relative file path */
                sprintf(esc_html__('Error locating %s for inclusion.', 'amolnovin'), '<code>' . esc_html($file) . '</code>')
            ));
        }
    });
