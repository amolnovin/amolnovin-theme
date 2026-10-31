<?php

namespace App\Providers;

use App\Admin\Admin;
use App\Content\PostTypes;
use App\Integrations\Captcha;
use App\Integrations\MarketManager;
use App\Integrations\Security;
use App\Integrations\Snippets;
use App\Optimize\Seo;
use App\Optimize\Speed;
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

        // performance & SEO switches (تنظیمات قالب)
        Speed::boot();
        Seo::boot();

        Head::boot();
        Feeds::boot();
        Lead::boot();
        Redirects::boot();
        Headers::boot();

        // plugin integrations
        Snippets::boot();
        Captcha::boot();
        Security::boot();
        MarketManager::boot();

        Admin::boot();
    }
}
