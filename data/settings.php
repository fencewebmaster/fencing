<?php
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

//----------------------------------------------------------------------------------

if (!function_exists('fc_color')) {
	function fc_color($val ='') {
		if (!function_exists('fc_fence_colors_legacy_map')) {
			require_once dirname(__DIR__) . '/config/fence-colors.php';
		}
		$data = fc_fence_colors_legacy_map();
		return ($val) ? ($data[$val] ?? null) : $data;
	}
}

//----------------------------------------------------------------------------------

if (!function_exists('fc_state')) {
	function fc_state($val ='') {
		$data = [
			"ACT" => "Australian Capital Territory",
			"NSW" => "New South Wales",
			"NT"  => "Northern Territory",
			"QLD" => "Queensland",
			"SA"  => "South Australia",
			"TAS" => "Tasmania",
			"VIC" => "Victoria",
			"WA"  => "Western Australia",
		];
		return ($val) ? $data[$val] : $data;
	}
}

//----------------------------------------------------------------------------------

if (!function_exists('fc_timeframe')) {
	function fc_timeframe($val ='') {
		$data = [
			'asap'    => 'ASAP - Within 24hrs',
			'soon'    => 'SOON - This Week',
			'later'   => 'LATER - This Month',
			'looking' => 'NIL - Just Looking',
		];
		return ($val) ? $data[$val] : $data;
	}
}

//----------------------------------------------------------------------------------

if (!function_exists('fc_extra_needed')) {
	function fc_extra_needed($val ='') {
		// Choices shown in planner / Download Your Project Plans (checkboxes). NIL is separate radio in other-items-needed.php.
		$selectable = [
			'pump-enclosure' => 'Pump Enclosure',
		];
		// Labels for older saved quotes / emails that still reference removed extras.
		$labels = array_merge($selectable, [
			'pool-covers'       => 'Pool Covers',
			'decking'           => 'Decking',
			'pergola'           => 'Pergola',
			'shed'              => 'Shed',
			'outdoor-furniture' => 'Outdoor Furniture',
			'outdoor-kitchen'   => 'Outdoor Kitchen',
		]);

		if( empty($val) ){
			return $selectable;
		}

		$paramParts = explode(',', $val);
		$paramParts = array_map('trim', $paramParts);
		$textValues = [];

		foreach ($paramParts as $part) {
			if (array_key_exists($part, $labels)) {
				$textValues[] = $labels[$part];
			}
		}

		if (!empty($textValues)) {
			return implode(', ', $textValues);
		}

		return 'Nothing Extra, Just Fencing';
	}
}
