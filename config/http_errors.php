<?php
/**
 * Shared HTTP error pages (404, etc.).
 */

declare(strict_types=1);

/**
 * Absolute path to the shared 404 view.
 */
function fc_http_errors_404_view(): string
{
    return dirname(__DIR__) . '/views/errors/404.php';
}

/**
 * Planner URL for frontend “go back” links.
 */
function fc_http_errors_planner_url(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/fc/404.php'));
    $dir = rtrim(dirname($script), '/');

    // When served from /fc/backend/…, climb to the app root.
    if (str_ends_with($dir, '/backend')) {
        $dir = rtrim(dirname($dir), '/');
    }

    $url = ($dir === '' ? '' : $dir) . '/planner';
    $site = (string) ($_SERVER['SERVER_NAME'] ?? '');
    if ($site !== '') {
        $url .= '?site=' . rawurlencode($site);
    }

    return $url;
}

/**
 * Detect admin vs frontend from the current script path.
 *
 * @return 'admin'|'frontend'
 */
function fc_http_errors_detect_area(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_contains($script, '/backend/') || str_ends_with($script, '/backend')) {
        return 'admin';
    }

    $uri = str_replace('\\', '/', (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? ''));
    if (str_contains($uri, '/backend/')) {
        return 'admin';
    }

    return 'frontend';
}

/**
 * Render a 404 response and exit.
 *
 * @param 'admin'|'frontend'|'auto' $area
 */
function fc_abort_404(string $area = 'auto', string $message = ''): void
{
    if ($area === 'auto') {
        $area = fc_http_errors_detect_area();
    }
    if ($area !== 'admin') {
        $area = 'frontend';
    }

    if (
        !empty($_SERVER['HTTP_ACCEPT'])
        && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json')
        && !str_contains((string) $_SERVER['HTTP_ACCEPT'], 'text/html')
    ) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => $message !== '' ? $message : 'Not found.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(404);

    if ($area === 'admin') {
        $homeUrl = function_exists('fc_auth_dashboard_url')
            ? fc_auth_dashboard_url()
            : '/dashboard';
        $homeLabel = 'Back to Dashboard';
        $defaultMessage = 'This admin page could not be found.';
    } else {
        $homeUrl = fc_http_errors_planner_url();
        $homeLabel = 'Back to Planner';
        $defaultMessage = 'This page could not be found.';
    }

    $fc404Message = $message !== '' ? $message : $defaultMessage;
    $fc404HomeUrl = $homeUrl;
    $fc404HomeLabel = $homeLabel;
    $fc404Context = $area;

    $view = fc_http_errors_404_view();
    if (is_readable($view)) {
        include $view;
        exit;
    }

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>404 Not Found</title></head><body>';
    echo '<h1>404 Not Found</h1><p>' . htmlspecialchars($fc404Message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><a href="' . htmlspecialchars($fc404HomeUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($fc404HomeLabel, ENT_QUOTES, 'UTF-8') . '</a></p>';
    echo '</body></html>';
    exit;
}
