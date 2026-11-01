<?php
/**
 * Checks the selector rewriting of App\Integrations\SnippetCss (Custom HTML Snippets 3.0.x).
 *
 *   php wordpress-theme/qa-tools/snippet-css-scope-test.php
 *
 * No WordPress needed: only the static CSS helpers are exercised, the few WP functions the class
 * touches are stubbed below.
 *
 * @package AmolNovin\QA
 */

namespace App\Support {
    class Options
    {
        public static function on(string $group, string $key): bool
        {
            return true;
        }
    }
}

namespace App\Integrations {
    class Plugins
    {
        public static function snippets(): bool
        {
            return true;
        }
    }
}

namespace {
    function get_posts(array $args): array
    {
        return [11];
    }

    function get_post_meta(int $id, string $key, bool $single = false)
    {
        return $id === 11 ? 'h2{color:red}' : '';
    }

    require __DIR__ . '/../amolnovin/app/Integrations/SnippetCss.php';

    use App\Integrations\SnippetCss as C;

    $failures = 0;

    $assert = static function (string $name, bool $ok, string $detail = '') use (&$failures): void {
        if ($ok) {
            echo "  ok   $name\n";

            return;
        }

        $failures++;
        echo "  FAIL $name" . ($detail !== '' ? "\n       $detail" : '') . "\n";
    };

    echo "prefixRules()\n";

    $css = [
        ['h2{color:red}', '.custom-snippet-container h2{color:red}'],
        ["h2, h3 {color:red}\np{margin:0}", ".custom-snippet-container h2, .custom-snippet-container h3 {color:red}\n.custom-snippet-container p{margin:0}"],
        ['body{background:#fff}', '.custom-snippet-container{background:#fff}'],
        ['body .btn{x:1}', '.custom-snippet-container .btn{x:1}'],
        [':root{--a:#000}', '.custom-snippet-container{--a:#000}'],
        ['.prose p{x:1}', '.custom-snippet-container p{x:1}'],
        ['html.dark .x{y:2}', '.custom-snippet-container.dark .x{y:2}'],
        ['@media (max-width:600px){h2{font-size:14px}}', '@media (max-width:600px){.custom-snippet-container h2{font-size:14px}}'],
        ['@keyframes spin{from{transform:none}to{transform:rotate(1turn)}}', '@keyframes spin{from{transform:none}to{transform:rotate(1turn)}}'],
        ["@import url('a.css');\nh1{x:1}", "@import url('a.css');\n.custom-snippet-container h1{x:1}"],
        ['.card:is(a,b) .t{x:1}', '.custom-snippet-container .card:is(a,b) .t{x:1}'],
        ['::selection{background:#000}', '.custom-snippet-container::selection{background:#000}'],
        ['*{box-sizing:border-box}', '.custom-snippet-container, .custom-snippet-container *{box-sizing:border-box}'],
        ['.custom-snippet-container h2{x:1}', '.custom-snippet-container h2{x:1}'],
        ['/* note */ h2{x:1}', '/* note */ .custom-snippet-container h2{x:1}'],
        ['div[data-x="a,b"]{x:1}', '.custom-snippet-container div[data-x="a,b"]{x:1}'],
        ['> p{x:1}', '.custom-snippet-container > p{x:1}'],
        ['a:hover{color:red}', '.custom-snippet-container a:hover{color:red}'],
        ['.a{background:url("data:image/svg+xml;base64,AA==")}', '.custom-snippet-container .a{background:url("data:image/svg+xml;base64,AA==")}'],
        ['@font-face{font-family:x;src:url(a.woff)}', '@font-face{font-family:x;src:url(a.woff)}'],
        ["@media (min-width:600px){@keyframes k{from{opacity:0}to{opacity:1}}.a{opacity:1}}", "@media (min-width:600px){@keyframes k{from{opacity:0}to{opacity:1}}.custom-snippet-container .a{opacity:1}}"],
        [".a::after{content:'}'}", ".custom-snippet-container .a::after{content:'}'}"],
        ['', ''],
        ['/* hi */', '/* hi */'],
    ];

    foreach ($css as $i => [$input, $expected]) {
        $got = C::prefixRules($input);
        $assert('case ' . ($i + 1), $got === $expected, 'in: ' . $input . "\n       got: " . $got);
    }

    echo "scope()\n";

    $content = '<div class="custom-snippet-container" id="snippet-11"><style>h2{color:red}</style><h2>hi</h2></div>'
        . '<style>.core-widget{color:blue}</style>';

    $scoped = C::scope($content);

    $assert('snippet CSS scoped', str_contains($scoped, '<style>.custom-snippet-container h2{color:red}</style>'), $scoped);
    $assert('other styles untouched', str_contains($scoped, '<style>.core-widget{color:blue}</style>'), $scoped);
    $assert('markup kept', str_contains($scoped, '<h2>hi</h2>'), $scoped);
    $assert('force scopes unknown CSS', str_contains(C::scope('<style>.other{x:1}</style>', true), '.custom-snippet-container .other{x:1}'));

    $delayed = C::scope('<template class="chs-delay-run" data-target="inline" data-mode="interaction"><style>h2{color:red}</style></template>');
    $assert('delayed snippet (3.0.x template) scoped', str_contains($delayed, '<style>.custom-snippet-container h2{color:red}</style>'), $delayed);

    $isolated = C::scope('<iframe class="chs-isolated-frame" data-chs-snippet="11" srcdoc="&lt;style&gt;body{}&lt;/style&gt;"></iframe>');
    $assert('isolated iframe untouched', str_contains($isolated, 'srcdoc='), $isolated);

    echo $failures === 0 ? "\nall checks passed\n" : "\n{$failures} check(s) failed\n";
    exit($failures === 0 ? 0 : 1);
}
