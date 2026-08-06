<?php
/**
 * FC Admin — unified JSON API entry.
 *
 * Usage: api.php?module=entries&action=list
 * Modules: entries, products, gallery, settings, fenceStyles
 */

declare(strict_types=1);

require __DIR__ . '/../app/app_bootstrap.php';

$module = isset($_GET['module']) ? (string) $_GET['module'] : '';

Fc\Admin\Core\Application::handleApiRequest($module !== '' ? $module : null);
