<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Settings "Dev Console" tab — git-command tokenizer/allowlist/process-runner.
 * Argv-only proc_open (no shell); mutating git subcommands require an explicit CONFIRM.
 */
final class DevConsoleService
{
    /**
     * Dev Mode console: builtins + any git command (argv only, no shell).
     *
     * @param array<string, mixed> $payload
     * @return array{ok:bool,output:string,error?:string,forbidden?:bool,exitCode?:int,clear?:bool}
     */
    public static function runCommand(string $command, string $root, array $payload): array
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
                    '  git <args>           An allow-listed git command in the project root',
                    '',
                    'Mutating git commands (pull, push, merge, reset, …) require CONFIRM.',
                    'Shell operators, directory overrides (-C, --git-dir), config/exec-path',
                    'overrides (-c, --exec-path, --upload-pack, --receive-pack), and output',
                    'redirection (-o, --output, --output-directory) are blocked.',
                    '"git config", "git filter-branch", and "git worktree" are not permitted',
                    'from this console.',
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

        $argv = self::tokenize($trimmed);
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
        if (self::argvEscapesRoot($argv)) {
            return [
                'ok' => false,
                'forbidden' => true,
                'error' => 'Command not allowed.',
                'output' => 'git -C / --git-dir / --work-tree overrides are blocked.',
            ];
        }

        if (self::argvHasDangerousOption($argv)) {
            return [
                'ok' => false,
                'forbidden' => true,
                'error' => 'Command not allowed.',
                'output' => 'git -c / --exec-path / --upload-pack / --receive-pack / -o / --output(-directory) '
                    . 'overrides are blocked (git config values and pack programs can run arbitrary commands '
                    . 'via a shell; output redirection can write an arbitrary file to disk).',
            ];
        }

        if (self::argvHasUnsafeUrlArgument($argv)) {
            return [
                'ok' => false,
                'forbidden' => true,
                'error' => 'Command not allowed.',
                'output' => 'Raw URLs / transport-helper syntax (file://, ext::, etc.) are blocked. '
                    . 'fetch/pull/push/ls-remote may only target the repository\'s already-configured remote.',
            ];
        }

        $subcommand = self::resolveSubcommand($argv);
        if (!self::subcommandAllowed($subcommand)) {
            return [
                'ok' => false,
                'forbidden' => true,
                'error' => 'Command not allowed.',
                'output' => 'git "' . $subcommand . '" is not on the allowed command list. Type "help".',
            ];
        }

        if (!is_dir($root . DIRECTORY_SEPARATOR . '.git')) {
            return [
                'ok' => false,
                'error' => 'This install is not a git repository.',
                'output' => '',
            ];
        }

        if (self::argvNeedsConfirm($argv)
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
    private static function tokenize(string $command): ?array
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
    private static function argvEscapesRoot(array $argv): bool
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
     * git flags that let a "safe-looking" subcommand run an arbitrary external program or
     * shell command — the actual root cause behind `git -c alias.x='!<shell>' x` and
     * `git config alias.x '!<shell>'; git x`. Rejected outright, at any argv position;
     * unlike the -C/--git-dir/--work-tree check above these are never legitimately needed
     * from this console, so there is no "confirm and proceed" path for them.
     *
     * @param list<string> $argv
     */
    private static function argvHasDangerousOption(array $argv): bool
    {
        // -o/--output(-directory) let "read-only" commands like log/diff/show write an
        // attacker-chosen file anywhere on disk instead of returning output — a one-shot
        // arbitrary-file-write (e.g. dropping a webshell) that never touches the confirm
        // gate, since those subcommands are in the "never mutate" bucket.
        $dangerousExact = [
            '-c', '--exec-path', '--upload-pack', '--receive-pack', '--config-env',
            '-o', '--output', '--output-directory',
        ];
        $dangerousPrefixes = [
            '--exec-path=', '--upload-pack=', '--receive-pack=', '--config-env=',
            '--output=', '--output-directory=',
        ];

        foreach ($argv as $arg) {
            if (in_array($arg, $dangerousExact, true)) {
                return true;
            }
            foreach ($dangerousPrefixes as $prefix) {
                if (str_starts_with($arg, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Reject any argument that looks like a URL or a git transport-helper reference
     * (file://, ext::, ssh://, http(s)://, a bare "::" token, …). `fetch`/`pull`/`push`/
     * `ls-remote`/`remote` never need one from this console — normal usage targets the
     * repository's already-configured remote by name (e.g. "git pull", "git fetch origin"),
     * which never puts a URL in argv at all. Without this, `git ls-remote file://<other-repo>`
     * reads any other git repository on disk with zero confirmation, and `git remote
     * set-url origin <url>` (or fetch/push with a raw URL) can point the repo at an
     * arbitrary host — a one-shot SSRF primitive, or worse via the `ext::` transport.
     *
     * @param list<string> $argv
     */
    private static function argvHasUnsafeUrlArgument(array $argv): bool
    {
        $count = count($argv);
        for ($i = 1; $i < $count; $i++) {
            if (str_contains($argv[$i], '://') || str_contains($argv[$i], '::')) {
                return true;
            }
        }

        return false;
    }

    /**
     * The git subcommand argv[0]=='git' resolves to, skipping any leading global flags.
     * (Dangerous flags like -c are already rejected by argvHasDangerousOption() before this
     * runs, so the only flags that can still precede the subcommand here are harmless ones
     * like -p/--paginate/--no-pager.)
     *
     * @param list<string> $argv
     */
    private static function resolveSubcommand(array $argv): string
    {
        $i = 1;
        $count = count($argv);
        while ($i < $count && str_starts_with($argv[$i], '-')) {
            $i++;
        }

        return strtolower((string) ($argv[$i] ?? ''));
    }

    /**
     * Default-deny allowlist of git subcommands this console may run at all. Notably
     * excludes `config` (the alias-injection vector), `filter-branch` (whose
     * --tree-filter/--index-filter arguments are themselves an arbitrary shell command by
     * design — the same root cause as the alias bypass, just via a different subcommand),
     * and `worktree` (its destination path is a plain positional argument that neither
     * argvEscapesRoot() nor argvHasDangerousOption() inspects, so `worktree add <path>`
     * would otherwise write the full tracked tree to any filesystem path the process can
     * reach — the same "arbitrary write" root cause as the already-blocked -o/--output),
     * along with anything else not explicitly recognized (custom aliases included).
     */
    private static function subcommandAllowed(string $subcommand): bool
    {
        $allowed = [
            // Read-only / inspection — never mutate the repo.
            'status', 'log', 'diff', 'show', 'describe', 'rev-parse', 'shortlog',
            'blame', 'ls-files', 'ls-remote', 'reflog', 'fsck', 'grep', 'cat-file',
            'count-objects', 'show-branch', 'name-rev', 'whatchanged',
            // Mutating — allowed, but still gated by argvNeedsConfirm() below.
            'pull', 'push', 'fetch', 'merge', 'rebase', 'reset', 'clean',
            'commit', 'checkout', 'switch', 'am', 'cherry-pick', 'revert',
            'gc', 'prune', 'replace', 'submodule',
            'stash', 'branch', 'tag', 'remote',
        ];

        return in_array($subcommand, $allowed, true);
    }

    /**
     * @param list<string> $argv
     */
    private static function argvNeedsConfirm(array $argv): bool
    {
        $i = 1;
        $count = count($argv);
        while ($i < $count && str_starts_with($argv[$i], '-')) {
            $i++;
        }

        $sub = strtolower((string) ($argv[$i] ?? ''));
        $always = [
            'pull', 'push', 'fetch', 'merge', 'rebase', 'reset', 'clean',
            'commit', 'checkout', 'switch', 'am', 'cherry-pick', 'revert',
            'gc', 'prune', 'replace', 'submodule',
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
        $output = trim(implode("\n", array_filter([$stdout, $stderr], static fn (string $chunk): bool => $chunk !== '')));

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
    public static function pull(string $root): array
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
        $output = trim(implode("\n", array_filter([$stdout, $stderr], static fn (string $chunk): bool => $chunk !== '')));

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
}
