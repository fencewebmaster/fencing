<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Semantic version numbers (MAJOR.MINOR.PATCH) for the Version Manager.
 *
 * Versions are parsed into integers and compared numerically: as strings, "1.10.0"
 * sorts before "1.9.0" and a naive string bump turns "1.4.9" into "1.4.10" only by luck.
 * Pre-release and build suffixes (1.0.0-beta) are not accepted.
 */
final class SemverHelper
{
    /** Each part is a plain non-negative integer without leading zeros, as semver.org requires. */
    private const PATTERN = '/^(0|[1-9]\d{0,8})\.(0|[1-9]\d{0,8})\.(0|[1-9]\d{0,8})$/';

    public const LEVELS = ['major', 'minor', 'patch'];

    /**
     * "v1.4.0", " 1.4.0 " → "1.4.0"; null when it is not MAJOR.MINOR.PATCH.
     */
    public static function normalize(string $version): ?string
    {
        $version = trim($version);
        if ($version !== '' && ($version[0] === 'v' || $version[0] === 'V')) {
            $version = substr($version, 1);
        }

        return preg_match(self::PATTERN, $version) === 1 ? $version : null;
    }

    public static function isValid(string $version): bool
    {
        return self::normalize($version) !== null;
    }

    /**
     * @return array{major:int,minor:int,patch:int}|null
     */
    public static function parse(string $version): ?array
    {
        $normalized = self::normalize($version);
        if ($normalized === null) {
            return null;
        }

        [$major, $minor, $patch] = array_map('intval', explode('.', $normalized));

        return ['major' => $major, 'minor' => $minor, 'patch' => $patch];
    }

    /**
     * Numeric comparison: <0 when $a is lower, 0 when equal, >0 when higher. An invalid
     * version sorts below every valid one.
     */
    public static function compare(string $a, string $b): int
    {
        $pa = self::parse($a);
        $pb = self::parse($b);
        if ($pa === null || $pb === null) {
            return ($pa === null ? 0 : 1) - ($pb === null ? 0 : 1);
        }

        return [$pa['major'], $pa['minor'], $pa['patch']] <=> [$pb['major'], $pb['minor'], $pb['patch']];
    }

    /**
     * The next version at $level: 2.5.7 → patch 2.5.8, minor 2.6.0, major 3.0.0. An empty or
     * invalid $version counts as 0.0.0, so a type's first release is 0.0.1, 0.1.0 or 1.0.0.
     */
    public static function bump(string $version, string $level): string
    {
        $parts = self::parse($version) ?? ['major' => 0, 'minor' => 0, 'patch' => 0];

        switch ($level) {
            case 'major':
                return ($parts['major'] + 1) . '.0.0';
            case 'minor':
                return $parts['major'] . '.' . ($parts['minor'] + 1) . '.0';
            default:
                return $parts['major'] . '.' . $parts['minor'] . '.' . ($parts['patch'] + 1);
        }
    }
}
