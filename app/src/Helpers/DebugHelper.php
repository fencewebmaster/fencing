<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Dev dump-and-die utility (config/helpers.php migration).
 */
final class DebugHelper
{
    public static function dd(mixed $data = ''): void
    {
        echo '<pre>';
        print_r($data);
        exit;
    }
}
