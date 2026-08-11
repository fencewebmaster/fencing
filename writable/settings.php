<?php
/**
 * Fence catalog data — assembles $fences from writable/fences/*.php and drops
 * non-live styles outside dev/localhost.
 *
 * Load it through \Fc\Admin\Services\FenceSettingsService, never directly: that service
 * publishes $GLOBALS['fences'] (which CartBuilderService and FenceCatalogService read via
 * `global $fences`) and loads the fc_*() view helpers, which now live in
 * app/src/Helpers/fc_functions.php over app/src/Services/PlannerOptionSettings.php.
 */

$uri_segments = explode('/', trim(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH), '/'));
$host_header = $_SERVER['HTTP_HOST'] ?? '';
$host = parse_url('//' . $host_header, PHP_URL_HOST);

if (!$host) {
	$host = $host_header;
}

$fences = [];

foreach (glob(__DIR__ . '/fences/*.php') ?: [] as $fenceFile) {
	include $fenceFile;
}

$fences_data = array();

foreach ($fences as $fik => $fence_info) {
	$fences_data[$fik] = $fence_info;

	if( !$fence_info['live'] && !in_array('dev', $uri_segments) && !in_array($host, ['localhost', '192.168.1.8']) ) {
		unset($fences_data[$fik]);
	} 
}

$fences = $fences_data;
