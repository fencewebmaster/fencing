<?php
/**
 * Frontend 404 entry (Apache rewrite / ErrorDocument).
 */

declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/http_errors.php';

fc_abort_404('frontend');
