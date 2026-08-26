<?php
/**
 * FC Admin — unified JSON API entry.
 *
 * Usage: api.php?module=entries&action=list
 * Modules: entries, products, gallery, settings, fenceStyles
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if (!Fc\Admin\Settings\ConsoleSettings::debugMode()) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

$module = isset($_GET['module']) ? (string) $_GET['module'] : '';

Fc\Admin\Core\Application::handleApiRequest($module !== '' ? $module : null);
