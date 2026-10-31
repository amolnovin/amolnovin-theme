<!doctype html>
<html @php(language_attributes()) class="no-js" data-root="{{ esc_url(trailingslashit(home_url())) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
@php(do_action('get_header'))
@php(wp_head())
<link rel="preload" href="{{ amol_asset('fonts/Vazirmatn-subset.woff2') }}" as="font" type="font/woff2" crossorigin>
<script>(function(d){var t;try{t=localStorage.getItem('avn-theme')}catch(e){}if(!t&&window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches)t='dark';d.documentElement.setAttribute('data-theme',t||'light');d.documentElement.className=d.documentElement.className.replace('no-js','js')})(document)</script>
<script src="{{ \App\Seo\Feeds::searchIndexUrl() }}" defer></script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('head')
</head>
<body @php(body_class('page-' . ($page['kind'] ?? 'generic')))>
@php(wp_body_open())
<!--SPRITE-->
@include('sections.header')
<main id="content" tabindex="-1">
@yield('content')
</main>
@include('sections.footer')
@include('partials.overlays')
<noscript><style>.js .reveal{opacity:1!important;transform:none!important}</style></noscript>
@php(do_action('get_footer'))
@php(wp_footer())
</body>
</html>
