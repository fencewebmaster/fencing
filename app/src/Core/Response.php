<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

final class Response
{
    public static function redirect(string $url, int $status = 302): void
    {
        header('Location: ' . $url, true, $status);
        exit;
    }
}
