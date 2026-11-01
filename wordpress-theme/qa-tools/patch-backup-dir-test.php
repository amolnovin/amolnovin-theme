<?php
/**
 * Checks the backup-folder logic of App\Updater\Patcher (the «ساخت پوشه‌ی پشتیبان ناموفق بود» bug).
 *
 *   php wordpress-theme/qa-tools/patch-backup-dir-test.php
 *
 * No WordPress needed: the handful of WP functions the updater touches are stubbed below. The point of the
 * test is the recursive creation of `uploads/amolnovin-backups/<version>-<date>/` — the old code called
 * WP_Filesystem::mkdir() without the recursive flag, so it failed whenever `amolnovin-backups` was missing.
 *
 * @package AmolNovin\QA
 */

namespace {
    define('ABSPATH', sys_get_temp_dir() . '/amolnovin-test/');
    define('FS_CHMOD_DIR', 0755);
    define('FS_CHMOD_FILE', 0644);

    class WP_Error
    {
        public function __construct(public string $code = '', public string $message = '')
        {
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }
    }

    function is_wp_error($thing): bool
    {
        return $thing instanceof WP_Error;
    }

    function trailingslashit(string $path): string
    {
        return rtrim($path, '/\\') . '/';
    }

    function untrailingslashit(string $path): string
    {
        return rtrim($path, '/\\');
    }

    function get_temp_dir(): string
    {
        return $GLOBALS['amol_test']['temp'];
    }

    function wp_upload_dir(): array
    {
        return ['basedir' => $GLOBALS['amol_test']['uploads'], 'error' => false];
    }

    function wp_mkdir_p(string $dir): bool
    {
        return is_dir($dir) || @mkdir($dir, 0755, true) || is_dir($dir);
    }

    /** Minimal stand-in for the WordPress filesystem API (the updater only uses these calls). */
    class Amol_Test_FS
    {
        public function mkdir(string $path, int $chmod = 0755, bool $recursive = false): bool
        {
            return is_dir($path) || @mkdir($path, $chmod, $recursive);
        }

        public function is_dir(string $path): bool
        {
            return is_dir($path);
        }

        public function exists(string $path): bool
        {
            return file_exists($path);
        }

        public function put_contents(string $path, string $contents, int $chmod = 0644): bool
        {
            return @file_put_contents($path, $contents) !== false;
        }

        public function copy(string $from, string $to, bool $overwrite = false): bool
        {
            return @copy($from, $to);
        }

        public function delete(string $path, bool $recursive = false): bool
        {
            return @unlink($path);
        }
    }
}

namespace {

    // the updater asks for a WP_Filesystem_Base instance — the stub must match that name
    if (! class_exists('WP_Filesystem_Base')) {
        class_alias('Amol_Test_FS', 'WP_Filesystem_Base');
    }

    function WP_Filesystem(): bool
    {
        return true;
    }

    require __DIR__ . '/../amolnovin/app/Updater/Patcher.php';

    use App\Updater\Patcher;

    $failures = 0;

    $assert = static function (string $name, bool $ok, string $detail = '') use (&$failures): void {
        if ($ok) {
            echo "  ok   $name\n";

            return;
        }

        $failures++;
        echo "  FAIL $name" . ($detail !== '' ? "\n       $detail" : '') . "\n";
    };

    $call = static function (string $method, array $args = []) {
        $ref = new ReflectionMethod(Patcher::class, $method);

        return $ref->invoke(null, ...$args);
    };

    $rm = static function (string $dir): void {
        if (! is_dir($dir)) {
            return;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    };

    $root = sys_get_temp_dir() . '/amolnovin-backup-test-' . getmypid();
    $rm($root);
    mkdir($root . '/uploads', 0755, true);
    mkdir($root . '/content', 0755, true);
    mkdir($root . '/tmp', 0755, true);

    $GLOBALS['amol_test'] = ['uploads' => $root . '/uploads', 'temp' => $root . '/tmp'];
    $GLOBALS['wp_filesystem'] = new Amol_Test_FS();

    if (! defined('WP_CONTENT_DIR')) {
        define('WP_CONTENT_DIR', $root . '/content');
    }

    echo "backupBases()\n";

    $bases = Patcher::backupBases();
    $assert('three candidates', count($bases) === 3, implode(' , ', $bases));
    $assert('uploads first', str_starts_with($bases[0], $root . '/uploads/'), $bases[0] ?? '');

    echo "prepareBackups() — creates the nested folder (the old bug)\n";

    $assert('amolnovin-backups does not exist yet', ! is_dir($root . '/uploads/amolnovin-backups'));

    $prepared = Patcher::prepareBackups();
    $assert('prepareBackups() succeeded', ! is_wp_error($prepared), is_wp_error($prepared) ? $prepared->get_error_message() : '');
    $assert('folder created under uploads', is_dir($root . '/uploads/amolnovin-backups'));
    $assert('reported as created', ! is_wp_error($prepared) && $prepared['created'] === true);

    $preparedAgain = Patcher::prepareBackups();
    $assert('second call reports existing folder', ! is_wp_error($preparedAgain) && $preparedAgain['created'] === false);

    echo "backupDir()\n";

    $dir = $call('backupDir', ['1.2.1']);
    $assert('backupDir() returns a path', is_string($dir), is_object($dir) ? $dir->get_error_message() : (string) $dir);
    $assert('versioned folder exists', is_string($dir) && is_dir($dir), (string) $dir);
    $assert('folder name carries the version', is_string($dir) && str_contains(basename((string) $dir), '1.2.1-'));

    $nested = $dir . '/app/Integrations';
    $assert('nested backup folder can be created', $call('makeDir', [$nested]) && is_dir($nested));

    echo "fallbacks\n";

    // read-only uploads: the next candidate (wp-content) must be used
    chmod($root . '/uploads/amolnovin-backups', 0555);
    $paths = Patcher::backupPaths();
    $assert('first candidate reported as not writable', $paths[0]['writable'] === false, json_encode($paths, JSON_UNESCAPED_SLASHES));
    $fallback = Patcher::prepareBackups();
    $assert('falls back to wp-content', ! is_wp_error($fallback) && str_starts_with($fallback['dir'], $root . '/content/'), is_wp_error($fallback) ? $fallback->get_error_message() : $fallback['dir']);
    chmod($root . '/uploads/amolnovin-backups', 0755);

    // everything unwritable (uploads, wp-content and temp): a WP_Error that names the paths, never a silent failure
    chmod($root . '/content/amolnovin-backups', 0555); // the folder the fallback just created
    chmod($root . '/content', 0555);
    $GLOBALS['amol_test'] = ['uploads' => $root . '/locked/uploads', 'temp' => $root . '/locked/tmp'];
    mkdir($root . '/locked', 0555, true);

    $broken = Patcher::backupPaths();
    $assert('all candidates unwritable', ! $broken[0]['writable'] && ! $broken[1]['writable'], json_encode($broken, JSON_UNESCAPED_SLASHES));

    $error = $call('backupDir', ['9.9.9']);
    $assert('backupDir() returns WP_Error', is_wp_error($error));
    $assert('error message names the folders', is_wp_error($error) && str_contains($error->get_error_message(), 'amolnovin-backups'), is_wp_error($error) ? $error->get_error_message() : '');

    chmod($root . '/locked', 0755);
    chmod($root . '/content', 0755);
    chmod($root . '/content/amolnovin-backups', 0755);
    $rm($root);

    echo $failures === 0 ? "\nall checks passed\n" : "\n{$failures} check(s) failed\n";
    exit($failures === 0 ? 0 : 1);
}
