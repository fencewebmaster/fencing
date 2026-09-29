<?php
/**
 * FC — UTF-8 stand-ins for the four mbstring functions FC calls, defined only when the
 * extension is missing.
 *
 * Loaded by app/bootstrap.php. On 29 Sep 2026 the live PHP briefly ran without mbstring and
 * the first mb_strlen() (RequestHelper, in every admin request's startup) took the whole admin
 * down, the planner's settings with it. With these, both keep working, and Site Health, which
 * lists mbstring as required, can still load to report it missing. Text is assumed to be UTF-8,
 * as it is everywhere in FC.
 */

declare(strict_types=1);

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        $count = preg_match_all('/./su', $string);

        return $count === false ? strlen($string) : $count;
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return $length === null ? substr($string, $start) : substr($string, $start, $length);
        }

        return implode('', array_slice($chars, $start, $length));
    }
}

if (!function_exists('mb_strtolower')) {
    // Only ASCII letters change case here; FC's one caller groups store names by it.
    function mb_strtolower(string $string, ?string $encoding = null): string
    {
        return strtolower($string);
    }
}

if (!function_exists('mb_strrpos')) {
    function mb_strrpos(string $haystack, string $needle, int $offset = 0, ?string $encoding = null): int|false
    {
        // The offset counts characters; strrpos() counts bytes.
        $byteOffset = $offset >= 0
            ? strlen(mb_substr($haystack, 0, $offset))
            : -strlen(mb_substr($haystack, $offset));
        $bytes = strrpos($haystack, $needle, $byteOffset);

        return $bytes === false ? false : mb_strlen(substr($haystack, 0, $bytes));
    }
}
