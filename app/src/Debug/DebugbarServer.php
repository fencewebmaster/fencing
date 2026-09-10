<?php

declare(strict_types=1);

namespace Fc\Admin\Debug;

use Fc\Admin\Services\AppConfigService;
use Fc\Admin\Services\Database;
use Fc\Admin\Services\SiteRegistryService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\ConsoleSettings;

/**
 * Server half of the Debugbar: request timing, DB query log and PHP error capture,
 * delivered to the frontend as a JSON island emitted by partials/footer.php.
 *
 * Master switch is Settings -> Console -> Debug Mode (ConsoleSettings::debugMode()) and
 * nothing else. Armed once per request from the frontend front controller (index.php);
 * every public method is a hard no-op when disabled, and everything here observes only -
 * no query results, responses or calculator behaviour are ever altered.
 */
final class DebugbarServer
{
    /**
     * Per-field ceiling for inlining a decoded fc_data JSON blob into the island. The island
     * ships inside the page HTML on every load, so an unbounded session field would be page
     * weight paid by every visitor while Debug Mode is on.
     */
    private const SESSION_JSON_MAX_BYTES = 65536;

    private static ?bool $enabled = null;

    private static float $startedAt = 0.0;

    /** @var list<array{kind:string,sql:string,ms:?float,error:?string}> */
    private static array $queries = [];

    private static int $queryCount = 0;

    private static float $queryMs = 0.0;

    private static int $droppedQueries = 0;

    /** @var list<array{severity:string,message:string,file:string,line:int}> */
    private static array $phpErrors = [];

    private static int $droppedPhpErrors = 0;

    private static int $maxEntries = 200;

    private static bool $verbose = false;

    /**
     * Arm the collectors when Debug Mode is on. Called before dispatch by the frontend
     * front controller only - the admin keeps its existing behaviour untouched.
     */
    public static function bootIfEnabled(): bool
    {
        if (self::$enabled !== null) {
            return self::$enabled;
        }

        try {
            self::$enabled = ConsoleSettings::debugMode();
        } catch (\Throwable) {
            self::$enabled = false;
        }

        if (!self::$enabled) {
            return false;
        }

        try {
            self::$startedAt = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
            self::$maxEntries = ConsoleSettings::debugMaxEntries();
            self::$verbose = ConsoleSettings::debugVerbose();

            // Record-only error handler: returning false hands every error straight back to
            // PHP's normal handling, so display/log behaviour stays byte-identical to today.
            // Installed before dispatch, so bootstrap/controller/model diagnostics are seen
            // under the php.ini mask; recordPhpError() then honours whatever mask is in force.
            set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
                DebugbarServer::recordPhpError($severity, $message, $file, $line);

                return false;
            });
        } catch (\Throwable) {
            // The Debugbar must never take a page down; run without the extras.
        }

        return true;
    }

    public static function enabled(): bool
    {
        return self::$enabled === true;
    }

    /** Whether the DB layer should hand out timing driver subclasses this request. */
    public static function collectQueries(): bool
    {
        return self::$enabled === true;
    }

    public static function recordQuery(string $kind, string $sql, ?float $ms, ?string $error): void
    {
        if (self::$enabled !== true) {
            return;
        }

        self::$queryCount++;
        if ($ms !== null) {
            self::$queryMs += $ms;
        }

        if (count(self::$queries) >= self::$maxEntries) {
            self::$droppedQueries++;

            return;
        }

        try {
            $sql = trim($sql);
            if (!self::$verbose) {
                $sql = DebugRedactor::maskSqlLiterals($sql);
            }
            if (strlen($sql) > 2000) {
                $sql = substr($sql, 0, 2000) . '...';
            }

            self::$queries[] = [
                'kind' => $kind,
                'sql' => $sql,
                'ms' => $ms === null ? null : round($ms, 3),
                'error' => $error,
            ];
        } catch (\Throwable) {
            // Never let capture failures reach the caller's query path.
        }
    }

    public static function recordPhpError(int $severity, string $message, string $file, int $line): void
    {
        if (self::$enabled !== true) {
            return;
        }

        // XAMPP masks E_DEPRECATED and this tree has known pre-existing deprecations
        // (Database's dynamic properties fire several per instantiation) - without this
        // filter the Log panel is all noise. Verbose Trace shows them.
        if (!self::$verbose && in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
            return;
        }

        // Installing a handler makes PHP call it for diagnostics the app deliberately silenced,
        // and this tree silences a lot: `@$site_info['gtmID']`, `@$_SESSION['planner_id']`, the
        // `@new mysqli` probe, and head.php's blanket error_reporting(0) for the whole view
        // render. Honour the reporting mask so the panel shows real problems rather than the
        // noise the codebase already declared uninteresting.
        //
        // Note the two cases collapse: inside `@` the mask is PHP 8's fatal-only set, but once
        // error_reporting(0) is in force `@` yields 0 as well, so they cannot be told apart -
        // hence the mask test rather than a comparison against a fixed value. Everything
        // before head.php (bootstrap, controllers, models, the DB layer) still runs under the
        // php.ini mask and is captured normally; Verbose Trace captures the lot.
        if (!self::$verbose && !(error_reporting() & $severity)) {
            return;
        }

        if (count(self::$phpErrors) >= 100) {
            self::$droppedPhpErrors++;

            return;
        }

        self::$phpErrors[] = [
            'severity' => self::severityLabel($severity),
            'message' => $message,
            'file' => basename($file),
            'line' => $line,
        ];
    }

    private static function severityLabel(int $severity): string
    {
        return match ($severity) {
            E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR => 'error',
            E_WARNING, E_USER_WARNING => 'warning',
            E_NOTICE, E_USER_NOTICE => 'notice',
            E_DEPRECATED, E_USER_DEPRECATED => 'deprecated',
            default => 'error(' . $severity . ')',
        };
    }

    /**
     * Everything the client bar needs from the server, redacted and summarized.
     * Emitted only into full HTML pages (footer.php) - never into /checkout fragments,
     * /submit's plain-text response, or JSON.
     *
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        if (self::$enabled !== true) {
            return [];
        }

        try {
            $redactKeys = ConsoleSettings::debugRedactKeys();

            return [
                'config' => [
                    'verbose' => self::$verbose,
                    'maxEntries' => self::$maxEntries,
                    'redactKeys' => $redactKeys,
                ],
                'environment' => self::environment(),
                'database' => self::databaseSummary(),
                'session' => self::sessionSummary($redactKeys),
                'timing' => [
                    'phpMsToFooter' => round((microtime(true) - self::$startedAt) * 1000, 2),
                    'memoryPeakBytes' => memory_get_peak_usage(true),
                    'queryCount' => self::$queryCount,
                    'queryMs' => round(self::$queryMs, 2),
                    'droppedQueries' => self::$droppedQueries,
                ],
                'queries' => self::$queries,
                'phpErrors' => self::$phpErrors,
                'droppedPhpErrors' => self::$droppedPhpErrors,
            ];
        } catch (\Throwable $e) {
            return ['error' => 'debugbar payload failed: ' . $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function environment(): array
    {
        $appVersion = '';
        $appDebugLegacy = null;
        try {
            $app = AppConfigService::all()->app ?? null;
            $appVersion = (string) ($app->version ?? '');
            $appDebugLegacy = (bool) ($app->debug ?? false);
        } catch (\Throwable) {
        }

        $brandingVersion = '';
        try {
            $brandingVersion = (string) (BrandingSettings::get()['version'] ?? '');
        } catch (\Throwable) {
        }

        $table = '';
        $demoSegment = '';
        try {
            $db = new Database();
            $table = (string) $db->tableName('planners');
            $demoSegment = self::matchedDemoSegment();
        } catch (\Throwable) {
        }

        return [
            'phpVersion' => PHP_VERSION,
            'appVersion' => $appVersion,
            'brandingVersion' => $brandingVersion,
            'debugMode' => true,
            'appDebugLegacy' => $appDebugLegacy,
            'plannersTable' => $table,
            // Which URL path segment (if any) flipped writes onto the _demo table - the
            // boolean alone hides WHY a deployment is suddenly writing to wp_planners_demo.
            'demoSegment' => $demoSegment,
        ];
    }

    /**
     * Connection facts and the size of each wp_planners payload column as it stands in the
     * session. Deliberately derived from already-resolved state - no SELECT of its own, so
     * turning the Debugbar on does not add a query to every page load. Sizes rather than
     * values: these columns hold the customer's own name, address and phone.
     *
     * @return array<string, mixed>
     */
    private static function databaseSummary(): array
    {
        $out = [
            'driver' => [
                'mysqli' => extension_loaded('mysqli'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'clientVersion' => extension_loaded('mysqli') && function_exists('mysqli_get_client_info')
                    ? (string) mysqli_get_client_info()
                    : '',
            ],
            'table' => '',
            'prefix' => '',
            'demoSegment' => self::matchedDemoSegment(),
            'plannerId' => (string) ($_SESSION['planner_id'] ?? ''),
            'columns' => [],
        ];

        try {
            $db = new Database();
            $out['table'] = (string) $db->tableName('planners');
            $out['prefix'] = (string) ($db->prefix ?? '');
        } catch (\Throwable) {
        }

        // wp_planners payload columns and the session field each is written from
        // (PlannerSubmissionModel::payload()).
        $map = [
            'fence_data' => 'fences',
            'cart_items_data' => 'cart_items',
            'project_plans_data' => 'project_plans',
            'color_data' => 'color',
        ];
        $fcData = is_array($_SESSION['fc_data'] ?? null) ? $_SESSION['fc_data'] : [];
        foreach ($map as $column => $sessionKey) {
            $value = $fcData[$sessionKey] ?? null;
            $out['columns'][] = [
                'column' => $column,
                'source' => "fc_data['" . $sessionKey . "']",
                'bytes' => is_string($value) ? strlen($value) : (is_array($value) ? strlen((string) json_encode($value)) : 0),
                'present' => $value !== null && $value !== '',
            ];
        }

        foreach (['products_data' => 'custom_fence_products', 'cart_data' => 'fc_cart'] as $column => $sessionKey) {
            $value = $_SESSION[$sessionKey] ?? null;
            if ($column === 'cart_data' && is_array($value)) {
                $value = $value['items'] ?? null;
            }
            $out['columns'][] = [
                'column' => $column,
                'source' => "\$_SESSION['" . $sessionKey . "']",
                'bytes' => $value === null ? 0 : strlen((string) json_encode($value)),
                'present' => !empty($value),
            ];
        }

        return $out;
    }

    private static function matchedDemoSegment(): string
    {
        try {
            $stages = SiteRegistryService::demoStages();
            $segments = explode('/', strtolower((string) ($_SERVER['PHP_SELF'] ?? '')));
            foreach ($segments as $segment) {
                if ($segment !== '' && in_array($segment, $stages, true)) {
                    return $segment;
                }
            }
        } catch (\Throwable) {
        }

        return '';
    }

    /**
     * Session as it stood at render. Scalars go through the redactor; JSON fields are decoded
     * and redacted at every depth so the panel can show them, carrying their original byte
     * size alongside. This is the visitor's own session, and the redact keys still apply, so
     * decoding exposes nothing the caller did not already own - but a field over
     * SESSION_JSON_MAX_BYTES stays collapsed rather than inflating every page load.
     *
     * @param list<string> $redactKeys
     * @return array<string, mixed>
     */
    private static function sessionSummary(array $redactKeys): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return ['active' => false];
        }

        $out = ['active' => true, 'keys' => []];
        foreach (array_keys($_SESSION) as $key) {
            $out['keys'][] = (string) $key;
        }

        $fcData = $_SESSION['fc_data'] ?? null;
        if (is_array($fcData)) {
            $fields = [];
            foreach ($fcData as $key => $value) {
                if (is_string($value) && (str_starts_with(ltrim($value), '[') || str_starts_with(ltrim($value), '{'))) {
                    $fields[$key] = self::sessionJsonField($value);
                } elseif (is_scalar($value) || $value === null) {
                    $fields[$key] = $value;
                } else {
                    $fields[$key] = gettype($value);
                }
            }
            $out['fc_data'] = DebugRedactor::redact($fields, $redactKeys);
        }

        $out['planner_id'] = (string) ($_SESSION['planner_id'] ?? '');
        $cart = $_SESSION['fc_cart']['items'] ?? null;
        $out['fc_cart_lines'] = is_array($cart) ? count($cart) : 0;

        return $out;
    }

    /**
     * One fc_data field that looks like JSON. Returns the decoded value wrapped with its
     * original byte size when it can be shown, or the size-only string with the reason when
     * it cannot - the panel must never render a truncated blob as if it were the whole value.
     *
     * @return array{__fcJson:int,parsed:mixed}|string
     */
    private static function sessionJsonField(string $value): array|string
    {
        $bytes = strlen($value);

        if ($bytes > self::SESSION_JSON_MAX_BYTES) {
            return 'json(' . $bytes . ' bytes, too large to inline)';
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'json(' . $bytes . ' bytes, unparseable)';
        }

        return ['__fcJson' => $bytes, 'parsed' => $decoded];
    }

    /**
     * The JSON island footer.php emits into full-page HTML. Empty string when the bar
     * should not render, so the view stays a plain conditional echo.
     */
    public static function inlineJsonIsland(): string
    {
        if (!self::showDebugbar()) {
            return '';
        }

        try {
            $json = json_encode(
                self::payload(),
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR
            );

            return '<script type="application/json" id="fc-debugbar-server">' . $json . '</script>';
        } catch (\Throwable) {
            return '';
        }
    }

    /** Whether footer/head should emit Debugbar assets for this request. */
    public static function showDebugbar(): bool
    {
        if (self::$enabled !== true) {
            return false;
        }

        try {
            return ConsoleSettings::showDebugbar();
        } catch (\Throwable) {
            return false;
        }
    }
}
