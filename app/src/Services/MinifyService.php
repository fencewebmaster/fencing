<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\FileHelper;
use Fc\Admin\Settings\MinifySettings;

/**
 * The minified CSS/JS copies of the frontend's and the admin's own files (public/assets/min/,
 * same relative paths as the sources): what state each copy is in, and the esbuild build that
 * refreshes them. Shared by build/minify/build.php (the CLI) and Settings → Minify CSS & JS (the
 * admin), so both write byte-identical copies. Each file is minified on its own — no bundling —
 * because every <script> relies on its own global scope and the load order its page gives it.
 */
final class MinifyService
{
    /**
     * Group key (also its MinifySettings switch) => its area, file type and source folder under
     * public/assets; each copy sits at min/<same path>.
     */
    public const GROUPS = [
        'css' => ['area' => 'frontend', 'type' => 'css', 'dir' => 'css/frontend'],
        'js' => ['area' => 'frontend', 'type' => 'js', 'dir' => 'js/frontend'],
        'adminCss' => ['area' => 'admin', 'type' => 'css', 'dir' => 'css/admin'],
        'adminJs' => ['area' => 'admin', 'type' => 'js', 'dir' => 'js/admin'],
    ];

    /** Where build/minify/README.md says to save the binary (ESBUILD_BIN overrides it). */
    private const TOOL_PATHS = ['build/minify/esbuild.exe', 'build/minify/esbuild'];

    /** One build at a time, CLI or admin: two esbuild runs writing the same copy would interleave. */
    private const LOCK_FILE = 'writable/storage/cache/minify.lock';

    /** The last build's per-file errors, so the tab still shows a "Build failed" row after a reload. */
    private const FAILED_FILE = 'writable/storage/cache/minify-build.json';

    /**
     * Left to itself esbuild rewrites into newer syntax than the source used (??, catch {}, inset,
     * #rrggbbaa, two-position gradient stops), so a copy could fail on a browser that runs its
     * original; these make it write the older equivalent instead.
     */
    private const SYNTAX_FLOOR = [
        '--supported:nullish-coalescing=false',
        '--supported:optional-catch-binding=false',
        '--supported:logical-assignment=false',
        '--supported:inset-property=false',
        '--supported:hex-rgba=false',
        '--supported:gradient-double-position=false',
    ];

    /**
     * One group's sources as 'css/frontend/style.css' paths (relative to public/assets), folder by
     * folder so a folder's files sit together. Anything already *.min.* ships minified and is skipped.
     *
     * @return list<string>
     */
    public static function sources(string $group): array
    {
        $dir = self::GROUPS[$group]['dir'] ?? '';
        $base = self::assetsRoot();
        if ($dir === '' || !is_dir($base . '/' . $dir)) {
            return [];
        }

        $found = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base . '/' . $dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = substr(str_replace('\\', '/', $file->getPathname()), strlen($base) + 1);
            if (self::groupOf($path) === $group) {
                $found[] = $path;
            }
        }
        usort($found, static fn (string $a, string $b): int => [dirname($a), basename($a)] <=> [dirname($b), basename($b)]);

        return $found;
    }

    /** The group a 'css/admin/theme.css' path (relative to public/assets) belongs to; '' for any other file. */
    public static function groupOf(string $relative): string
    {
        foreach (self::GROUPS as $key => $group) {
            if (str_starts_with($relative, $group['dir'] . '/')
                && str_ends_with($relative, '.' . $group['type'])
                && !str_ends_with($relative, '.min.' . $group['type'])
            ) {
                return $key;
            }
        }

        return '';
    }

    /**
     * Every source with its copy's state. 'fresh' = the copy is at least as new as the source (the
     * only state asset() serves), 'stale' = the source was edited since, 'missing' = never built,
     * 'failed' = the last build could not minify it (its copy, if any, is whatever was there before).
     *
     * @return list<array{path:string,group:string,area:string,type:string,source:array{bytes:int,mtime:int},copy:array{bytes:int,mtime:int}|null,state:string,fresh:bool,served:bool,error:string}>
     */
    public static function files(): array
    {
        $base = self::assetsRoot();
        $failed = self::lastFailures();
        $rows = [];
        foreach (self::GROUPS as $group => $meta) {
            $enabled = MinifySettings::enabled($group);
            foreach (self::sources($group) as $path) {
                $source = self::fileInfo($base . '/' . $path) ?? ['bytes' => 0, 'mtime' => 0];
                $copy = self::fileInfo($base . '/min/' . $path);
                // 'fresh' is the mtime test asset() applies; a failed build leaves an older copy in place, still served while it is fresh.
                $fresh = $copy !== null && $copy['mtime'] >= $source['mtime'];
                $state = $copy === null ? 'missing' : ($fresh ? 'fresh' : 'stale');
                $error = '';
                if (isset($failed[$path])) {
                    $state = 'failed';
                    $error = (string) $failed[$path];
                }
                $rows[] = [
                    'path' => $path,
                    'group' => $group,
                    'area' => $meta['area'],
                    'type' => $meta['type'],
                    'source' => $source,
                    'copy' => $copy,
                    'state' => $state,
                    'fresh' => $fresh,
                    'served' => $enabled && $fresh,
                    'error' => $error,
                ];
            }
        }

        return $rows;
    }

    /**
     * Whether this server can run the build, and why not when it can't. Production has no binary
     * (it is gitignored; the copies are committed), which is a fact to show, not an error.
     *
     * @return array{available:bool,path:string,reason:string}
     */
    public static function tool(): array
    {
        $binary = self::binary();
        if ($binary === '') {
            return [
                'available' => false,
                'path' => '',
                'reason' => 'esbuild is not installed on this server (build/minify/esbuild is missing). The copies committed in git are served while they are up to date; see build/minify/README.md to install it.',
            ];
        }
        if (!function_exists('proc_open')) {
            return [
                'available' => false,
                'path' => self::relative($binary),
                'reason' => 'PHP cannot start programs on this server (proc_open is disabled), so the copies can only be rebuilt from the command line: php build/minify/build.php.',
            ];
        }

        return ['available' => true, 'path' => self::relative($binary), 'reason' => ''];
    }

    /**
     * Minify every source of the given groups into public/assets/min/ and drop copies whose source
     * is gone, so min/ mirrors the sources. One esbuild run per file; a file that fails keeps
     * whatever copy it had (asset() falls back to the source once that copy is older).
     *
     * @param list<string> $groups any of the GROUPS keys
     * @return array{ok:bool,error?:string,locked?:bool,built:int,failed:array<string,string>,removed:list<string>,bytes:array{source:int,copy:int,sourceGzip:int,copyGzip:int},seconds:float}
     */
    public static function build(array $groups): array
    {
        $tool = self::tool();
        $result = ['ok' => false, 'built' => 0, 'failed' => [], 'removed' => [], 'bytes' => ['source' => 0, 'copy' => 0, 'sourceGzip' => 0, 'copyGzip' => 0], 'seconds' => 0.0];
        if (!$tool['available']) {
            $result['error'] = $tool['reason'];

            return $result;
        }

        $groups = array_values(array_intersect(array_keys(self::GROUPS), $groups));
        if ($groups === []) {
            $result['error'] = 'Nothing to minify: choose a group of files.';

            return $result;
        }

        $lockPath = self::root() . '/' . self::LOCK_FILE;
        if (!is_dir(dirname($lockPath))) {
            @mkdir(dirname($lockPath), 0775, true);
        }
        $lock = @fopen($lockPath, 'c');
        if ($lock === false) {
            $result['error'] = 'Cannot open ' . self::LOCK_FILE . ' — check that writable/storage/cache is writable.';

            return $result;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            $result['locked'] = true;
            $result['error'] = 'Another build is already running — try again in a moment.';

            return $result;
        }

        try {
            return self::buildLocked($groups, $result);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @param list<string> $groups
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private static function buildLocked(array $groups, array $result): array
    {
        // Under Apache the default 120s limit would cut a slow disk short; every group together takes a few seconds.
        @set_time_limit(300);
        $started = microtime(true);
        $base = self::assetsRoot();
        $binary = self::binary();

        foreach ($groups as $group) {
            $sources = self::sources($group);
            foreach ($sources as $path) {
                $src = $base . '/' . $path;
                $out = $base . '/min/' . $path;
                $dir = dirname($out);
                if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    $result['failed'][$path] = 'Cannot create ' . self::relative($dir) . '.';
                    continue;
                }

                $run = self::runEsbuild($binary, $src, $out);
                if ($run !== '') {
                    $result['failed'][$path] = $run;
                    continue;
                }
                if (self::GROUPS[$group]['type'] === 'css') {
                    // The copy sits one folder deeper (min/), so relative url()s need one more "../".
                    $css = (string) file_get_contents($out);
                    $css = (string) preg_replace_callback(
                        '#url\(\s*(["\']?)(?![a-z][a-z0-9+.-]*:|/|\#)([^"\')]+)\1\s*\)#i',
                        static fn (array $m): string => 'url(' . $m[1] . '../' . $m[2] . $m[1] . ')',
                        $css
                    );
                    file_put_contents($out, $css);
                }

                // A source stamped in the future (a copy from another machine) would leave its fresh copy "stale" forever.
                clearstatcache(false, $src);
                $sourceTime = (int) filemtime($src);
                if ($sourceTime > time()) {
                    @touch($out, $sourceTime);
                    @touch($out . '.map', $sourceTime);
                }

                $a = (string) file_get_contents($src);
                $b = (string) file_get_contents($out);
                $result['bytes']['source'] += strlen($a);
                $result['bytes']['copy'] += strlen($b);
                $result['bytes']['sourceGzip'] += strlen((string) gzencode($a, 9));
                $result['bytes']['copyGzip'] += strlen((string) gzencode($b, 9));
                $result['built']++;
            }

            foreach (self::orphanCopies($group, $sources) as $orphan) {
                if (@unlink($base . '/min/' . $orphan)) {
                    $result['removed'][] = $orphan;
                }
            }
        }

        $result['seconds'] = round(microtime(true) - $started, 2);
        $result['ok'] = $result['failed'] === [];
        if (!$result['ok']) {
            $result['error'] = count($result['failed']) . ' of ' . ($result['built'] + count($result['failed'])) . ' files could not be minified — see the rows marked Build failed.';
        }
        self::rememberFailures($groups, $result['failed']);

        return $result;
    }

    /**
     * The last build's per-file errors by path, from writable/storage/cache/minify-build.json.
     *
     * @return array<string, string>
     */
    private static function lastFailures(): array
    {
        $data = FileHelper::readJsonFile(self::root() . '/' . self::FAILED_FILE);
        $failed = is_array($data['failed'] ?? null) ? $data['failed'] : [];

        return array_filter($failed, static fn ($message, $path): bool => is_string($path) && is_string($message), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Replace the remembered errors of the groups just built; other groups keep theirs.
     *
     * @param list<string> $groups
     * @param array<string, string> $failed
     */
    private static function rememberFailures(array $groups, array $failed): void
    {
        $kept = [];
        foreach (self::lastFailures() as $path => $message) {
            if (!in_array(self::groupOf($path), $groups, true)) {
                $kept[$path] = $message;
            }
        }
        $all = $kept + $failed;
        $file = self::root() . '/' . self::FAILED_FILE;
        if ($all === []) {
            @unlink($file);

            return;
        }
        // esbuild echoes the offending source line, which need not be UTF-8; a false here would truncate the file.
        $json = json_encode(['at' => time(), 'failed' => $all], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if (is_string($json)) {
            @file_put_contents($file, $json, LOCK_EX);
        }
    }

    /** @return string '' on success, else esbuild's error text */
    private static function runEsbuild(string $binary, string $src, string $out): string
    {
        $argv = array_merge(
            [$binary, $src, '--minify'],
            self::SYNTAX_FLOOR,
            ['--sourcemap', '--sources-content=false', '--log-level=error', '--outfile=' . $out]
        );
        $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $pipes = [];
        // argv form: no shell, so the paths need no quoting; the string form is the fallback older PHP takes.
        $process = @proc_open($argv, $descriptor, $pipes, self::root());
        if (!is_resource($process)) {
            $process = @proc_open(implode(' ', array_map('escapeshellarg', $argv)), $descriptor, $pipes, self::root());
        }
        if (!is_resource($process)) {
            return 'esbuild could not be started.';
        }

        fclose($pipes[0]);
        // stderr first: esbuild prints nothing to stdout here, and its errors can exceed Windows' 4KB pipe buffer.
        $stderr = (string) stream_get_contents($pipes[2]);
        $stdout = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);
        if ($code !== 0) {
            $text = trim($stderr !== '' ? $stderr : $stdout);

            return $text !== '' ? $text : 'esbuild exited with code ' . $code . '.';
        }

        return '';
    }

    /**
     * Copies (and their .map files) under min/<group folder> whose source no longer exists.
     *
     * @param list<string> $sources
     * @return list<string>
     */
    private static function orphanCopies(string $group, array $sources): array
    {
        $dir = self::assetsRoot() . '/min/' . self::GROUPS[$group]['dir'];
        if (!is_dir($dir)) {
            return [];
        }
        $known = array_flip($sources);
        $orphans = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = substr(str_replace('\\', '/', $file->getPathname()), strlen(self::assetsRoot() . '/min/'));
            $source = (string) preg_replace('#\.map$#', '', $path);
            if (!isset($known[$source])) {
                $orphans[] = $path;
            }
        }
        sort($orphans);

        return $orphans;
    }

    /** The esbuild binary's absolute path: ESBUILD_BIN when set, else the README's location; '' when absent. */
    private static function binary(): string
    {
        $env = trim((string) getenv('ESBUILD_BIN'));
        $candidates = $env !== '' ? [$env] : array_map(static fn (string $p): string => self::root() . '/' . $p, self::TOOL_PATHS);
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return str_replace('\\', '/', $candidate);
            }
        }

        return '';
    }

    /** @return array{bytes:int,mtime:int}|null null when the file is missing */
    private static function fileInfo(string $absolute): ?array
    {
        if (!is_file($absolute)) {
            return null;
        }
        clearstatcache(false, $absolute);

        return ['bytes' => (int) filesize($absolute), 'mtime' => (int) filemtime($absolute)];
    }

    private static function root(): string
    {
        return rtrim(str_replace('\\', '/', (string) FC_ROOT), '/');
    }

    private static function assetsRoot(): string
    {
        return self::root() . '/public/assets';
    }

    /** A path under the project root as 'build/minify/esbuild.exe'; anything else unchanged. */
    private static function relative(string $absolute): string
    {
        $absolute = str_replace('\\', '/', $absolute);
        $root = self::root() . '/';

        return str_starts_with($absolute, $root) ? substr($absolute, strlen($root)) : $absolute;
    }
}
