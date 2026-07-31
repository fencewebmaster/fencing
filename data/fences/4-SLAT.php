<?php
/**
 * Slat Fence (Barr-based).
 *
 * Uses Barr's panel/gate/raked/side model as the base, but provides Slat-specific:
 * - Step 2: Slat Size, Slat Gap, Fence Height; Width Dimension From in Panel Options
 * - Step 3: panel/gate/post controls (Edit Spacing moved to Step 2)
 * - Colors: extended Color selections (handled by `fc_color()`)
 */

if (!isset($fences['barr'])) {
	return;
}

$fences['slat'] = $fences['barr'];

$fences['slat']['title'] = 'Slat';

$fences['slat']['live'] = TRUE;

$fences['slat']['name'] = 'SLAT FENCE';

$fences['slat']['slug'] = 'slat';

$fences['slat']['panel_group'] = 'b';

$fences['slat']['image'] = 'assets/img/fences/webp/slat-fence.webp';

$fences['slat']['panel_count'] = 4;

$fences['slat']['color'] = ['black_satin', 'pearl_white_gloss', 'surfmist_matt', 'dune_satin', 'basalt_satin', 'woodland_grey_matt', 'monument_matt'];

$fences['slat']['offcut'] = [
	'panel' => FALSE,
	'gate' => FALSE
];

$fences['slat']['hide_post_value'] = FALSE;

$fences['slat']['form'] = [
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
		'slug' => 'fence_section_dimensions_note',
		'title' => 'Fence Section Dimensions',
		'description' => '<span class="d-block mb-2">Overall Height = Ground to top of post</span><span class="d-block mb-2">Width = Outside or Center-Line of posts</span><span class="d-block">Length can be 300&nbsp;mm to 90,000&nbsp;mm (0.3&nbsp;m to 90&nbsp;m)</span>',
		'target' => '.step-2_notes',
		'type' => 'important-note'
	]
];

$fences['slat']['settings'] = [
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
						'title' => 'Base Plated',
						'image' => 'assets/img/webp/base-plate-posts.webp',
						'extra' => '',
						'default' => TRUE
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
						'extra' => ''
					],
					[
						'slug' => 'opt-4',
						'title' => 'Core Drilled',
						'image' => 'assets/img/core-drilled.png',
						'extra' => ''
					],
					[
						'slug' => 'opt-5',
						'title' => '135 Degree Angle',
						'image' => 'assets/img/135deg-angle.png',
						'extra' => ''
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
		]
	],
	'gate' => [
		'title' => 'Add / Remove Gate',
		'label' => 'Gate Options',
		'action' => ['default'],
		'custom' => TRUE,
		'custom_gate_details' => [
			'title' => 'Your Gate Details',
			'description' => '
		&#9989; Hinges MUST be screwed into a Post<br>
		&#9989; Posts can be bolted to walls<br>
		&#9989; Max Gate Width = 2100mm<br>
		&#9989; Max Gate Height = 2240mm<br>
		&#9989; 65mm x 16.5mm Slats ONLY<br>
		&#9989; 9mm or 20mm Slat Spacing
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
			],
			[
				'title' => 'Width Dimension From',
				'marker' => 'A',
				'slug' => 'width_dimension_from',
				'type' => 'text_option',
				'key' => 'gate',
				'label' => '',
				'close_btn' => FALSE,
				'options' => [
					[
						'slug' => '-1',
						'title' => 'Center-line Width',
						'default' => TRUE
					],
					[
						'slug' => '-2',
						'title' => 'Outside Width'
					]
				]
			],
			[
				'title' => 'Type Of Gate',
				'marker' => 'B',
				'slug' => 'gate_type',
				'type' => 'text_option',
				'key' => 'gate',
				'label' => '',
				'close_btn' => FALSE,
				'options' => [
					[
						'slug' => 'single',
						'title' => 'Single',
						'default' => TRUE
					],
					[
						'slug' => 'double',
						'title' => 'Double'
					]
				]
			],
			[
				'title' => 'Add Heavy Duty Rails?',
				'marker' => 'C',
				'slug' => 'heavy_duty_rails',
				'type' => 'text_option',
				'key' => 'gate',
				'label' => '',
				'close_btn' => FALSE,
				'options' => [
					[
						'slug' => 'yes',
						'title' => 'Yes'
					],
					[
						'slug' => 'no',
						'title' => 'No',
						'default' => TRUE
					]
				]
			]
		]
	],
	'panel_options' => [
		'title' => 'Panel Options',
		'label' => 'Panel Options',
		'action' => ['default'],
		'fields' => [
			[
				'title' => 'Panel Options',
				'slug' => 'panel_option',
				'type' => 'text_option',
				'label' => '',
				'options' => [
					[
						'slug' => 'even',
						'type' => 'text_option',
						'title' => 'Even Size Panels',
						'default' => TRUE,
						'size' => [
							'default' => 2400,
							'width' => 2425,
							'width_based_height' => [
								1000 => 1758,
								1200 => 2230,
								1800 => 1994
							]
						]
					],
					[
						'slug' => 'full',
						'type' => 'text_option',
						'title' => 'Full Size Panels',
						'size' => [
							'default' => 2400,
							'width' => 2425,
							'width_based_height' => [
								1000 => 1758,
								1200 => 2230,
								1800 => 1994
							]
						]
					]
				],
				'notes' => [
					'title' => 'Panel Off-Cuts',
					'description' => 'The off-cut can be used for another fence section (where applicable). If the off-cut is used ensure you manually update the panel quantities to account for this as this planner does NOT use Off-Cuts.'
				],
				'info' => [
					[
						'title' => 'Even Size Panels',
						'description' => 'This option evenly spaces out the posts, which also means you will need to cut down every individual panel.'
					]
				]
			],
			[
				'title' => 'Width Dimension From',
				'marker' => 'A',
				'slug' => 'width_dimension_from',
				'type' => 'text_option',
				'key' => 'panel_options',
				'label' => '',
				'close_btn' => FALSE,
				'options' => [
					[
						'slug' => '-1',
						'title' => 'Center-line Width'
					],
					[
						'slug' => '-2',
						'title' => 'Outside Width',
						'default' => TRUE
					]
				]
			]
		]
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
						'default' => TRUE
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
						'extra' => ''
					],
					[
						'slug' => 'opt-4',
						'title' => 'Core Drilled',
						'image' => 'assets/img/core-drilled.png',
						'extra' => ''
					],
					[
						'slug' => 'opt-5',
						'title' => '135 Degree Angle',
						'image' => 'assets/img/135deg-angle.png',
						'extra' => ''
					]
				]
			]
		]
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
						'default' => TRUE
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
						'default' => FALSE
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
						'title' => 'Base Plated',
						'image' => 'assets/img/webp/base-plate-posts.webp',
						'extra' => '',
						'default' => TRUE
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
						'extra' => ''
					],
					[
						'slug' => 'opt-4',
						'title' => 'Core Drilled',
						'image' => 'assets/img/core-drilled.png',
						'extra' => ''
					],
					[
						'slug' => 'opt-5',
						'title' => '135 Degree Angle',
						'image' => 'assets/img/135deg-angle.png',
						'extra' => ''
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
		]
	]
];

$fences['slat']['pack_qty'] = [
	'slat_spacer+5' => 50,
	'slat_spacer+9' => 50,
	'slat_spacer+12' => 50,
	'slat_spacer+15' => 50,
	'slat_spacer+20' => 50,
	'slat_spacer+30' => 50
];

$fences['slat']['max_panel_width_mm'] = 2400;

$fences['slat']['max_panel_span_mm'] = 2500;
