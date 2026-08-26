<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

final class Router
{
    /** @var array<int, array{method:string,pattern:string,handler:callable}> */
    private array $routes = [];

    /** URL prefix applied to every pattern registered while inside a group() call. */
    private string $groupPrefix = '';

    public function get(string $pattern, callable $handler): self
    {
        return $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): self
    {
        return $this->add('POST', $pattern, $handler);
    }

    public function any(string $pattern, callable $handler): self
    {
        return $this->add('*', $pattern, $handler);
    }

    /**
     * Register routes under a shared URL prefix.
     *
     * Only the pattern is affected: auth and permission filters stay with the
     * dispatchers (Core\Application / Core\FrontendApplication), which run them from
     * the resolved route tail, so a group carries no middleware/namespace options.
     * Groups nest; an empty pattern inside a group registers the bare prefix itself.
     */
    public function group(string $prefix, callable $routes): self
    {
        $previous          = $this->groupPrefix;
        $this->groupPrefix = trim($previous . '/' . trim($prefix, '/'), '/');

        $routes($this);

        $this->groupPrefix = $previous;

        return $this;
    }

    private function add(string $method, string $pattern, callable $handler): self
    {
        $pattern = trim($pattern, '/');
        if ($this->groupPrefix !== '') {
            $pattern = $pattern === '' ? $this->groupPrefix : $this->groupPrefix . '/' . $pattern;
        }

        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];

        return $this;
    }

    /**
     * @return array{handler:callable,params:array<string,string>}|null
     */
    public function match(string $method, string $path): ?array
    {
        $path = trim($path, '/');
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPattern($route['pattern'], $path);
            if ($params !== null) {
                return [
                    'handler' => $route['handler'],
                    'params'  => $params,
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>|null
     */
    private function matchPattern(string $pattern, string $path): ?array
    {
        if ($pattern === '' && $path === '') {
            return [];
        }

        if ($pattern === $path) {
            return [];
        }

        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        if (!is_string($regex)) {
            return null;
        }

        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = (string) $value;
            }
        }

        return $params;
    }
}
