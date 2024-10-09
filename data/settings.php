<?php
$uri_segments = explode('/', trim(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH), '/'));

foreach (glob('data/fences/*') as $fence) {
	include $fence;
}

$fences_data = array();

foreach($fences as $fik => $fence_info ) {
	$fences_data[$fik] = $fence_info;

	if( !$fence_info['live'] && !in_array('dev', $uri_segments) && !in_array($_SERVER['HTTP_HOST'], ['localhost', '192.168.1.8']) ) {
		unset($fences_data[$fik]);
	} 
}

$fences = $fences_data;

//----------------------------------------------------------------------------------

function fc_color($val ='') {
	$data = [
		'black' => [
			'title' => 'Black',
			'sub_title' => 'Satin',
			'background_color' => '#000',
			'text_color' => '#fff',
		],
		'white' => [
			'title' => 'Pearl White',
			'sub_title' => 'Gloss',
			'background_color' => '#fff',
			'text_color' => '#000',
		],
		'monument' => [
			'title' => 'Monument',
			'sub_title' => 'Matt',
			'background_color' => '#635F5D',
			'text_color' => '#fff',
		],
		'matt_black' => [
			'title' => 'Black',
			'sub_title' => 'Matt',
			'background_color' => '#000',
			'text_color' => '#fff',
		],
		'polished_stainless_steel' => [
			'title' => 'Stainless Steel',
			'sub_title' => 'Polished',
			'background_color' => 'linear-gradient(90deg, rgba(168,168,168,1) 0%, rgba(251,251,251,1) 36%, rgba(255,255,255,1) 60%, rgba(168,168,168,1) 100%);',
			'text_color' => '#000',
		],
		'satin_stainless_steel' => [
			'title' => 'Stainless Steel',
			'sub_title' => 'Satin',
			'background_color' => 'url(https://www.rigidized.com/wp-content/uploads/4Satin-01.jpg);',
			'text_color' => '#000',
		]

	];
	return ($val) ? $data[$val] : $data;
} 

//----------------------------------------------------------------------------------

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

//----------------------------------------------------------------------------------

function fc_timeframe($val ='') {
	$data = [
		'asap'    => 'ASAP - Within 24hrs',
		'soon'    => 'SOON - This Week',
		'later'   => 'LATER - This Month',
		'looking' => 'NIL - Just Looking',
	];
	return ($val) ? $data[$val] : $data;
} 

//----------------------------------------------------------------------------------

function fc_installer($val ='') {
	$data = [
		'diy' => 'DIY - I install myself <span class="badge bg-secondary py-1">cheaper</span>',
		'install' => 'NO - I need an installer',
	];
	return ($val) ? $data[$val] : $data;
} 

//----------------------------------------------------------------------------------

function fc_extra_needed($val ='') {
	$data = [
		'pool-covers' 		=> 'Pool Covers',
		'pump-enclosure' 	=> 'Pump Enclosure',
		'decking' 			=> 'Decking',
		'pergola' 			=> 'Pergola',
		'shed' 				=> 'Shed',
		'outdoor-furniture' => 'Outdoor Furniture',
		'outdoor-kitchen' 	=> 'Outdoor Kitchen',
	];

	if( empty($val) ){
		return $data;
	}

	$paramParts = explode(',', $val);
    $paramParts = array_map('trim', $paramParts);
    $textValues = [];

    foreach ($paramParts as $part) {
        if (array_key_exists($part, $data)) {
            $textValues[] = $data[$part];
        }
    }

    if (!empty($textValues)) {
        return implode(', ', $textValues);
    } else {
        return 'Nothing Extra, Just Fencing';
    }
} 
