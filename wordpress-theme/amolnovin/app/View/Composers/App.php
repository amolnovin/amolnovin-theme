<?php

namespace App\View\Composers;

use App\Content\PageData;
use App\Support\Site;
use Roots\Acorn\View\Composer;

class App extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        '*',
    ];

    /**
     * Data available to every view: `$site` (brand + contact data) and `$page` (SEO + page definition of this request).
     */
    public function with(): array
    {
        return [
            'site' => Site::all(),
            'page' => is_admin() ? [] : PageData::current(),
        ];
    }

    /**
     * Retrieve the site name.
     */
    public function siteName(): string
    {
        return get_bloginfo('name', 'display');
    }
}
