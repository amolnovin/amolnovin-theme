<?php
/**
 * Checks App\Support\Html::wrapTables() — tables from the editor must end up inside `.table-wrap`,
 * otherwise a wide table makes the whole page scroll left/right (see 07-overflow.css).
 *
 *   php wordpress-theme/qa-tools/table-wrap-test.php
 *
 * No WordPress needed: only the static helper is exercised, the few WP functions it touches are stubbed.
 *
 * @package AmolNovin\QA
 */

namespace {
    function esc_url(string $url): string
    {
        return $url;
    }

    function home_url(string $path = ''): string
    {
        return 'https://example.com' . $path;
    }

    function untrailingslashit(string $path): string
    {
        return rtrim($path, '/');
    }

    require __DIR__ . '/../amolnovin/app/Support/Html.php';

    use App\Support\Html;

    $cases = [
        ['<p>a</p>', '<p>a</p>', 'no table'],
        ['<table><tr><td>1</td></tr></table>', '<div class="table-wrap"><table><tr><td>1</td></tr></table></div>', 'bare table'],
        ['<div class="table-wrap"><table class="table"><tr><td>x</td></tr></table></div>', '<div class="table-wrap"><table class="table"><tr><td>x</td></tr></table></div>', 'already wrapped'],
        ['<div class="table-wrap reveal"><table><tr><td>x</td></tr></table></div>', '<div class="table-wrap reveal"><table><tr><td>x</td></tr></table></div>', 'wrapped with an extra class'],
        [
            '<p>a</p><table class="wide"><tr><td>1</td></tr></table><p>b</p><table><tr><td>2</td></tr></table>',
            '<p>a</p><div class="table-wrap"><table class="wide"><tr><td>1</td></tr></table></div><p>b</p><div class="table-wrap"><table><tr><td>2</td></tr></table></div>',
            'two tables',
        ],
        ['<p>x</p><table><caption>t</caption><tr><td>1</td></tr></table><p>y</p>', '<p>x</p><div class="table-wrap"><table><caption>t</caption><tr><td>1</td></tr></table></div><p>y</p>', 'table with a caption'],
        ['<table><tr><td>1</td>', '<table><tr><td>1</td>', 'unbalanced markup is handed back untouched'],
        ['', '', 'empty string'],
    ];

    $failures = 0;

    foreach ($cases as [$input, $expected, $name]) {
        $got = Html::wrapTables($input);

        if ($got === $expected) {
            echo "  ok   $name\n";

            continue;
        }

        $failures++;
        echo "  FAIL $name\n       want: $expected\n       got:  $got\n";
    }

    echo $failures === 0 ? "\nall " . count($cases) . " cases passed\n" : "\n{$failures} case(s) failed\n";
    exit($failures === 0 ? 0 : 1);
}
