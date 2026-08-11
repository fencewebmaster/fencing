<?php
/**
 * FC frontend — front controller.
 *
 * The only PHP entry point in the web root. .htaccess rewrites every public URL that
 * isn't a real file onto this file; routing lives in the 'frontend' group of app/routes/web.php.
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

\Fc\Admin\Core\FrontendApplication::handleWebRequest();
