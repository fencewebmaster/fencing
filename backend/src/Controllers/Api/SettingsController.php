<?php
/**
 * FC Admin — settings API (theme colors + branding).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

final class SettingsController
{
    public static function dispatch(): void
    {
        require_once FC_ROOT . '/config/theme.php';
        require_once FC_ROOT . '/config/branding.php';
        require_once FC_ROOT . '/config/fence-colors.php';
        require_once FC_ROOT . '/config/catalog.php';
        require_once FC_ROOT . '/config/system.php';
        require_once FC_ROOT . '/config/integrations.php';

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : '';

        if ($action === 'theme') {
            self::handleTheme($method);
            return;
        }

        if ($action === 'branding') {
            self::handleBranding($method);
            return;
        }

        if ($action === 'fence-colors') {
            self::handleFenceColors($method);
            return;
        }

        if ($action === 'catalog') {
            self::handleCatalog($method);
            return;
        }

        if ($action === 'system') {
            self::handleSystem($method);
            return;
        }

        if ($action === 'integrations') {
            self::handleIntegrations($method);
            return;
        }

        if ($action === 'cloudflare-verify') {
            self::handleCloudflareVerify($method);
            return;
        }

        if ($action === 'git-pull') {
            self::handleGitPull($method);
            return;
        }

        if ($action === 'dev-console') {
            self::handleDevConsole($method);
            return;
        }

        http_response_code($action === '' ? 400 : 405);
        echo json_encode([
            'ok' => false,
            'error' => $action === '' ? 'Unknown action.' : 'Method not allowed.',
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function handleTheme(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_theme_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['colors']) || !is_array($payload['colors'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "colors": { "--fc-princeton-orange": "#f67925" } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_theme_save($payload['colors']);
            if (!$result['ok']) {
                http_response_code(500);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_theme_api_payload();
            $response['message'] = 'Theme saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleBranding(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_branding_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['branding']) || !is_array($payload['branding'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "branding": { "appName": "Fencing Calculator" } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_branding_save($payload['branding']);
            if (!$result['ok']) {
                http_response_code(500);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_branding_api_payload();
            $response['message'] = 'Branding saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleFenceColors(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_fence_colors_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['fenceColors']) || !is_array($payload['fenceColors'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "fenceColors": [ { "slug": "black", "label": "Black · Satin", "color": "#404040", "image": "" } ] }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_fence_colors_save($payload['fenceColors']);
            if (!$result['ok']) {
                http_response_code(500);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_fence_colors_api_payload();
            $response['message'] = 'Fence colors saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleCatalog(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_catalog_api_payload(true), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['catalog']) || !is_array($payload['catalog'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "catalog": { ... } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_catalog_save($payload['catalog']);
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_catalog_api_payload(true);
            $response['message'] = 'Catalog settings saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleSystem(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_system_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['system']) || !is_array($payload['system'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "system": { ... } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_system_save($payload['system']);
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_system_api_payload();
            $response['message'] = 'System settings saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleIntegrations(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_integrations_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['integrations']) || !is_array($payload['integrations'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "integrations": { ... } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!function_exists('fc_auth_verify_csrf') || !fc_auth_verify_csrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_integrations_save(
                $payload['integrations'],
                isset($payload['revision']) ? (string) $payload['revision'] : ''
            );
            if (empty($result['ok'])) {
                http_response_code(!empty($result['conflict']) ? 409 : 400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $result['message'] = 'Integration settings saved.';
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleCloudflareVerify(string $method): void
    {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = self::jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        if (!function_exists('fc_auth_verify_csrf') || !fc_auth_verify_csrf(
            isset($payload['csrf']) ? (string) $payload['csrf'] : null
        )) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        require_once FC_ROOT . '/config/cloudflare.php';

        $token = trim((string) ($payload['cloudflareApiToken'] ?? ''));
        if ($token === '') {
            $saved = fc_integrations_get();
            $token = trim((string) ($saved['cloudflareApiToken'] ?? ''));
        }

        $zoneId = trim((string) ($payload['cloudflareZoneId'] ?? ''));
        $siteKey = trim((string) ($payload['siteKey'] ?? ''));

        $result = fc_cloudflare_verify_zone($token, $zoneId);
        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => (string) ($result['error'] ?? 'Cloudflare zone check failed.'),
                'siteKey' => $siteKey,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'ok' => true,
            'zoneName' => (string) ($result['zoneName'] ?? ''),
            'status' => (string) ($result['status'] ?? ''),
            'siteKey' => $siteKey,
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function handleGitPull(string $method): void
    {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = self::jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        if (!function_exists('fc_auth_verify_csrf') || !fc_auth_verify_csrf(
            isset($payload['csrf']) ? (string) $payload['csrf'] : null
        )) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (trim((string) ($payload['confirm'] ?? '')) !== 'CONFIRM') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Type CONFIRM to pull updates.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $root = defined('FC_ROOT') ? (string) FC_ROOT : '';
        if ($root === '' || !is_dir($root)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Project root is not available.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!is_dir($root . DIRECTORY_SEPARATOR . '.git')) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'This install is not a git repository.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = self::runGitPull($root);
        if (empty($result['ok'])) {
            http_response_code(500);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    private static function handleDevConsole(string $method): void
    {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = self::jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        if (!function_exists('fc_auth_verify_csrf') || !fc_auth_verify_csrf(
            isset($payload['csrf']) ? (string) $payload['csrf'] : null
        )) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $command = trim((string) ($payload['command'] ?? ''));
        if ($command === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Enter a command.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $root = defined('FC_ROOT') ? (string) FC_ROOT : '';
        if ($root === '' || !is_dir($root)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Project root is not available.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = self::runDevConsoleCommand($command, $root, $payload);
        if (empty($result['ok'])) {
            http_response_code(!empty($result['forbidden']) ? 403 : 400);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Dev Mode console: builtins + any git command (argv only, no shell).
     *
     * @param array<string, mixed> $payload
     * @return array{ok:bool,output:string,error?:string,forbidden?:bool,exitCode?:int,clear?:bool}
     */
    private static function runDevConsoleCommand(string $command, string $root, array $payload): array
    {
        $trimmed = trim($command);
        $normalized = strtolower(preg_replace('/\s+/', ' ', $trimmed) ?? '');

        if ($normalized === 'help' || $normalized === '?') {
            return [
                'ok' => true,
                'output' => implode("\n", [
                    'Commands:',
                    '  help                 Show this help',
                    '  clear                Clear the console (client-side)',
                    '  pwd                  Show project root',
                    '  git <args>           Any git command in the project root',
                    '',
                    'Mutating git commands (pull, push, merge, reset, …) require CONFIRM.',
                    'Shell operators and directory overrides (-C, --git-dir) are blocked.',
                ]),
            ];
        }

        if ($normalized === 'pwd') {
            return [
                'ok' => true,
                'output' => $root,
            ];
        }

        if ($normalized === 'clear') {
            return [
                'ok' => true,
                'output' => '',
                'clear' => true,
            ];
        }

        $argv = self::tokenizeConsoleCommand($trimmed);
        if ($argv === null || $argv === []) {
            return [
                'ok' => false,
                'forbidden' => true,
                'error' => 'Invalid command.',
                'output' => 'Could not parse command. Avoid shell operators (;|&`$()<>).',
            ];
        }

        if (strtolower($argv[0]) !== 'git') {
            return [
                'ok' => false,
                'forbidden' => true,
                'error' => 'Command not allowed.',
                'output' => 'Only "git …", help, clear, and pwd are allowed. Type "help".',
            ];
        }

        $argv[0] = 'git';
        if (self::gitArgvEscapesRoot($argv)) {
            return [
                'ok' => false,
                'forbidden' => true,
                'error' => 'Command not allowed.',
                'output' => 'git -C / --git-dir / --work-tree overrides are blocked.',
            ];
        }

        if (!is_dir($root . DIRECTORY_SEPARATOR . '.git')) {
            return [
                'ok' => false,
                'error' => 'This install is not a git repository.',
                'output' => '',
            ];
        }

        if (self::gitArgvNeedsConfirm($argv)
            && trim((string) ($payload['confirm'] ?? '')) !== 'CONFIRM'
        ) {
            return [
                'ok' => false,
                'error' => 'This git command requires confirmation.',
                'output' => 'Confirm in the dialog (type CONFIRM) and run again.',
            ];
        }

        return self::runAllowlistedProcess($argv, $root);
    }

    /**
     * @return list<string>|null
     */
    private static function tokenizeConsoleCommand(string $command): ?array
    {
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F;|&`$(){}<>\\\\]/', $command)) {
            return null;
        }

        $tokens = [];
        $length = strlen($command);
        $current = '';
        $quote = null;

        for ($i = 0; $i < $length; $i++) {
            $ch = $command[$i];
            if ($quote !== null) {
                if ($ch === $quote) {
                    $quote = null;
                } else {
                    $current .= $ch;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                continue;
            }
            if ($ch === ' ' || $ch === "\t") {
                if ($current !== '') {
                    $tokens[] = $current;
                    $current = '';
                }
                continue;
            }
            $current .= $ch;
        }

        if ($quote !== null) {
            return null;
        }
        if ($current !== '') {
            $tokens[] = $current;
        }

        return $tokens;
    }

    /**
     * @param list<string> $argv
     */
    private static function gitArgvEscapesRoot(array $argv): bool
    {
        $count = count($argv);
        for ($i = 1; $i < $count; $i++) {
            $arg = $argv[$i];
            if ($arg === '-C' || $arg === '--git-dir' || $arg === '--work-tree') {
                return true;
            }
            if (str_starts_with($arg, '--git-dir=') || str_starts_with($arg, '--work-tree=')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $argv
     */
    private static function gitArgvNeedsConfirm(array $argv): bool
    {
        $i = 1;
        $count = count($argv);
        while ($i < $count && str_starts_with($argv[$i], '-')) {
            $opt = $argv[$i];
            if ($opt === '-c') {
                $i += 2;
                continue;
            }
            $i++;
        }

        $sub = strtolower((string) ($argv[$i] ?? ''));
        $always = [
            'pull', 'push', 'fetch', 'merge', 'rebase', 'reset', 'clean',
            'commit', 'checkout', 'switch', 'am', 'cherry-pick', 'revert',
            'gc', 'prune', 'filter-branch', 'replace', 'submodule', 'worktree',
        ];
        if (in_array($sub, $always, true)) {
            return true;
        }

        $rest = array_slice($argv, $i + 1);
        $restLower = array_map('strtolower', $rest);

        if ($sub === 'stash') {
            $action = $restLower[0] ?? 'push';
            return !in_array($action, ['list', 'show'], true);
        }

        if ($sub === 'branch') {
            foreach ($rest as $arg) {
                if ($arg === '-d' || $arg === '-D' || $arg === '--delete'
                    || $arg === '-m' || $arg === '-M' || $arg === '--move'
                    || $arg === '-c' || $arg === '-C' || $arg === '--copy'
                ) {
                    return true;
                }
            }

            return false;
        }

        if ($sub === 'tag') {
            foreach ($rest as $arg) {
                if ($arg === '-d' || $arg === '--delete' || $arg === '-f' || $arg === '--force') {
                    return true;
                }
            }

            return isset($rest[0]) && !str_starts_with((string) $rest[0], '-');
        }

        if ($sub === 'remote') {
            $action = $restLower[0] ?? '';

            return in_array($action, ['add', 'remove', 'rm', 'rename', 'set-url', 'prune'], true);
        }

        return false;
    }

    /**
     * @param list<string> $argv
     * @return array{ok:bool,output:string,error?:string,exitCode?:int}
     */
    private static function runAllowlistedProcess(array $argv, string $root): array
    {
        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = null;
        $pipes = [];

        if (PHP_VERSION_ID >= 70400) {
            $process = @proc_open($argv, $descriptor, $pipes, $root);
        }

        if (!is_resource($process)) {
            $escaped = array_map('escapeshellarg', $argv);
            $process = @proc_open(implode(' ', $escaped), $descriptor, $pipes, $root);
        }

        if (!is_resource($process)) {
            return [
                'ok' => false,
                'error' => 'Could not start command.',
                'output' => '',
            ];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], true);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $stdout = is_string($stdout) ? trim($stdout) : '';
        $stderr = is_string($stderr) ? trim($stderr) : '';
        $output = trim(implode("\n", array_filter([$stdout, $stderr], static fn(string $chunk): bool => $chunk !== '')));

        if ($exitCode !== 0) {
            return [
                'ok' => false,
                'error' => 'Command failed.',
                'output' => $output !== '' ? $output : 'Exit code ' . $exitCode . '.',
                'exitCode' => $exitCode,
            ];
        }

        return [
            'ok' => true,
            'output' => $output !== '' ? $output : '(no output)',
            'exitCode' => $exitCode,
        ];
    }

    /**
     * @return array{ok:bool,output:string,message?:string,error?:string,exitCode?:int}
     */
    private static function runGitPull(string $root): array
    {
        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = null;
        $pipes = [];

        // Prefer argv form so the shell is not required for argument parsing.
        if (PHP_VERSION_ID >= 70400) {
            $process = @proc_open(['git', 'pull'], $descriptor, $pipes, $root);
        }

        if (!is_resource($process)) {
            $process = @proc_open('git pull', $descriptor, $pipes, $root);
        }

        if (!is_resource($process)) {
            return [
                'ok' => false,
                'error' => 'Could not start git pull.',
                'output' => '',
            ];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], true);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $stdout = is_string($stdout) ? trim($stdout) : '';
        $stderr = is_string($stderr) ? trim($stderr) : '';
        $output = trim(implode("\n", array_filter([$stdout, $stderr], static fn(string $chunk): bool => $chunk !== '')));

        if ($exitCode !== 0) {
            return [
                'ok' => false,
                'error' => 'git pull failed.',
                'output' => $output !== '' ? $output : 'git pull exited with code ' . $exitCode . '.',
                'exitCode' => $exitCode,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Updates pulled successfully.',
            'output' => $output !== '' ? $output : 'Already up to date.',
            'exitCode' => $exitCode,
        ];
    }

    /** @return mixed */
    private static function jsonBody()
    {
        $raw = file_get_contents('php://input');
        return is_string($raw) ? json_decode($raw, true) : null;
    }
}
