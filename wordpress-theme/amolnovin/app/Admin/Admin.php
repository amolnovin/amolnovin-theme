<?php

namespace App\Admin;

use App\Console\ImportCommand;

/**
 * Admin integration: Customizer, structured data editor, SEO box, menu fields, importer, leads.
 */
class Admin
{
    public static function boot(): void
    {
        Customizer::boot();

        if (is_admin()) {
            Settings::boot();
            DataEditor::boot();
            SeoBox::boot();
            MenuFields::boot();
            ThemeData::boot();
            ImportPage::boot();
            Leads::boot();
        }

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('amol import', ImportCommand::class);
        }
    }
}
