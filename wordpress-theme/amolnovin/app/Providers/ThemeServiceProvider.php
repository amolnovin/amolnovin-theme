<?php

namespace App\Providers;

use App\Admin\Admin;
use App\Content\PostTypes;
use App\Rest\Lead;
use App\Seo\Feeds;
use App\Seo\Head;
use App\Content\Redirects;
use App\Support\Headers;
use App\Support\Html;
use Illuminate\Support\Facades\Blade;
use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        parent::boot();

        // Blade directives:  @icon('check', 'i--sm')   @rl($html)
        Blade::directive('icon', fn ($expr) => "<?php echo amol_icon({$expr}); ?>");
        Blade::directive('rl', fn ($expr) => "<?php echo amol_rl({$expr}); ?>");

        // theme modules
        PostTypes::boot();
        Html::boot();
        Head::boot();
        Feeds::boot();
        Lead::boot();
        Redirects::boot();
        Headers::boot();
        Admin::boot();
    }
}
