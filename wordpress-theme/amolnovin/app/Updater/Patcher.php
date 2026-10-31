<?php

namespace App\Updater;

use App\Support\Options;

/**
 * Theme updates through patches (بروزرسانی قالب با پچ).
 *
 * ── The standard this class implements ───────────────────────────────────────────────────────────────────────
 *   updates/manifest.json                     catalogue of releases (see updates/README.md)
 *   updates/patches/amolnovin-x-to-y.zip      only the changed files, paths relative to the theme root
 *   updates/manifest.schema.json              JSON Schema of the catalogue
 *
 * A patch entry looks like:
 *   {
 *     "version": "1.1.0",
 *     "date": "2026-10-31",
 *     "requires": { "php": "8.3", "wp": "6.6", "from": ["1.0.0"] },
 *     "package": "patches/amolnovin-1.0.0-to-1.1.0.zip",
 *     "sha256": "<sha256 of the zip>",
 *     "notes": "…",
 *     "files": [ { "path": "style.css", "sha256": "…" } ],
 *     "deleted": [ "old/file.php" ]
 *   }
 *
 * Safety rules: https-only sources (host allow-list), SHA-256 of the package and of every file, path validation
 * (no `..`, no absolute paths), a full backup before writing, a recorded log and one-click rollback.
 */
class Patcher
{
    public const STATE = 'amol_update_state';
    public const CACHE = 'amol_update_manifest';
    public const BACKUP_DIR = 'amolnovin-backups';

    // ---------------------------------------------------------------- basics

    public static function themeDir(): string
    {
        return untrailingslashit(get_template_directory());
    }

    public static function currentVersion(): string
    {
        return (string) wp_get_theme()->get('Version');
    }

    public static function manifestUrl(): string
    {
        $url = (string) Options::get('updates', 'manifest_url', '');

        return (string) apply_filters('amol/updater/manifest_url', $url);
    }

    /** @return array{applied:array<string,array<string,mixed>>,history:array<int,array<string,mixed>>} */
    public static function state(): array
    {
        $state = get_option(self::STATE, []);
        $state = is_array($state) ? $state : [];

        return wp_parse_args($state, ['applied' => [], 'history' => []]);
    }

    /** @return array<string,array<string,mixed>> versions applied by this class */
    public static function applied(): array
    {
        $state = self::state();

        return is_array($state['applied']) ? $state['applied'] : [];
    }

    /** @return array<int,array<string,mixed>> */
    public static function history(): array
    {
        $state = self::state();
        $history = is_array($state['history'] ?? null) ? $state['history'] : [];

        return array_reverse(array_slice($history, -20));
    }

    // ---------------------------------------------------------------- manifest

    /**
     * Download (and cache) the release catalogue.
     *
     * @return array<string,mixed>|\WP_Error
     */
    public static function fetchManifest(bool $force = false)
    {
        if (! $force) {
            $cached = get_transient(self::CACHE);

            if (is_array($cached)) {
                return $cached;
            }
        }

        $url = self::manifestUrl();

        if ($url === '' || ! wp_http_validate_url($url)) {
            return new \WP_Error('amol_manifest_url', 'آدرس فهرست بروزرسانی معتبر نیست. آن را در تب «بروزرسانی» تنظیم کنید.');
        }

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'redirection' => 3,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'AmolNovin-Theme-Updater/' . self::currentVersion(),
            ],
        ]);

        if (is_wp_error($response)) {
            return new \WP_Error('amol_manifest_http', 'دریافت فهرست بروزرسانی ناموفق بود: ' . $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);

        if ($code !== 200) {
            return new \WP_Error('amol_manifest_status', sprintf('سرور بروزرسانی کد %d برگرداند.', $code));
        }

        $data = json_decode($body, true);

        if (! is_array($data) || ! isset($data['releases']) || ! is_array($data['releases'])) {
            return new \WP_Error('amol_manifest_shape', 'ساختار فهرست بروزرسانی معتبر نیست (کلید releases پیدا نشد).');
        }

        $data['_url'] = $url;
        $data['_fetched'] = time();

        set_transient(self::CACHE, $data, 6 * HOUR_IN_SECONDS);
        self::rememberCheck($data);

        return $data;
    }

    /**
     * Releases that can be applied to the running version, newest first.
     *
     * @return array<int,array<string,mixed>>|\WP_Error
     */
    public static function available(bool $force = false)
    {
        $manifest = self::fetchManifest($force);

        if (is_wp_error($manifest)) {
            return $manifest;
        }

        $current = self::currentVersion();
        $out = [];

        foreach ($manifest['releases'] as $release) {
            if (! is_array($release) || empty($release['version'])) {
                continue;
            }

            $version = self::cleanVersion((string) $release['version']);

            if (! self::isNewer($version, $current)) {
                continue;
            }

            if (! empty(self::state()['applied'][$version])) {
                continue; // already applied
            }

            $from = (array) ($release['requires']['from'] ?? []);

            if ($from && ! in_array($current, array_map([self::class, 'cleanVersion'], $from), true)) {
                continue; // incremental patch for another starting version
            }

            $release['_version'] = $version;
            $out[] = $release;
        }

        usort($out, static fn ($a, $b) => version_compare($b['_version'], $a['_version']));

        return $out;
    }

    /** @return array<string,mixed> */
    public static function status(): array
    {
        $state = self::state();
        $manifest = get_transient(self::CACHE);
        $available = is_array($manifest) ? self::available(false) : [];

        return [
            'version' => self::currentVersion(),
            'manifest_url' => self::manifestUrl(),
            'last_check' => (int) Options::get('updates', 'last_check', 0),
            'available' => is_wp_error($available) ? [] : $available,
            'error' => is_wp_error($available) ? $available->get_error_message() : '',
            'applied' => is_array($state['applied']) ? $state['applied'] : [],
            'history' => self::history(),
            'cached' => is_array($manifest),
        ];
    }

    // ---------------------------------------------------------------- apply

    /**
     * Apply a release of the catalogue.
     *
     * @return array{version:string,files:int,deleted:int,backup:string}|\WP_Error
     */
    public static function apply(string $version)
    {
        $version = self::cleanVersion($version);
        $available = self::available(false);

        if (is_wp_error($available)) {
            return $available;
        }

        $release = null;

        foreach ($available as $candidate) {
            if (($candidate['_version'] ?? '') === $version) {
                $release = $candidate;
                break;
            }
        }

        if (! $release) {
            return new \WP_Error('amol_patch_missing', 'این نسخه در فهرست بروزرسانی موجود نیست یا برای نسخه‌ی فعلی شما ساخته نشده است.');
        }

        $check = self::checkRequirements($release);

        if (is_wp_error($check)) {
            return $check;
        }

        if (empty($release['sha256']) || empty($release['files']) || ! is_array($release['files'])) {
            return new \WP_Error('amol_patch_unverifiable', 'این پچ فهرست فایل و امضای SHA-256 ندارد؛ برای امنیت اعمال نمی‌شود.');
        }

        $url = self::packageUrl((string) ($release['package'] ?? ''));

        if (is_wp_error($url)) {
            return $url;
        }

        $download = self::download($url);

        if (is_wp_error($download)) {
            return $download;
        }

        $file = $download;

        try {
            $hash = hash_file('sha256', $file);

            if (! is_string($hash) || ! hash_equals(strtolower((string) $release['sha256']), $hash)) {
                return new \WP_Error('amol_patch_hash', 'امضای SHA-256 بسته با فهرست بروزرسانی هم‌خوان نیست؛ بسته اعمال نشد.');
            }

            $result = self::installFromZip($file, $release);

            if (is_wp_error($result)) {
                return $result;
            }

            return [
                'version' => $version,
                'files' => $result['files'],
                'deleted' => $result['deleted'],
                'backup' => $result['backup'],
            ];
        } finally {
            if (is_string($file) && $file !== '' && file_exists($file)) {
                @unlink($file); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink -- temp file in the system temp directory.
            }
        }
    }

    /**
     * Apply a patch uploaded from the panel (offline / air-gapped sites).
     *
     * @return array{version:string,files:int,deleted:int,backup:string}|\WP_Error
     */
    public static function applyUpload(string $uploadedFile)
    {
        if ($uploadedFile === '' || ! is_file($uploadedFile)) {
            return new \WP_Error('amol_patch_upload', 'فایلی برای بارگذاری پیدا نشد.');
        }

        $release = self::readPatchManifest($uploadedFile);

        if (is_wp_error($release)) {
            return $release;
        }

        $release['_version'] = self::cleanVersion((string) $release['version']);

        if (! self::isNewer($release['_version'], self::currentVersion())) {
            return new \WP_Error('amol_patch_old', 'این پچ برای نسخه‌ای بزرگ‌تر از نسخه‌ی فعلی نیست.');
        }

        $check = self::checkRequirements($release);

        if (is_wp_error($check)) {
            return $check;
        }

        $result = self::installFromZip($uploadedFile, $release);

        if (is_wp_error($result)) {
            return $result;
        }

        return [
            'version' => $release['_version'],
            'files' => $result['files'],
            'deleted' => $result['deleted'],
            'backup' => $result['backup'],
        ];
    }

    /** @return array{version:string,files:int,deleted:int,backup:string}|\WP_Error */
    public static function rollback(string $version)
    {
        $version = self::cleanVersion($version);
        $state = self::state();
        $applied = $state['applied'][$version] ?? null;

        if (! is_array($applied) || empty($applied['backup']) || ! is_dir((string) $applied['backup'])) {
            return new \WP_Error('amol_rollback_missing', 'پشتیبان این نسخه پیدا نشد.');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $backup = untrailingslashit((string) $applied['backup']);
        $theme = self::themeDir();

        // remove files the patch added, restore the ones it replaced
        $restored = self::restoreBackup($theme, $backup, (array) ($applied['added'] ?? []));

        unset($state['applied'][$version]);
        $state['history'][] = [
            'type' => 'rollback',
            'version' => $version,
            'date' => time(),
            'user' => get_current_user_id(),
            'files' => $restored,
        ];

        update_option(self::STATE, $state, false);
        self::clearManifestCache();
        self::refreshThemeCache();
        self::opcache($theme);

        return ['version' => $version, 'files' => $restored, 'deleted' => 0, 'backup' => $backup];
    }

    // ---------------------------------------------------------------- internals

    /**
     * Unzip, verify, back up and copy.
     *
     * @param array<string,mixed> $release
     * @return array{files:int,deleted:int,backup:string}|\WP_Error
     */
    protected static function installFromZip(string $zip, array $release)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $tmp = trailingslashit(get_temp_dir()) . 'amol-patch-' . wp_generate_password(8, false);

        if (! wp_mkdir_p($tmp)) {
            return new \WP_Error('amol_patch_tmp', 'ساخت پوشه‌ی موقت ناموفق بود.');
        }

        $unzipped = unzip_file($zip, $tmp);

        if (is_wp_error($unzipped)) {
            self::rmdir($tmp);

            return new \WP_Error('amol_patch_unzip', 'باز کردن بسته ناموفق بود: ' . $unzipped->get_error_message());
        }

        $root = self::archiveRoot($tmp);
        $files = self::collectFiles($root);

        if (! $files) {
            self::rmdir($tmp);

            return new \WP_Error('amol_patch_empty', 'بسته هیچ فایلی ندارد.');
        }

        $expected = [];

        foreach ((array) ($release['files'] ?? []) as $entry) {
            $relative = is_array($entry) ? self::cleanRelative((string) ($entry['path'] ?? '')) : '';

            if ($relative !== '') {
                $expected[$relative] = strtolower((string) ($entry['sha256'] ?? ''));
            }
        }

        // verify every declared file before touching the theme
        $checked = 0;

        foreach ($expected as $relative => $hash) {
            $source = $root . '/' . $relative;

            if (! is_file($source)) {
                self::rmdir($tmp);

                return new \WP_Error('amol_patch_file_missing', sprintf('فایل «%s» در بسته نیست.', $relative));
            }

            if ($hash !== '' && ! hash_equals($hash, (string) hash_file('sha256', $source))) {
                self::rmdir($tmp);

                return new \WP_Error('amol_patch_file_hash', sprintf('امضای فایل «%s» با فهرست بروزرسانی هم‌خوان نیست.', $relative));
            }

            $checked++;
        }

        if (! $checked && $release['files'] !== []) {
            self::rmdir($tmp);

            return new \WP_Error('amol_patch_files_empty', 'فهرست فایل‌های پچ خالی است.');
        }

        $theme = self::themeDir();
        $fs = self::fs();
        $backup = trailingslashit(wp_upload_dir()['basedir']) . self::BACKUP_DIR . '/' . $release['_version'] . '-' . gmdate('Ymd-His');
        $added = [];
        $copied = 0;

        if (! $fs->mkdir($backup, FS_CHMOD_DIR)) {
            self::rmdir($tmp);

            return new \WP_Error('amol_patch_backup', 'ساخت پوشه‌ی پشتیبان ناموفق بود؛ پچ اعمال نشد.');
        }

        // backup folder lives under uploads/ so it is reachable over HTTP: keep it closed,
        // and tag the two guard files so rollback never mistakes them for theme files.
        $fs->put_contents(
            $backup . '/.htaccess',
            "# amolnovin-backup-protection\nRequire all denied\nDeny from all\n",
            FS_CHMOD_FILE
        );
        $fs->put_contents(
            $backup . '/index.php',
            "<?php // amolnovin-backup-protection\n",
            FS_CHMOD_FILE
        );

        foreach (array_keys($expected) as $relative) {
            $source = $root . '/' . $relative;
            $target = self::safePath($theme . '/' . $relative);

            if (! $target) {
                self::rmdir($tmp);

                return new \WP_Error('amol_patch_path', sprintf('مسیر غیرمجاز در بسته: %s', $relative));
            }

            $dir = dirname($target);

            if (! $fs->is_dir($dir)) {
                $fs->mkdir($dir, FS_CHMOD_DIR);
            }

            if ($fs->exists($target)) {
                $backupFile = $backup . '/' . $relative;
                $fs->mkdir(dirname($backupFile), FS_CHMOD_DIR);
                $fs->copy($target, $backupFile, true);
            } else {
                $added[] = $relative;
            }

            if (! $fs->copy($source, $target, true)) {
                // put back whatever this patch already changed before reporting the failure
                self::restoreBackup($theme, $backup, $added);
                self::rmdir($tmp);

                return new \WP_Error('amol_patch_copy', sprintf('نوشتن فایل «%s» ناموفق بود؛ تغییرات نیمه‌کاره بازگردانی شد.', $relative));
            }

            $copied++;
        }

        $deleted = 0;

        foreach ((array) ($release['deleted'] ?? []) as $relative) {
            $relative = self::cleanRelative((string) $relative);
            $target = self::safePath($theme . '/' . $relative);

            if (! $target || ! $fs->exists($target)) {
                continue;
            }

            $backupFile = $backup . '/' . $relative;
            $fs->mkdir(dirname($backupFile), FS_CHMOD_DIR);
            $fs->copy($target, $backupFile, true);

            if ($fs->delete($target)) {
                $deleted++;
            }
        }

        $state = self::state();
        $state['applied'][$release['_version']] = [
            'date' => time(),
            'backup' => $backup,
            'files' => $copied,
            'deleted' => $deleted,
            'added' => $added,
            'notes' => (string) ($release['notes'] ?? ''),
        ];
        $state['history'][] = [
            'type' => 'apply',
            'version' => $release['_version'],
            'date' => time(),
            'user' => get_current_user_id(),
            'files' => $copied,
            'deleted' => $deleted,
        ];

        update_option(self::STATE, $state, false);
        update_option('amol_options', self::rememberVersion($release['_version']), false);
        \App\Support\Options::flush();
        self::clearManifestCache();
        self::rmdir($tmp);
        self::refreshThemeCache();
        self::opcache($theme);

        do_action('amol/updater/applied', $release['_version'], $state['applied'][$release['_version']]);

        return ['files' => $copied, 'deleted' => $deleted, 'backup' => $backup];
    }

    /** @param array<string,mixed> $release */
    protected static function checkRequirements(array $release)
    {
        $php = (string) ($release['requires']['php'] ?? '');
        $wp = (string) ($release['requires']['wp'] ?? '');

        if ($php !== '' && version_compare(PHP_VERSION, self::cleanVersion($php), '<')) {
            return new \WP_Error('amol_requires_php', sprintf('این پچ به PHP %s یا بالاتر نیاز دارد (نسخه‌ی فعلی: %s).', $php, PHP_VERSION));
        }

        if ($wp !== '' && version_compare((string) get_bloginfo('version'), self::cleanVersion($wp), '<')) {
            return new \WP_Error('amol_requires_wp', sprintf('این پچ به وردپرس %s یا بالاتر نیاز دارد (نسخه‌ی فعلی: %s).', $wp, get_bloginfo('version')));
        }

        return true;
    }

    /** Resolve a `package` field against the manifest location. */
    protected static function packageUrl(string $package)
    {
        $package = trim($package);

        if ($package === '') {
            return new \WP_Error('amol_patch_package', 'این نسخه بسته‌ی پچ ندارد.');
        }

        $url = preg_match('#^https?://#i', $package) ? $package : '';

        if ($url === '') {
            $manifest = (string) Options::get('updates', 'manifest_url', '');
            $base = preg_replace('~/[^/]*$~', '', $manifest);
            $url = $base . '/' . ltrim($package, '/');
        }

        if (! wp_http_validate_url($url) || ! str_starts_with(strtolower($url), 'https://')) {
            return new \WP_Error('amol_patch_insecure', 'بسته‌ی پچ باید با https ارائه شود.');
        }

        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        $allowed = (array) apply_filters('amol/updater/allowed_hosts', [
            'github.com',
            'codeload.github.com',
            'raw.githubusercontent.com',
            'objects.githubusercontent.com',
            'release-assets.githubusercontent.com',
        ]);

        $manifestHost = strtolower((string) wp_parse_url((string) Options::get('updates', 'manifest_url', ''), PHP_URL_HOST));

        if ($manifestHost !== '') {
            $allowed[] = $manifestHost;
        }

        if (! in_array($host, array_map('strtolower', $allowed), true)) {
            return new \WP_Error('amol_patch_host', sprintf('میزبان %s در فهرست مجاز بروزرسانی نیست.', $host));
        }

        return $url;
    }

    /** @return string|\WP_Error temp file path */
    protected static function download(string $url)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $file = wp_tempnam($url);

        if (! $file) {
            return new \WP_Error('amol_patch_tmp', 'ساخت فایل موقت ناموفق بود.');
        }

        $response = wp_remote_get($url, [
            'timeout' => 60,
            'stream' => false,
            'redirection' => 3,
            'headers' => ['User-Agent' => 'AmolNovin-Theme-Updater/' . self::currentVersion()],
        ]);

        if (is_wp_error($response)) {
            @unlink($file); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink

            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            @unlink($file); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink

            return new \WP_Error('amol_patch_download', sprintf('دریافت بسته‌ی پچ ناموفق بود (کد %d).', $code));
        }

        $body = wp_remote_retrieve_body($response);

        if (! is_string($body) || $body === '' || file_put_contents($file, $body) === false) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            @unlink($file); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink

            return new \WP_Error('amol_patch_write', 'نوشتن بسته‌ی دانلودشده ناموفق بود.');
        }

        return $file;
    }

    /** Read `manifest.json` from an uploaded patch zip. @return array<string,mixed>|\WP_Error */
    protected static function readPatchManifest(string $zip)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $tmp = trailingslashit(get_temp_dir()) . 'amol-patch-manifest-' . wp_generate_password(8, false);
        wp_mkdir_p($tmp);
        $unzipped = unzip_file($zip, $tmp);

        if (is_wp_error($unzipped)) {
            self::rmdir($tmp);

            return new \WP_Error('amol_patch_unzip', 'بسته باز نشد: ' . $unzipped->get_error_message());
        }

        $root = self::archiveRoot($tmp);
        $manifest = $root . '/manifest.json';
        $data = is_file($manifest) ? json_decode((string) file_get_contents($manifest), true) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        self::rmdir($tmp);

        if (! is_array($data) || empty($data['version']) || empty($data['files']) || ! is_array($data['files'])) {
            return new \WP_Error('amol_patch_manifest', 'این بسته فایل manifest.json معتبر (نسخه + فهرست فایل‌ها) ندارد.');
        }

        foreach ($data['files'] as $entry) {
            if (! is_array($entry) || empty($entry['path']) || empty($entry['sha256'])) {
                return new \WP_Error('amol_patch_manifest', 'هر فایل پچ باید path و sha256 داشته باشد.');
            }
        }

        return $data;
    }

    /** Support both «files at the archive root» and «one wrapper folder». */
    protected static function archiveRoot(string $dir): string
    {
        $entries = array_values(array_filter((array) scandir($dir), static fn ($f) => ! in_array($f, ['.', '..', '.DS_Store', '__MACOSX'], true)));

        if (count($entries) === 1 && is_dir($dir . '/' . $entries[0])) {
            return $dir . '/' . $entries[0];
        }

        return $dir;
    }

    /** @return array<int,string> every file of the archive, relative to $root */
    protected static function collectFiles(string $root): array
    {
        $out = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if (! $item->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', ltrim(str_replace($root, '', $item->getPathname()), '/'));

            if (str_starts_with($relative, '__MACOSX/') || basename($relative) === '.DS_Store') {
                continue;
            }

            $out[] = $relative;
        }

        return $out;
    }

    /** Normalise a relative path or return '' when it escapes the theme. */
    protected static function safePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $dir = str_replace('\\', '/', self::themeDir());

        if (! str_starts_with($path, $dir . '/')) {
            return '';
        }

        $relative = self::cleanRelative(substr($path, strlen($dir) + 1));

        if ($relative === '' || str_contains($relative, '..') || str_starts_with($relative, '/')) {
            return '';
        }

        return $dir . '/' . $relative;
    }

    public static function cleanRelative(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = (string) preg_replace('#^\./#', '', $path);

        if (str_contains($path, "\0") || str_starts_with($path, '/') || preg_match('#(^|/)\.\.(/|$)#', $path)) {
            return '';
        }

        return ltrim($path, '/');
    }

    public static function cleanVersion(string $version): string
    {
        return (string) preg_replace('/[^0-9A-Za-z\.\-]/', '', ltrim(trim($version), 'vV'));
    }

    public static function isNewer(string $a, string $b): bool
    {
        return version_compare(self::cleanVersion($a), self::cleanVersion($b), '>');
    }

    protected static function fs()
    {
        global $wp_filesystem;

        if (! $wp_filesystem instanceof \WP_Filesystem_Base) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : new \WP_Filesystem_Direct(null);
    }

    protected static function rmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $fs = self::fs();

        if (! $fs->delete($dir, true) && is_dir($dir)) {
            @rmdir($dir); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
        }
    }

    /**
     * Delete the files a patch added and copy every backed-up file back over the theme.
     *
     * @param array<int,string> $added files that did not exist before the patch
     */
    protected static function restoreBackup(string $theme, string $backup, array $added): int
    {
        $fs = self::fs();

        foreach ($added as $relative) {
            $target = self::safePath($theme . '/' . self::cleanRelative((string) $relative));

            if ($target && $fs->exists($target)) {
                $fs->delete($target);
            }
        }

        if (! is_dir($backup)) {
            return 0;
        }

        $restored = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backup, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if (! $item->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($backup, '', $item->getPathname()), '/');

            // the guard files written into every backup are not part of the theme
            if (in_array($relative, ['.htaccess', 'index.php'], true)
                && false !== strpos((string) $fs->get_contents($item->getPathname()), 'amolnovin-backup-protection')) {
                continue;
            }

            $target = self::safePath($theme . '/' . $relative);

            if (! $target) {
                continue;
            }

            if (! $fs->is_dir(dirname($target))) {
                $fs->mkdir(dirname($target), FS_CHMOD_DIR);
            }

            if ($fs->copy($item->getPathname(), $target, true)) {
                $restored++;
            }
        }

        return $restored;
    }

    /** WordPress caches the theme headers; without this the old version number stays until the cache expires. */
    protected static function refreshThemeCache(): void
    {
        if (function_exists('wp_clean_themes_cache')) {
            wp_clean_themes_cache();
        }

        if (function_exists('wp_clean_theme_cache')) {
            wp_clean_theme_cache();
        }
    }

    protected static function opcache(string $dir): void
    {
        if (! function_exists('opcache_reset')) {
            return;
        }

        if (function_exists('opcache_invalidate')) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $item) {
                if ($item->isFile() && str_ends_with($item->getFilename(), '.php')) {
                    @opcache_invalidate($item->getPathname(), true); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                }
            }
        }
    }

    /** @param array<string,mixed> $manifest */
    protected static function rememberCheck(array $manifest): void
    {
        $all = get_option('amol_options', []);
        $all = is_array($all) ? $all : [];
        $all['updates'] = is_array($all['updates'] ?? null) ? $all['updates'] : [];
        $all['updates']['last_check'] = time();
        $all['updates']['last_version'] = self::currentVersion();

        update_option('amol_options', $all, false);
        Options::flush();
    }

    /** @return array<string,mixed> */
    protected static function rememberVersion(string $version): array
    {
        $all = get_option('amol_options', []);
        $all = is_array($all) ? $all : [];
        $all['updates'] = is_array($all['updates'] ?? null) ? $all['updates'] : [];
        $all['updates']['last_check'] = time();
        $all['updates']['last_version'] = $version;

        return $all;
    }

    public static function clearManifestCache(): void
    {
        delete_transient(self::CACHE);
    }
}
