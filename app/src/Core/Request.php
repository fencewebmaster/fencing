<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

final class Request
{
    private string $method;

    private string $path;

    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed> */
    private array $post;

    /** @var array<string, mixed>|null */
    private ?array $jsonBody = null;

    public function __construct()
    {
        $this->method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $this->path   = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '/');
        $this->query  = $_GET;
        $this->post   = $_POST;
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

    /**
     * Set/override a query value — for routes that resolve a path segment into a
     * query-shaped param (e.g. a pretty URL rewritten to ?view=slug) before a
     * controller reads it via query()/allQuery().
     *
     * @param mixed $value
     */
    public function setQuery(string $key, $value): void
    {
        $this->query[$key] = $value;
    }

    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function allPost(): array
    {
        return $this->post;
    }

    /**
     * Whether $key is present in the query string (regardless of value) — for code
     * that branches on presence itself rather than fetching a value.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->query);
    }

    /**
     * Read $key from the query string first, falling back to POST data. Matches this
     * app's one existing GET-over-POST fallback convention — use query()/post()
     * directly instead where a call site's source must stay pinned to one of the two.
     *
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $this->query[$key] ?? $this->post[$key] ?? $default;
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
