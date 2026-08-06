<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

final class Request
{
    private string $method;

    private string $path;

    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed>|null */
    private ?array $jsonBody = null;

    public function __construct()
    {
        $this->method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $this->path   = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '/');
        $this->query  = $_GET;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function query(string $key, $default = '')
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    public function action(): string
    {
        return trim((string) $this->query('action', ''));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function jsonBody(): ?array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }

        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            $this->jsonBody = null;

            return null;
        }

        $decoded = json_decode($raw, true);
        $this->jsonBody = is_array($decoded) ? $decoded : null;

        return $this->jsonBody;
    }
}
