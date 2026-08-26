<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Generic display-formatting utilities.
 */
final class FormatHelper
{
    /**
     * Human-readable byte size (e.g. 1MB, 512KB).
     */
    public static function bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . 'B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $unit = 'KB';
        foreach ($units as $candidate) {
            $value /= 1024;
            $unit = $candidate;
            if ($value < 1024) {
                break;
            }
        }

        if ($value >= 100 || abs($value - round($value)) < 0.05) {
            return (string) (int) round($value) . $unit;
        }

        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . $unit;
    }

    /**
     * HTML-escaped currency amount (e.g. $1,234.56).
     */
    public static function money(float $amount, string $currencySymbol = '$'): string
    {
        $formatted = number_format($amount, 2, '.', ',');

        return htmlspecialchars($currencySymbol . $formatted, ENT_QUOTES, 'UTF-8');
    }
}
