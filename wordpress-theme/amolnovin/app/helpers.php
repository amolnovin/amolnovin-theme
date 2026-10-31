<?php

/**
 * Global helpers (prefixed `amol_`, autoloaded by Composer — see composer.json "files").
 * They keep the Blade templates as close as possible to the Jinja templates of the static HTML template.
 */

use App\Content\PageData;
use App\Content\Repo;
use App\Support\Data;
use App\Support\Html;
use App\Support\Icons;
use App\Support\Persian;
use App\Support\Site;

if (! function_exists('amol_site')) {
    /** The `SITE` dictionary (brand + contact data). amol_site('phone_display') */
    function amol_site(?string $key = null, mixed $default = null): mixed
    {
        $s = Site::all();

        return $key === null ? $s : ($s[$key] ?? $default);
    }
}

if (! function_exists('amol_page')) {
    function amol_page(): array
    {
        return PageData::current();
    }
}

if (! function_exists('amol_common')) {
    /** Shared lists: amol_common('tech'), amol_common('plans') … */
    function amol_common(?string $key = null, mixed $default = null): mixed
    {
        $c = Data::common();

        return $key === null ? $c : ($c[$key] ?? $default);
    }
}

if (! function_exists('amol_url')) {
    function amol_url(string $path): string
    {
        return Html::url($path);
    }
}

if (! function_exists('amol_asset')) {
    function amol_asset(string $path): string
    {
        return Html::asset($path);
    }
}

if (! function_exists('amol_icon')) {
    function amol_icon(string $name, string $class = ''): string
    {
        return Icons::svg($name, $class);
    }
}

if (! function_exists('amol_rl')) {
    /** Resolve the @/path and @asset: tokens of stored content. */
    function amol_rl(?string $html): string
    {
        return Html::resolve($html);
    }
}

if (! function_exists('amol_fa')) {
    function amol_fa(mixed $value): string
    {
        return Persian::fa((string) $value);
    }
}

if (! function_exists('amol_jdate')) {
    function amol_jdate(string $iso): string
    {
        return Persian::jdate($iso);
    }
}

if (! function_exists('amol_types')) {
    function amol_types(): array
    {
        return Repo::types();
    }
}

if (! function_exists('amol_content')) {
    /** Render stored post content (blocks / shortcodes) and resolve the theme's link tokens. */
    function amol_content(string $html): string
    {
        return Html::resolve(apply_filters('the_content', $html)); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
    }
}

if (! function_exists('amol_img')) {
    /** Image URL of an entity: featured image if set, else theme image img/{dir}/{name}{suffix}.webp */
    function amol_img(array $entity, string $dir, string $suffix = '', string $key = 'img'): string
    {
        return Repo::image($entity, $dir, $suffix, $key);
    }
}

if (! function_exists('amol_or')) {
    /** Jinja's `x or default` for array members: empty values (null, '', 0, [], false) fall back to the default. */
    function amol_or(array $a, string $key, mixed $default = null): mixed
    {
        return ! empty($a[$key]) ? $a[$key] : $default;
    }
}

if (! function_exists('amol_cities')) {
    /** Cities listed in the footer (Customizer textarea, one per line or comma separated). */
    function amol_cities(): array
    {
        $mod = trim((string) get_theme_mod('amol_cities', ''));

        if ($mod !== '') {
            return array_values(array_filter(array_map('trim', preg_split('/[\r\n,،]+/u', $mod))));
        }

        return Data::seed('site')['cities'] ?? [];
    }
}

if (! function_exists('amol_entity_url')) {
    /** Permalink of a type / project / post entity (falls back to the default URL structure for seed data). */
    function amol_entity_url(array $e, string $kind): string
    {
        if (! empty($e['_url'])) {
            return $e['_url'];
        }

        return amol_url(match ($kind) {
            'type' => '/services/web-design/' . $e['slug'] . '/',
            'project' => '/projects/' . $e['slug'] . '/',
            default => '/' . $e['slug'] . '/',
        });
    }
}

if (! function_exists('amol_project_services')) {
    /** key => label of the services badges (design / support / seo). */
    function amol_project_services(): array
    {
        return Repo::projectServices();
    }
}

if (! function_exists('amol_project_cats')) {
    /** @return array<string,string> category key => label */
    function amol_project_cats(): array
    {
        return array_column(Repo::projectCats(), 1, 0);
    }
}

if (! function_exists('amol_store_cats')) {
    function amol_store_cats(): array
    {
        return array_column(Repo::storeCats(), 1, 0);
    }
}

if (! function_exists('amol_section')) {
    /** Render one data-driven page section. */
    function amol_section(array $sec, int $idx = 0): string
    {
        $view = 'page-sections.' . ($sec['kind'] ?? '');

        return view()->exists($view) ? view($view, ['sec' => $sec, 'idx' => $idx])->render() : '';
    }
}

if (! function_exists('amol_team')) {
    /** Team members of the About page (shared list, editable in the theme data editor). */
    function amol_team(): array
    {
        return amol_common('team') ?: (Data::seed('site')['team'] ?? []);
    }
}

if (! function_exists('amol_sitemap_groups')) {
    /** Groups of the HTML sitemap page; the type / project / post / store lists are always built from live content. */
    function amol_sitemap_groups(array $page): array
    {
        $out = [];

        foreach (($page['groups'] ?? []) as [$title, $links]) {
            switch ($title) {
                case 'انواع سایت':
                    $links = array_map(fn ($t) => [$t['title'], amol_entity_url($t, 'type')], Repo::types());
                    break;

                case 'نمونه‌کارها':
                    $links = array_merge([['همه‌ی نمونه‌کارها', '/portfolio/']], array_map(fn ($p) => [$p['title'], amol_entity_url($p, 'project')], Repo::projectsWithDetail()));
                    break;

                case 'وبلاگ و فروشگاه':
                    $links = array_merge([['وبلاگ', '/blog-n/']], array_map(fn ($p) => [$p['title'], amol_entity_url($p, 'post')], Repo::posts()), [['فروشگاه افزونه‌ها', '/wplm-store/']]);
                    break;
            }

            $out[] = [$title, $links];
        }

        return $out;
    }
}
