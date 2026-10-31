<?php

namespace App\Console;

use App\Admin\Importer;

/**
 * WP-CLI: wp amol import [--reset]
 */
class ImportCommand
{
    /**
     * Import the demo content (pages, website types, projects, plugins, posts, menus).
     *
     * ## OPTIONS
     *
     * [--reset]
     * : Delete the sample page / post of a fresh WordPress install first.
     *
     * @when after_wp_load
     */
    public function __invoke(array $args, array $assoc): void
    {
        $stats = Importer::run(['reset' => isset($assoc['reset'])]);

        \WP_CLI::success('Imported: ' . implode(', ', array_map(fn ($k, $v) => "{$v} {$k}", array_keys($stats), $stats)));
    }
}
