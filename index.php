<?php
/**
 * FC frontend — front controller.
 *
 * The only PHP entry point in the web root. .htaccess rewrites every public URL that
 * isn't a real file onto this file; routing lives in the 'frontend' group of app/routes/web.php.
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use Fc\Admin\Settings\SeoSettings;

// Settings -> SEO -> Search engine visibility off: every public response says so, not only the
// pages that print a robots meta tag.
if (!SeoSettings::searchEngineVisible()) {
    header('X-Robots-Tag: noindex, nofollow');
}

// Debugbar collectors (Settings -> Console -> Debug Mode) arm before dispatch so request
// timing, DB queries and PHP errors are visible from the first byte. Hard no-op when off,
// and it never writes into responses - footer.php decides whether anything is emitted.
\Fc\Admin\Debug\DebugbarServer::bootIfEnabled();

\Fc\Admin\Core\FrontendApplication::handleWebRequest();