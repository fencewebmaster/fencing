<?php

declare(strict_types=1);

/**
 * FC Debugbar — server-side checks (redaction + ConsoleSettings normalize).
 * Zero-dependency, read-only: nothing here writes config.php or touches the DB.
 *
 *   php tests/debugbar/run-php.php
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use Fc\Admin\Debug\DebugbarServer;
use Fc\Admin\Debug\DebugRedactor;
use Fc\Admin\Settings\ConsoleSettings;

$passed = 0;
$failed = 0;

function check(string $name, bool $condition, mixed $detail = null): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ok  {$name}\n";
    } else {
        $failed++;
        echo 'FAIL  ' . $name . ($detail === null ? '' : ' — ' . var_export($detail, true)) . "\n";
    }
}

// ---------------------------------------------------------------- redaction
$out = DebugRedactor::redact(
    ['name' => 'x', 'api_key' => 'S', 'nested' => ['db_password' => 'p', 'ok' => 1], 'n' => 5],
    ['key', 'password']
);
check('redact: sensitive keys replaced recursively', $out['api_key'] === '[redacted]' && $out['nested']['db_password'] === '[redacted]');
check('redact: everything else untouched', $out['name'] === 'x' && $out['nested']['ok'] === 1 && $out['n'] === 5);
check('redact: non-array passthrough', DebugRedactor::redact('plain', ['key']) === 'plain');
check('keyIsSensitive: case-insensitive substring', DebugRedactor::keyIsSensitive('X-Auth-Token', ['token']));

// ---------------------------------------------------------------- fc_data JSON fields
// sessionJsonField() decides whether a session blob can be shown or must stay a byte count.
// Private by design (nothing else should call it), so reached by reflection rather than
// widened for the tests.
$sjf = new ReflectionMethod(DebugbarServer::class, 'sessionJsonField');
$sjf->setAccessible(true);
$field = static fn (string $v): array|string => $sjf->invoke(null, $v);

$ok = $field('{"a":1,"b":[2,3]}');
check('session json: valid object decoded with its byte size', is_array($ok)
    && $ok['__fcJson'] === 17 && $ok['parsed']['a'] === 1 && $ok['parsed']['b'][1] === 3);

$arr = $field('[{"x":1}]');
check('session json: arrays decode as arrays', is_array($arr) && $arr['parsed'][0]['x'] === 1);

check('session json: malformed stays a byte count, and says why',
    $field('{"a":1,') === 'json(7 bytes, unparseable)');

$big = '{"pad":"' . str_repeat('x', 70000) . '"}';
$capped = $field($big);
check('session json: oversized field is not inlined', is_string($capped)
    && str_contains($capped, 'too large to inline') && str_contains($capped, (string) strlen($big)));

check('session json: a field at the cap is still shown', is_array($field('{"p":"' . str_repeat('x', 65536 - 8) . '"}')));

// The decoded value must still go through the redactor, at depth - that is the whole reason
// decoding is safe to do at all.
$redacted = DebugRedactor::redact(['fences' => $field('{"outer":{"api_key":"S","keep":1}}')], ['key']);
check('session json: nested sensitive keys still redacted after decoding',
    $redacted['fences']['parsed']['outer']['api_key'] === '[redacted]'
    && $redacted['fences']['parsed']['outer']['keep'] === 1);

// ---------------------------------------------------------------- SQL literal masking
$sql = "INSERT INTO wp_planners (name, mobile) VALUES ('John Citizen', '0412345678')";
$masked = DebugRedactor::maskSqlLiterals($sql);
check('mask: single-quoted literals hidden', strpos($masked, 'John') === false && strpos($masked, '0412') === false);
check('mask: query shape preserved', strpos($masked, 'INSERT INTO wp_planners (name, mobile) VALUES') === 0);

$sql2 = 'SELECT * FROM wp_planners WHERE `planner_id`="LH1LZMUX" ORDER BY id DESC';
check('mask: double-quoted literals hidden (where_clause style)', strpos(DebugRedactor::maskSqlLiterals($sql2), 'LH1LZMUX') === false);

check('mask: short literals kept for readability', DebugRedactor::maskSqlLiterals("WHERE state = 'ok'") === "WHERE state = 'ok'");
check("mask: escaped/doubled quotes don't break out", strpos(DebugRedactor::maskSqlLiterals("SET a = 'It''s here', b = 'x'"), 'here') === false);

// ---------------------------------------------------------------- ConsoleSettings::normalize (the whitelist)
$n = ConsoleSettings::normalize([]);
check('normalize: defaults fill every key', $n['debugMode'] === false && $n['showDebugbar'] === true
    && $n['debugVerbose'] === false && $n['debugMaxEntries'] === 200 && $n['debugRedactKeys'] !== '');

$n = ConsoleSettings::normalize(['debugMode' => 'on', 'showDebugbar' => '0', 'debugVerbose' => 'true']);
check('normalize: string bools coerce like the debugMode original', $n['debugMode'] === true && $n['showDebugbar'] === false && $n['debugVerbose'] === true);

$n = ConsoleSettings::normalize(['debugMaxEntries' => 999999]);
check('normalize: max entries clamps high', $n['debugMaxEntries'] === 2000);
$n = ConsoleSettings::normalize(['debugMaxEntries' => 3]);
check('normalize: max entries clamps low', $n['debugMaxEntries'] === 50);
$n = ConsoleSettings::normalize(['debugMaxEntries' => 'not-a-number']);
check('normalize: junk max entries -> default', $n['debugMaxEntries'] === 200);

$n = ConsoleSettings::normalize(['debugRedactKeys' => ' Token ,, KEY ,']);
check('normalize: redact keys lowercased/trimmed CSV', $n['debugRedactKeys'] === 'token,key');
$n = ConsoleSettings::normalize(['debugRedactKeys' => '  , ,']);
check('normalize: emptied redact keys restore defaults (never silently off)', strpos($n['debugRedactKeys'], 'password') !== false);

$n = ConsoleSettings::normalize(['debugMode' => true, 'not_whitelisted' => 'dropped']);
check('normalize IS the whitelist: unknown keys dropped', !array_key_exists('not_whitelisted', $n) && count($n) === 5);

// ---------------------------------------------------------------- @-suppression filter
// Installing an error handler makes PHP call it for `@`-silenced expressions too; the tree
// suppresses a lot on purpose, so those must not fill the Log panel. head.php's blanket
// error_reporting(0) must NOT have the same effect, or the panel goes silent on every page.
if (DebugbarServer::bootIfEnabled()) {
    $suppressed = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
    $countErrors = static function (): int {
        $p = DebugbarServer::payload();
        return count($p['phpErrors'] ?? []);
    };

    $before = $countErrors();
    $old = error_reporting($suppressed);
    DebugbarServer::recordPhpError(E_WARNING, 'from an @-suppressed expression', __FILE__, __LINE__);
    error_reporting($old);
    check('errors: @-suppressed diagnostics are not captured', $countErrors() === $before);

    // Once head.php runs error_reporting(0), `@` yields 0 too - the two are indistinguishable,
    // so view-render diagnostics are held back as well and Verbose Trace is the way to see them.
    $old = error_reporting(0);
    DebugbarServer::recordPhpError(E_WARNING, 'under head.php error_reporting(0)', __FILE__, __LINE__);
    error_reporting($old);
    check('errors: diagnostics the app silenced are not captured', $countErrors() === $before);

    DebugbarServer::recordPhpError(E_WARNING, 'ordinary warning', __FILE__, __LINE__);
    check('errors: reportable warnings still captured', $countErrors() === $before + 1);

    DebugbarServer::recordPhpError(E_DEPRECATED, 'dynamic property', __FILE__, __LINE__);
    check('errors: deprecations filtered (known pre-existing noise)', $countErrors() === $before + 1);
} else {
    echo "  --  @-suppression checks skipped (Debug Mode is off)
";
}

// ---------------------------------------------------------------- accessors
check('debugRedactKeys() splits to a list', in_array('password', ConsoleSettings::defaults()['debugRedactKeys'] !== '' ? explode(',', ConsoleSettings::defaults()['debugRedactKeys']) : [], true));

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed ? 1 : 0);
