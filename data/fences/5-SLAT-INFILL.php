<?php
/**
 * Slat Fence Infill (Barr-based, Slat-like behavior).
 *
 * UI requirements:
 * - No Gate Options
 * - No Post Options
 * - No Edit Left Side
 * - No Edit Right Side
 * - Only Panel Options and Edit Spacing
 * - Default post display = Wall Fix
 */

if (!isset($fences['barr'])) {
	return;
}

$fences['slat_fence_infill'] = $fences['barr'];

$fences['slat_fence_infill']['title'] = 'Slat Infill';

$fences['slat_fence_infill']['live'] = TRUE;

$fences['slat_fence_infill']['name'] = 'SLAT FENCE INFILL';

$fences['slat_fence_infill']['slug'] = 'slat_fence_infill';

$fences['slat_fence_infill']['panel_group'] = 'b';

$fences['slat_fence_infill']['image'] = 'assets/img/fences/webp/slat-fence-infill.webp';

$fences['slat_fence_infill']['panel_count'] = '';

$fences['slat_fence_infill']['color'] = ['black_satin', 'pearl_white_gloss', 'surfmist_matt', 'dune_satin', 'basalt_satin', 'woodland_grey_matt', 'monument_matt'];

$fences['slat_fence_infill']['offcut'] = [
	'panel' => FALSE,
	'gate' => FALSE
];

$fences['slat_fence_infill']['hide_post_value'] = TRUE;

$fences['slat_fence_infill']['form'] = [
	[
		'slug' => 'slat_size',
		'title' => 'Slat Size',
		'target' => '.step-2_field',
		'type' => 'slat-size-select',
		'slat_size_rows' => [
			[
				'value' => '',
				'label' => 'Select Slat Size'
			],
			[
				'value' => '65.3',
				'label' => '65mm'
			],
			[
				'value' => '90.3',
				'label' => '90mm'
			]
		],
		'default' => ''
	],
	[
		'slug' => 'slat_gap',
		'title' => 'Slat Gap',
		'target' => '.step-2_field',
		'type' => 'slat-gap-select',
		'option' => [
			0 => '0mm',
			5 => '5mm',
			9 => '9mm',
			12 => '12mm',
			15 => '15mm',
			20 => '20mm',
			30 => '30mm',
			'' => 'Select Gap'
		],
		'default' => ''
	],
	[
		'slug' => 'max_fence_height',
		'title' => 'Fence Height',
		'target' => '.step-2_field',
		'type' => 'slat-max-height-input'
	],
	[
		'slug' => 'panel_count',
		'title' => 'Number of Panels',
		'target' => '.step-2_field',
		'type' => 'number-field',
		'default' => '',
		'min' => 1,
		'max' => 9999
	],
	[
		'slug' => 'panel_count_note',
		'title' => 'Number of Panels',
		'description' => '<span class="d-block mb-2 fw-semibold">How many panels this size?</span><span class="d-block">Enter the number of equal-width panel openings required for this section.</span>',
		'target' => '.step-2_notes',
		'type' => 'important-note'
	]
];

$fences['slat_fence_infill']['settings'] = [
	'rail_options' => [],
	'left_side' => [
		'title' => 'Edit Left Side',
		'label' => 'Left Side',
		'action' => ['edit'],
		'notes' => [
			'title' => 'When To Use Swivel Brackets',
			'description' => 'Swivel brackets are used instead of the standards straight brackets. This allow you to connect this fence section at an angle. e.g. 45degs to the connecting fence section'
		],
		'fields' => [
			[
				'title' => 'Edit Left Side',
				'marker' => 'A',
				'class' => '',
				'slug' => 'left_option',
				'type' => 'range_option',
				'label' => '',
				'close_btn' => TRUE,
				'options' => [
					[
						'slug' => 'yes-post',
						'type' => 'range_option',
						'key' => 'left_side',
						'image' => 'assets/img/webp/yes-post.webp',
						'default' => TRUE,
						'title' => '',
						'size' => [
							'width' => 0
						]
					],
					[
						'slug' => 'no-post',
						'type' => 'range_option',
						'key' => 'left_side',
						'image' => 'assets/img/webp/no-post-1.webp',
						'title' => '',
						'size' => [
							'width' => -50
						]
					],
					[
						'slug' => 'no-post-swivel-bracket',
						'type' => 'range_option',
						'key' => 'left_side',
						'image' => 'assets/img/webp/no-post-2.webp',
						'title' => '',
						'size' => [
							'width' => -50
						]
					]
				],
				'notes' => [
					'title' => 'When To Use Swivel Brackets',
					'description' => 'Swivel brackets are used instead of the standards straight brackets. This allow you to connect this fence section at an angle. e.g. 45degs to the connecting fence section'
				]
			],
			[
				'title' => 'Post Options',
				'marker' => 'B',
				'slug' => 'post_option',
				'type' => 'image_option',
				'key' => 'left_side',
				'label' => '',
				'close_btn' => FALSE,
				'notes' => [
					'title' => '1200mm',
					'description' => '
							Base Plated Posts = 50x50mm Posts<br>
							Cemented In Posts = 50x25mm Posts<br>
							*Due to the 1800H height for Base Plated posts we need to use 50x50mm Base Plated Posts'
				],
				'options' => [
					[
						'slug' => 'opt-1',
						'title' => '',
						'image' => 'assets/img/webp/base-plate-posts.webp',
						'extra' => '',
						'key' => 'post_options'
					],
					[
						'slug' => 'opt-2',
						'title' => '',
						'image' => 'assets/img/webp/cement-in-posts.webp',
						'extra' => '',
						'key' => 'post_options',
						'default' => TRUE
					]
				]
			],
			[
				'title' => 'Add Step-Up Panel',
				'slug' => 'left_raked',
				'marker' => 'C',
				'class' => 'd-none',
				'type' => 'text_option',
				'key' => 'add_step_up_panels',
				'label' => '',
				'close_btn' => FALSE,
				'options' => [
					[
						'slug' => 'none',
						'title' => 'Nil',
						'default' => TRUE,
						'size' => [
							'width' => 0,
							'height' => 0
						]
					],
					[
						'slug' => '1300x300',
						'title' => '1300H - 300 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1300
						]
					],
					[
						'slug' => '1400x400',
						'title' => '1400H - 400 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1400
						]
					],
					[
						'slug' => '1500x500',
						'title' => '1500H - 500 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1500
						]
					],
					[
						'slug' => '1600x600',
						'title' => '1600H - 600 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1600
						]
					],
					[
						'slug' => '1700x700',
						'title' => '1700H - 700 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1700
						]
					],
					[
						'slug' => '1800x600',
						'title' => '1800H - 600 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1800
						]
					]
				],
				'notes' => [
					'image' => 'assets/img/webp/poolsafe-step-up-panel-v3.webp',
					'title' => 'When To Use Step-Up Panels',
					'description' => 'Step-Up panels are used when you need to change the heights or go over an object. e,g, over a retaining wall, over a few steps... against a boundary fence etc...'
				]
			]
		],
		'disabled' => TRUE
	],
	'gate' => [
		'title' => 'Add / Remove Gate',
		'label' => 'Add Gate',
		'action' => ['default'],
		'custom' => TRUE,
		'custom_gate_details' => [
			'title' => 'Your gate details',
			'description' => '
					&#9989; Hinges MUST be screwed into a post<br>
					&#9989; Posts can be bolted to walls<br>
					&#9989; Custom gate width is limited by your selected panel option and fence height (max shown above)
				'
		],
		'size' => [
			'width' => 975
		],
		'fields' => [
			[
				'slug' => 'move',
				'type' => 'move',
				'label' => ''
			]
		],
		'disabled' => TRUE
	],
	'panel_options' => [
		'title' => 'Panel Options',
		'label' => 'Panel Options',
		'action' => ['default'],
		'fields' => [
			[
				'title' => 'Slat Size',
				'marker' => 'A',
				'slug' => 'slat_size',
				'type' => 'image_option',
				'key' => 'panel_options',
				'label' => 'Slat Size',
				'close_btn' => FALSE,
				'options' => [
					[
						'slug' => '65.3',
						'title' => '65mm',
						'image' => 'assets/img/65mm-slats.webp',
						'extra' => '',
						'key' => 'panel_options',
						'default' => TRUE
					],
					[
						'slug' => '90.3',
						'title' => '90mm',
						'image' => 'assets/img/90mm-slats.webp',
						'extra' => '',
						'key' => 'panel_options'
					]
				]
			]
		],
		'disabled' => TRUE
	],
	'post_options' => [
		'title' => 'Post Options',
		'label' => 'Post Options',
		'action' => ['default'],
		'fields' => [
			[
				'title' => 'Post Options',
				'slug' => 'post_option',
				'type' => 'image_option',
				'label' => '',
				'notes' => [
					'title' => '1200mm',
					'description' => '
							Base Plated Posts = 50x50mm Posts<br>
							Cemented In Posts = 50x25mm Posts<br>
							*Due to the 1800H height for Base Plated posts we need to use 50x50mm Base Plated Posts'
				],
				'options' => [
					[
						'slug' => 'opt-1',
						'title' => 'Base Plated',
						'image' => 'assets/img/webp/base-plate-posts.webp',
						'extra' => '',
						'default' => FALSE
					],
					[
						'slug' => 'opt-2',
						'title' => 'Cement In',
						'image' => 'assets/img/webp/cement-in-posts.webp',
						'extra' => '',
						'default' => FALSE
					],
					[
						'slug' => 'opt-3',
						'title' => 'Wall Fix',
						'image' => 'assets/img/wall-fix.png',
						'extra' => '',
						'default' => TRUE
					],
					[
						'slug' => 'opt-4',
						'title' => 'Core Drilled',
						'image' => 'assets/img/core-drilled.png',
						'extra' => '',
						'default' => FALSE
					],
					[
						'slug' => 'opt-5',
						'title' => '135 Degree Angle',
						'image' => 'assets/img/135deg-angle.png',
						'extra' => '',
						'default' => FALSE
					]
				]
			]
		],
		'disabled' => TRUE
	],
	'right_side' => [
		'title' => 'Edit Right Side',
		'label' => 'Right Side',
		'action' => ['edit'],
		'fields' => [
			[
				'title' => 'Edit Right Side',
				'marker' => 'A',
				'class' => '',
				'slug' => 'right_option',
				'type' => 'range_option',
				'label' => '',
				'close_btn' => TRUE,
				'options' => [
					[
						'slug' => 'yes-post',
						'type' => 'range_option',
						'key' => 'right_side',
						'image' => 'assets/img/webp/yes-post.webp',
						'default' => TRUE,
						'title' => '',
						'size' => [
							'width' => 0
						]
					],
					[
						'slug' => 'no-post',
						'type' => 'range_option',
						'key' => 'right_side',
						'image' => 'assets/img/webp/no-post-1.webp',
						'title' => '',
						'size' => [
							'width' => -50
						]
					],
					[
						'slug' => 'no-post-swivel-bracket',
						'type' => 'range_option',
						'key' => 'right_side',
						'image' => 'assets/img/webp/no-post-2.webp',
						'title' => '',
						'size' => [
							'width' => -50
						]
					]
				]
			],
			[
				'title' => 'Post Options',
				'marker' => 'B',
				'slug' => 'post_option',
				'type' => 'image_option',
				'key' => 'left_side',
				'label' => '',
				'close_btn' => FALSE,
				'notes' => [
					'title' => '1200mm',
					'description' => '
							Base Plated Posts = 50x50mm Posts<br>
							Cemented In Posts = 50x25mm Posts<br>
							*Due to the 1800H height for Base Plated posts we need to use 50x50mm Base Plated Posts'
				],
				'options' => [
					[
						'slug' => 'opt-1',
						'title' => '',
						'image' => 'assets/img/webp/base-plate-posts.webp',
						'extra' => '',
						'key' => 'post_options'
					],
					[
						'slug' => 'opt-2',
						'title' => '',
						'image' => 'assets/img/webp/cement-in-posts.webp',
						'extra' => '',
						'key' => 'post_options',
						'default' => TRUE
					]
				]
			],
			[
				'title' => 'Add Step-Up Panel',
				'marker' => 'C',
				'class' => 'd-none',
				'slug' => 'right_raked',
				'type' => 'text_option',
				'key' => 'add_step_up_panels',
				'label' => '',
				'close_btn' => FALSE,
				'options' => [
					[
						'slug' => 'none',
						'title' => 'Nil',
						'default' => TRUE,
						'size' => [
							'width' => 0,
							'height' => 0
						]
					],
					[
						'slug' => '1300x300',
						'title' => '1300H - 300 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1300
						]
					],
					[
						'slug' => '1400x400',
						'title' => '1400H - 400 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1400
						]
					],
					[
						'slug' => '1500x500',
						'title' => '1500H - 500 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1500
						]
					],
					[
						'slug' => '1600x600',
						'title' => '1600H - 600 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1600
						]
					],
					[
						'slug' => '1700x700',
						'title' => '1700H - 700 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1700
						]
					],
					[
						'slug' => '1800x600',
						'title' => '1800H - 600 Step-Up',
						'size' => [
							'width' => 1250,
							'height' => 1800
						]
					]
				],
				'notes' => [
					'image' => 'assets/img/webp/poolsafe-step-up-panel-v3.webp',
					'title' => 'When To Use Step-Up Panels',
					'description' => 'Step-Up panels are used when you need to change the heights or go ever an object. e,g, over a retaining wall, over a few steps... against a boundary fence etc...'
				]
			]
		],
		'disabled' => TRUE
	],
	'edit_spacing' => [
		'title' => 'Edit Spacing',
		'label' => 'Edit Spacing',
		'action' => ['default'],
		'fields' => [
			[
				'title' => 'Slat Gap',
				'slug' => 'slat_gap',
				'type' => 'text_option',
				'label' => '',
				'options' => [
					[
						'slug' => '0',
						'type' => 'text_option',
						'title' => '0mm',
						'default' => FALSE
					],
					[
						'slug' => '5',
						'type' => 'text_option',
						'title' => '5mm',
						'default' => FALSE
					],
					[
						'slug' => '9',
						'type' => 'text_option',
						'title' => '9mm',
						'default' => FALSE
					],
					[
						'slug' => '12',
						'type' => 'text_option',
						'title' => '12mm',
						'default' => FALSE
					],
					[
						'slug' => '15',
						'type' => 'text_option',
						'title' => '15mm',
						'default' => FALSE
					],
					[
						'slug' => '20',
						'type' => 'text_option',
						'title' => '20mm',
						'default' => TRUE
					],
					[
						'slug' => '30',
						'type' => 'text_option',
						'title' => '30mm',
						'default' => FALSE
					]
				]
			]
		],
		'disabled' => TRUE
	]
];

$fences['slat_fence_infill']['pack_qty'] = [
	'slat_spacer+5' => 50,
	'slat_spacer+9' => 50,
	'slat_spacer+12' => 50,
	'slat_spacer+15' => 50,
	'slat_spacer+20' => 50,
	'slat_spacer+30' => 50,
	'sfs+end_caps' => 2
];
