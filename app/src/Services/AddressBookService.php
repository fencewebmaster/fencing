<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\FileHelper;
use Fc\Admin\Helpers\FormatHelper;
use Fc\Admin\Settings\SystemSettings;

/**
 * Settings → System → Address Book: the details, export and import of writable/au.jsonl, the street list
 * behind the planner's Address suggestions (AddressLookupService, built by build/address/build.php).
 *
 * The file is ~70 MB and per site, so it moves between sites as gzip (~17 MB) and arrives in chunks small
 * enough for any host's upload limit. An import is checked line by line before it replaces the live file:
 * the lookup binary-searches the keys, so one bad or out-of-order line would break suggestions silently.
 * The counts the meta record does not carry (addresses, suburbs, postcodes, per state) take one 0.6s pass,
 * cached in writable/storage/cache/address-book.json against the file's path, size, mtime and inode.
 */
final class AddressBookService
{
    /** Bytes per import request: below the 2 MB post_max_size some hosts still ship. */
    public const CHUNK_BYTES = 1500000;

    /** Largest upload accepted (gzip or plain) and largest file it may unpack to. */
    private const MAX_UPLOAD_BYTES = 200 * 1024 * 1024;
    private const MAX_FILE_BYTES = 300 * 1024 * 1024;

    private const CACHE_FILE = 'address-book.json';

    // A data line exactly as build.php writes it: key, street, locality, state, postcode, address count.
    private const LINE = '/^\{"k":"([A-Z0-9 ]+)","n":"(?:[^"\\\\]|\\\\.)*","l":"((?:[^"\\\\]|\\\\.)*)","s":"([A-Z]{2,3})","p":"(\d{0,4})","c":(\d+)\}$/';

    /**
     * The file's details for the System tab: name, size, dates, release and counts, or exists=false.
     *
     * @return array<string, mixed>
     */
    public static function details(): array
    {
        $path = AddressLookupService::dataPath();
        $base = [
            'file' => AddressLookupService::DATA_FILE,
            'path' => 'writable/' . AddressLookupService::DATA_FILE,
            'chunkBytes' => self::CHUNK_BYTES,
            'canImport' => PermissionService::isSuperAdmin(),
            'stateHint' => AddressLookupService::stateHintForHost((string) ($_SERVER['HTTP_HOST'] ?? '')),
        ];

        clearstatcache(true, $path);
        if (!is_file($path)) {
            return $base + ['exists' => false];
        }

        $key = self::cacheKey($path);
        $cached = FileHelper::readJsonFile(self::cacheFile());
        $stats = is_array($cached) && ($cached['key'] ?? null) === $key && is_array($cached['stats'] ?? null)
            ? $cached['stats']
            : null;
        if ($stats === null) {
            $stats = self::scan($path);
            CacheStorageService::cacheDir();
            @file_put_contents(self::cacheFile(), (string) json_encode(['key' => $key, 'stats' => $stats], JSON_UNESCAPED_SLASHES), LOCK_EX);
        }

        $size = (int) $key['size'];
        $mtime = (int) $key['mtime'];
        $built = (string) ($stats['meta']['built'] ?? '');

        return $base + [
            'exists' => true,
            'valid' => (bool) $stats['ok'],
            'error' => (string) ($stats['error'] ?? ''),
            'size' => $size,
            'sizeLabel' => FormatHelper::bytes($size),
            'updatedAt' => date('Y-m-d H:i:s', $mtime),
            'updatedAtLabel' => date(SystemSettings::dateFormatPhp(), $mtime),
            'source' => (string) ($stats['meta']['source'] ?? ''),
            'built' => $built,
            'builtLabel' => $built !== '' && strtotime($built) !== false ? date('M. j, Y', (int) strtotime($built)) : '',
            'licence' => (string) ($stats['meta']['licence'] ?? ''),
            'streets' => (int) ($stats['streets'] ?? 0),
            'addresses' => (int) ($stats['addresses'] ?? 0),
            'suburbs' => (int) ($stats['suburbs'] ?? 0),
            'postcodes' => (int) ($stats['postcodes'] ?? 0),
            'states' => is_array($stats['states'] ?? null) ? $stats['states'] : [],
        ];
    }

    /**
     * Streams the file as gzip, compressed on the fly so nothing is staged on disk. Returns false when
     * there is no file to send (the caller answers 404); exits after a send.
     */
    public static function sendExport(): bool
    {
        $path = AddressLookupService::dataPath();
        $in = is_file($path) ? @fopen($path, 'rb') : false;
        if ($in === false) {
            return false;
        }

        $meta = AddressLookupService::meta();
        $built = preg_replace('/[^0-9-]/', '', (string) ($meta['built'] ?? '')) ?: date('Y-m-d', (int) filemtime($path));
        $host = trim((string) preg_replace('/[^a-z0-9.]+/i', '-', (string) ($_SERVER['HTTP_HOST'] ?? '')), '-');

        @set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="au-' . $built . ($host !== '' ? '-' . $host : '') . '.jsonl.gz"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        $zip = deflate_init(ZLIB_ENCODING_GZIP, ['level' => 6]);
        while (!feof($in)) {
            $chunk = fread($in, 1048576);
            if ($chunk === false) {
                break;
            }
            echo deflate_add($zip, $chunk, ZLIB_NO_FLUSH);
            flush();
        }
        echo deflate_add($zip, '', ZLIB_FINISH);
        fclose($in);
        exit;
    }

    /**
     * Takes one chunk of an import. Chunks arrive in order, each at the byte offset the staged part has
     * reached; the last one unpacks a gzip, checks every line and only then replaces the live file.
     *
     * @return array<string, mixed> ['ok' => bool, 'done' => bool, 'error'?, 'status'?, 'details'?]
     */
    public static function importChunk(string $uploadId, int $index, int $total, int $offset, int $size, string $chunkFile): array
    {
        if (!preg_match('/^[a-z0-9]{8,40}$/', $uploadId) || $index < 0 || $total < 1 || $index >= $total || $offset < 0) {
            return ['ok' => false, 'error' => 'That upload request is incomplete. Try the import again.', 'status' => 400];
        }
        if ($size < 1 || $size > self::MAX_UPLOAD_BYTES) {
            return ['ok' => false, 'error' => 'The address file must be between 1 byte and ' . FormatHelper::bytes(self::MAX_UPLOAD_BYTES) . '.', 'status' => 400];
        }

        $part = self::stagePath($uploadId) . '.part';
        if ($index === 0) {
            self::sweepStale();
            @unlink($part);
        }
        clearstatcache(true, $part);
        $have = is_file($part) ? (int) filesize($part) : 0;
        if ($have !== $offset) {
            return ['ok' => false, 'error' => 'The upload lost its place (a chunk was missed). Start the import again.', 'status' => 409];
        }

        $data = @file_get_contents($chunkFile);
        if (!is_string($data) || $data === '' || $offset + strlen($data) > $size) {
            @unlink($part);
            return ['ok' => false, 'error' => 'A chunk of the upload could not be read. Start the import again.', 'status' => 400];
        }
        if (@file_put_contents($part, $data, FILE_APPEND | LOCK_EX) === false) {
            @unlink($part);
            return ['ok' => false, 'error' => 'PHP can\'t write to the writable folder, so the file wasn\'t imported.', 'status' => 500];
        }

        if ($index < $total - 1) {
            return ['ok' => true, 'done' => false];
        }

        clearstatcache(true, $part);
        if ((int) filesize($part) !== $size) {
            @unlink($part);
            return ['ok' => false, 'error' => 'The upload arrived incomplete. Start the import again.', 'status' => 400];
        }

        return self::finishImport($uploadId, $part);
    }

    /**
     * @return array<string, mixed>
     */
    private static function finishImport(string $uploadId, string $part): array
    {
        $staged = self::stagePath($uploadId);
        @set_time_limit(300);

        $head = (string) @file_get_contents($part, false, null, 0, 2);
        if ($head === "\x1f\x8b") {
            $unpacked = self::gunzip($part, $staged);
            @unlink($part);
            if ($unpacked !== '') {
                @unlink($staged);
                return ['ok' => false, 'error' => $unpacked, 'status' => 400];
            }
        } elseif (!@rename($part, $staged)) {
            @unlink($part);
            return ['ok' => false, 'error' => 'PHP can\'t write to the writable folder, so the file wasn\'t imported.', 'status' => 500];
        }

        $stats = self::scan($staged);
        if (!$stats['ok']) {
            @unlink($staged);
            return ['ok' => false, 'error' => (string) $stats['error'], 'status' => 400];
        }

        $live = AddressLookupService::dataPath();
        if (!@rename($staged, $live)) {
            @unlink($staged);
            return ['ok' => false, 'error' => 'The new file checked out but couldn\'t replace the old one (it may be in use). Try again in a moment.', 'status' => 500];
        }
        @chmod($live, 0644);

        CacheStorageService::cacheDir();
        @file_put_contents(self::cacheFile(), (string) json_encode(['key' => self::cacheKey($live), 'stats' => $stats], JSON_UNESCAPED_SLASHES), LOCK_EX);

        return ['ok' => true, 'done' => true, 'details' => self::details()];
    }

    /** Unpacks a gzip upload, stopping at MAX_FILE_BYTES; '' on success, else the reason. */
    private static function gunzip(string $from, string $to): string
    {
        $in = @gzopen($from, 'rb');
        $out = @fopen($to, 'wb');
        if ($in === false || $out === false) {
            if ($in !== false) {
                gzclose($in);
            }
            if ($out !== false) {
                fclose($out);
            }
            return 'The upload could not be unpacked.';
        }

        $written = 0;
        $error = '';
        while (!gzeof($in)) {
            $chunk = gzread($in, 1048576);
            if ($chunk === false) {
                $error = 'The .gz file is damaged.';
                break;
            }
            $written += strlen($chunk);
            if ($written > self::MAX_FILE_BYTES) {
                $error = 'The unpacked file is larger than ' . FormatHelper::bytes(self::MAX_FILE_BYTES) . '.';
                break;
            }
            fwrite($out, $chunk);
        }
        gzclose($in);
        fclose($out);

        return $error;
    }

    /**
     * One pass over a file: checks the meta line, every data line's shape and the key order the lookup
     * relies on, and counts streets, addresses, suburbs and postcodes, in total and per state.
     *
     * @return array<string, mixed> ['ok' => bool, 'error' => string, 'meta' => [...], 'streets' => int, …]
     */
    private static function scan(string $path): array
    {
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return ['ok' => false, 'error' => 'The address file can\'t be read.'];
        }

        @set_time_limit(300);
        $first = fgets($fh);
        $meta = is_string($first) && strncmp($first, '{"k":"' . AddressLookupService::META_KEY . '"', 8) === 0 ? json_decode($first, true) : null;
        if (!is_array($meta)) {
            fclose($fh);
            return ['ok' => false, 'error' => 'This isn\'t an address book file: its first line should be the G-NAF meta record (build/address/build.php writes it).'];
        }
        unset($meta['k'], $meta['abbr']);

        $streets = 0;
        $addresses = 0;
        $suburbs = [];
        $postcodes = [];
        $states = [];
        $previous = '';
        $lineNo = 1;
        $error = '';
        while (($line = fgets($fh)) !== false) {
            $lineNo++;
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }
            if (!preg_match(self::LINE, $line, $m)) {
                $error = 'Line ' . number_format($lineNo) . ' isn\'t an address line: ' . substr($line, 0, 80);
                break;
            }
            if (strcmp($m[1], $previous) < 0) {
                $error = 'Line ' . number_format($lineNo) . ' is out of order, so suggestions would miss streets. Rebuild the file with build/address/build.php.';
                break;
            }
            $previous = $m[1];
            $state = $m[3];
            $count = (int) $m[5];
            $streets++;
            $addresses += $count;
            $postcodes[$m[4]] = true;
            if (!isset($states[$state])) {
                $states[$state] = ['state' => $state, 'suburbs' => 0, 'streets' => 0, 'addresses' => 0];
            }
            $states[$state]['streets']++;
            $states[$state]['addresses'] += $count;
            if (!isset($suburbs[$m[2] . '|' . $state])) {
                $suburbs[$m[2] . '|' . $state] = true;
                $states[$state]['suburbs']++;
            }
        }
        fclose($fh);

        if ($error === '' && $streets === 0) {
            $error = 'The file has no street lines.';
        }
        if ($error !== '') {
            return ['ok' => false, 'error' => $error, 'meta' => $meta];
        }
        ksort($states);

        return [
            'ok' => true,
            'error' => '',
            'meta' => $meta,
            'streets' => $streets,
            'addresses' => $addresses,
            'suburbs' => count($suburbs),
            'postcodes' => count($postcodes),
            'states' => array_values($states),
        ];
    }

    /** Where an upload is staged: matches .gitignore's writable/au.jsonl.tmp.* */
    private static function stagePath(string $uploadId): string
    {
        return AddressLookupService::dataPath() . '.tmp.' . $uploadId;
    }

    /** Drops staged uploads idle for 15 minutes: a cancelled or abandoned import leaves its pieces behind. */
    private static function sweepStale(): void
    {
        foreach (glob(AddressLookupService::dataPath() . '.tmp.*') ?: [] as $file) {
            if (is_file($file) && (int) @filemtime($file) < time() - 900) {
                @unlink($file);
            }
        }
    }

    /**
     * @return array{path:string, size:int, mtime:int, inode:int}
     */
    private static function cacheKey(string $path): array
    {
        clearstatcache(true, $path);

        return [
            'path' => $path,
            'size' => (int) @filesize($path),
            'mtime' => (int) @filemtime($path),
            'inode' => (int) @fileinode($path),
        ];
    }

    private static function cacheFile(): string
    {
        return CacheStorageService::cacheDir() . DIRECTORY_SEPARATOR . self::CACHE_FILE;
    }
}
