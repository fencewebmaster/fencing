<?php
/**
 * Frontend 404 entry (Apache rewrite / ErrorDocument).
 */

declare(strict_types=1);

require_once __DIR__ . '/app/src/Core/Autoloader.php';
\Fc\Admin\Core\Autoloader::register();

require_once __DIR__ . '/app/src/Core/NotFoundHandler.php';

\Fc\Admin\Core\NotFoundHandler::abort('frontend');
