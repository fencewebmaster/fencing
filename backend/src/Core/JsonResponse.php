<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

final class JsonResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public static function send(array $data, int $status = 200, array $headers = []): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            foreach ($headers as $name => $value) {
                header($name . ': ' . $value);
            }
            http_response_code($status);
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

  /**
     * @param array<string, mixed> $data
     */
    public static function ok(array $data, int $status = 200, array $headers = []): void
    {
        self::send($data, $status, $headers);
    }

    public static function error(string $message, int $status = 400): void
    {
        self::send(['ok' => false, 'error' => $message], $status);
    }
}
