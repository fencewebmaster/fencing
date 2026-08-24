<?php
$fences['glass_pool'] = [
	'title' => 'Glass Pool',
	'live' => TRUE,
	'name' => 'Glass Pool',
	'slug' => 'glass_pool',
	'panel_group' => 'a',
	'image' => 'public/assets/img/fences/webp/glass-pool.webp',
	'panel_count' => 4,
	'color' => ['matt_black', 'polished_stainless_steel', 'satin_stainless_steel'],
	'offcut' => [
		'panel' => FALSE,
		'gate' => FALSE
	],
	'hide_post_value' => FALSE,
	'settings' => [
		'rail_options' => [],
		'left_side' => [
			'title' => 'Edit Left Side',
			'label' => 'Left Side',
			'action' => ['edit'],
			'class' => '',
			'notes' => [
				'title' => 'When To Use Swivel Brackets',
				'description' => 'Swivel brackets are used instead of the standards straight brackets. This allow you to connect this fence section at an angle. e.g. 45degs to the connecting fence section'
			],
			'fields' => [
				[
					'title' => 'Edit Left Side',
					'marker' => 'A',
					'slug' => 'left_option',
					'type' => 'range_option',
					'label' => '',
					'close_btn' => TRUE,
					'class' => 'btn-recalculate',
					'options' => [
						[
							'slug' => 'side-gap',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'public/assets/img/clamps/gap-left.jpg',
							'default' => TRUE,
							'title' => 'Gap',
							'size' => [
								'width' => -1
							]
						],
						[
							'slug' => 'PTP90',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'public/assets/img/clamps/90-degree.jpg',
							'title' => '90deg Panel to Panel Clamp',
							'size' => [
								'width' => 0
							]
						],
						[
							'slug' => 'PTPA',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'public/assets/img/clamps/swivel-clamps.jpg',
							'title' => 'Angled Panel to Panel Clamp',
							'size' => [
								'width' => 25
							]
						],
						[
							'slug' => 'PTW',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'public/assets/img/clamps/wall-clamp.jpg',
							'title' => 'Panel to wall clamp',
							'size' => [
								'width' => 25
							]
						]
					],
					'notes' => [
						'title' => '',
						'description' => ''
					]
				],
				[
					'title' => 'Spigot Options',
					'marker' => 'B',
					'slug' => 'post_option',
					'type' => 'image_option',
					'key' => 'left_side',
					'label' => '',
					'class' => 'd-hidden',
					'close_btn' => FALSE,
					'options' => [
						[
							'slug' => 'opt-1',
							'title' => 'Bolt Down',
							'image' => 'public/assets/img/webp/bolt-down-w.png',
							'extra' => '',
							'key' => 'post_options',
							'default' => TRUE
						],
						[
							'slug' => 'opt-2',
							'title' => 'Core-Drilled<br>285mm',
							'image' => 'public/assets/img/webp/core-drill-w.png',
							'extra' => '',
							'key' => 'post_options'
						]
					]
				],
				[
					'title' => 'Add Step-Up Panel',
					'slug' => 'left_raked',
					'marker' => 'B',
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
						'image' => 'public/assets/img/raked-glass-left.jpg',
						'title' => 'When To Use Step-Up Panels',
						'description' => 'Step-Up panels are used when you need to change the heights or go over an object. e,g, over a retaining wall, over a few steps... against a boundary fence etc...'
					]
				]
			]
		],
		'gate' => [
			'title' => 'Add / Remove Gate',
			'label' => 'Add Gate',
			'action' => ['default'],
			'custom' => FALSE,
			'size' => [
				'width' => 700
			],
			'fields' => [
				[
					'slug' => 'gate_hinge_type',
					'type' => 'image_option',
					'title' => 'Hinge Type',
					'marker' => 'A',
					'options' => [
						[
							'slug' => 'opt-1',
							'title' => 'Standard Range',
							'image' => 'public/assets/img/standard-hinge.jpg',
							'extra' => '',
							'key' => 'gate',
							'default' => TRUE,
							'gap' => [
								'hinge' => 10,
								'latch' => 9
							],
							'gate_width' => [750, 834, 890, 1000]
						],
						[
							'slug' => 'opt-2',
							'title' => 'Soft-Close Range',
							'image' => 'public/assets/img/soft-hinge.jpg',
							'extra' => '',
							'key' => 'gate',
							'gap' => [
								'hinge' => 5,
								'latch' => 9
							],
							'gate_width' => [800, 900]
						]
					]
				],
				[
					'title' => 'Hinge Panel width',
					'slug' => 'gate_hinge_panel_width',
					'marker' => 'B',
					'type' => 'dropdown_option',
					'label' => '',
					'close_btn' => FALSE,
					'options' => [
						[
							'slug' => '600',
							'title' => '600mm',
							'key' => 'gate',
							'size' => [
								'width' => 600,
								'height' => 1300
							]
						],
						[
							'slug' => '800',
							'title' => '800mm',
							'key' => 'gate',
							'size' => [
								'width' => 800,
								'height' => 1400
							]
						],
						[
							'slug' => '1000',
							'title' => '1000mm',
							'key' => 'gate',
							'size' => [
								'width' => 1000,
								'height' => 1500
							]
						],
						[
							'slug' => '1100',
							'title' => '1100mm',
							'key' => 'gate',
							'size' => [
								'width' => 1100,
								'height' => 1600
							]
						],
						[
							'slug' => '1200',
							'title' => '1200mm',
							'key' => 'gate',
							'default' => TRUE,
							'size' => [
								'width' => 1200,
								'height' => 1700
							]
						],
						[
							'slug' => '1300',
							'title' => '1300mm',
							'key' => 'gate',
							'size' => [
								'width' => 1300,
								'height' => 1800
							]
						],
						[
							'slug' => '1400',
							'title' => '1400mm',
							'key' => 'gate',
							'size' => [
								'width' => 1400,
								'height' => 1800
							]
						],
						[
							'slug' => '1500',
							'title' => '1500mm',
							'key' => 'gate',
							'size' => [
								'width' => 1500,
								'height' => 1800
							]
						],
						[
							'slug' => '1600',
							'title' => '1600mm',
							'key' => 'gate',
							'size' => [
								'width' => 1600,
								'height' => 1800
							]
						],
						[
							'slug' => '1700',
							'title' => '1700mm',
							'key' => 'gate',
							'size' => [
								'width' => 1700,
								'height' => 1800
							]
						],
						[
							'slug' => '1800',
							'title' => '1800mm',
							'key' => 'gate',
							'size' => [
								'width' => 1800,
								'height' => 1800
							]
						]
					],
					'notes' => [
						'image' => '',
						'title' => 'Note',
						'description' => 'Hinge panel MUST be wider than the gate, unless Hinge panel has additional fixings and supports.'
					]
				],
				[
					'title' => 'Gate Width',
					'slug' => 'gate_width',
					'marker' => 'C',
					'type' => 'dropdown_option_check',
					'check_settings' => [
						'name' => 'gate_only',
						'class' => 'select-gate_only',
						'label' => 'Gate <b>Only</b>'
					],
					'label' => '',
					'close_btn' => FALSE,
					'options' => [
						[
							'slug' => '750',
							'title' => '750mm',
							'key' => 'gate',
							'default' => TRUE,
							'size' => [
								'width' => 750,
								'height' => 1300
							]
						],
						[
							'slug' => '800',
							'title' => '800mm',
							'key' => 'gate',
							'size' => [
								'width' => 800,
								'height' => 1300
							]
						],
						[
							'slug' => '834',
							'title' => '834mm',
							'key' => 'gate',
							'size' => [
								'width' => 834,
								'height' => 1300
							]
						],
						[
							'slug' => '890',
							'title' => '890mm',
							'key' => 'gate',
							'size' => [
								'width' => 890,
								'height' => 1300
							]
						],
						[
							'slug' => '900',
							'title' => '900mm',
							'key' => 'gate',
							'size' => [
								'width' => 900,
								'height' => 1300
							]
						],
						[
							'slug' => '1000',
							'title' => '1000mm',
							'key' => 'gate',
							'size' => [
								'width' => 1000,
								'height' => 1300
							]
						]
					]
				],
				[
					'marker' => 'D',
					'slug' => 'move',
					'type' => 'move'
				],
				[
					'slug' => 'gate_hinge_position',
					'type' => 'image_option',
					'title' => 'Hinge Position',
					'marker' => 'E',
					'options' => [
						[
							'slug' => 'left-hand',
							'title' => 'Left Hand',
							'image' => 'public/assets/img/left-hand.jpg',
							'extra' => '',
							'key' => 'gate',
							'default' => TRUE
						],
						[
							'slug' => 'right-hand',
							'title' => 'Right Hand',
							'image' => 'public/assets/img/right-hand.jpg',
							'extra' => '',
							'key' => 'gate',
							'default' => FALSE
						]
					]
				]
			]
		],
		'edit_spacing' => [
			'title' => 'Max Panel Spacing',
			'label' => 'Edit Spacing',
			'action' => ['default'],
			'fields' => [
				[
					'title' => 'Max Panel Spacing',
					'image' => 'public/assets/img/center-gap.png',
					'slug' => 'panel_option',
					'type' => 'range_icon',
					'label' => '',
					'unit' => 'mm',
					'min' => 30,
					'max' => 80,
					'step' => 10,
					'default' => 50
				]
			]
		],
		'panel_options_custom' => [
			'title' => 'Panel Options',
			'label' => 'Panel Options',
			'action' => ['default'],
			'fields' => [
				[
					'title' => 'Maximum Panel Size',
					'slug' => 'panel_option',
					'type' => 'range_sub',
					'marker' => 'A',
					'label' => '',
					'unit' => 'mm',
					'min' => 1000,
					'max' => 2000,
					'step' => 50,
					'default' => 1400,
					'weight' => [
						'default' => 72,
						'unit' => 'kg'
					]
				],
				[
					'title' => 'Panel Clamps',
					'marker' => 'B',
					'slug' => 'post_option',
					'type' => 'image_option',
					'key' => 'left_side',
					'label' => '',
					'close_btn' => FALSE,
					'notes' => [
						'title' => 'When To Use Panel Clamps',
						'description' => 'Panel Clamps can be used to make the fencing more rigid'
					],
					'options' => [
						[
							'slug' => 'opt-1',
							'image' => 'public/assets/img/clamps/no-clamps.png',
							'extra' => '',
							'title' => 'No Clamps',
							'key' => 'post_options',
							'default' => TRUE
						],
						[
							'slug' => 'opt-2',
							'image' => 'public/assets/img/clamps/yes-clamps.png',
							'title' => 'Yes Clamps',
							'extra' => '',
							'key' => 'post_options'
						]
					]
				]
			]
		],
		'panel_options' => [
			'title' => 'Panel Options',
			'label' => 'Panel Options',
			'action' => ['default'],
			'class' => 'd-none',
			'notes' => [
				'title' => 'Panel Off-Cuts',
				'description' => 'The off-cut can be used for another fence section (where applicable). If the off-cut is used ensure you manually update the panel quantities to account for this as this planner does NOT use Off-Cuts.'
			],
			'info' => [
				[
					'title' => 'Even Size Panels',
					'description' => 'This option evenly spaces out the posts, which also means you will need to cut down every individual panel.'
				],
				[
					'title' => 'Full Size 2400W / 3000W Panels:',
					'description' => 'This option uses full length panels, which means you will ONLY need to cut down 1x panel. '
				]
			],
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
							'title' => 'Full Size Panels 2000W',
							'desc' => 'ONLY Available In BLACK',
							'default' => TRUE,
							'size' => [
								'width' => 2050,
								'default' => 1400
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
						],
						[
							'title' => 'Use 2400W / 3000W Panels',
							'description' => 'This option uses full length panels, which means you will ONLY need to cut down 1x panel. '
						]
					]
				]
			]
		],
		'post_options' => [
			'title' => 'Post Options',
			'label' => 'Spigot Options',
			'action' => ['default'],
			'fields' => [
				[
					'title' => 'Spigot Options',
					'slug' => 'post_option',
					'type' => 'image_option',
					'label' => '',
					'notes' => [
						'title' => 'Note',
						'description' => '
							<ul>
								<li><b>Base Plated</b> Spigots use 4x M8 Fixings</li>
								<li><b>Non Conductive</b> spigots are required when the spigots are within 1200mm from the Pool/Water</li>
								<li><b>Core-Drilled 285mm</b> Spigots are used when the cement is at surface level</li>
								<li><b>Core-Drilled 360mm</b> Spigots are used when there is a layer of pavers and sand then cement</li>
							</ul>'
					],
					'options' => [
						[
							'slug' => 'opt-1',
							'title' => 'Base Plated',
							'image' => 'public/assets/img/spigots/base-plated.jpg',
							'extra' => '',
							'key' => 'post_options',
							'default' => TRUE
						],
						[
							'slug' => 'opt-1-1',
							'title' => 'Non Conductive',
							'image' => 'public/assets/img/spigots/non-conductive.jpg',
							'extra' => '',
							'key' => 'post_options'
						],
						[
							'slug' => 'opt-2',
							'title' => 'Core-Drilled<br>285mm',
							'image' => 'public/assets/img/spigots/core-drilled-285mm.jpg',
							'extra' => '',
							'key' => 'post_options'
						],
						[
							'slug' => 'opt-2-1',
							'title' => 'Core-Drilled<br>360mm',
							'image' => 'public/assets/img/spigots/core-drilled-360mm.jpg',
							'extra' => '',
							'key' => 'post_options'
						]
					]
				]
			]
		],
		'right_side' => [
			'title' => 'Edit Right Side',
			'label' => 'Right Side',
			'action' => ['edit'],
			'notes' => [
				'title' => 'When To Use Swivel Brackets',
				'description' => 'Swivel brackets are used instead of the standards straight brackets. This allow you to connect this fence section at an angle. e.g. 45degs to the connecting fence section'
			],
			'fields' => [
				[
					'title' => 'Edit Right Side',
					'marker' => 'A',
					'slug' => 'right_option',
					'type' => 'range_option',
					'label' => '',
					'close_btn' => TRUE,
					'class' => 'btn-recalculate',
					'options' => [
						[
							'slug' => 'side-gap',
							'type' => 'range_option',
							'key' => 'right_side',
							'image' => 'public/assets/img/clamps/gap-right.jpg',
							'default' => TRUE,
							'title' => 'Gap',
							'size' => [
								'width' => -1
							]
						],
						[
							'slug' => 'PTP90',
							'type' => 'range_option',
							'key' => 'right_side',
							'image' => 'public/assets/img/clamps/90-degree.jpg',
							'title' => '90deg Panel to Panel Clamp',
							'size' => [
								'width' => 0
							]
						],
						[
							'slug' => 'PTPA',
							'type' => 'range_option',
							'key' => 'right_side',
							'image' => 'public/assets/img/clamps/swivel-clamps.jpg',
							'title' => 'Angled Panel to Panel Clamp',
							'size' => [
								'width' => 25
							]
						],
						[
							'slug' => 'PTW',
							'type' => 'range_option',
							'key' => 'right_side',
							'image' => 'public/assets/img/clamps/wall-clamp.jpg',
							'title' => 'Panel to wall clamp',
							'size' => [
								'width' => 25
							]
						]
					],
					'notes' => [
						'title' => '',
						'description' => ''
					]
				],
				[
					'title' => 'Post Options',
					'marker' => 'B',
					'slug' => 'post_option',
					'type' => 'image_option',
					'key' => 'right_side',
					'label' => '',
					'class' => 'd-hidden',
					'close_btn' => FALSE,
					'options' => [
						[
							'slug' => 'opt-1',
							'title' => 'Base Plated',
							'image' => 'public/assets/img/webp/bolt-down-w.png',
							'extra' => '',
							'key' => 'post_options',
							'default' => TRUE
						],
						[
							'slug' => 'opt-2',
							'title' => 'Core-Drilled<br>285mm',
							'image' => 'public/assets/img/webp/core-drill-w.png',
							'extra' => '',
							'key' => 'post_options'
						]
					]
				],
				[
					'title' => 'Add Step-Up Panel',
					'marker' => 'B',
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
						'image' => 'public/assets/img/raked-glass-right.jpg',
						'title' => 'When To Use Step-Up Panels',
						'description' => 'Step-Up panels are used when you need to change the heights or go over an object. e,g, over a retaining wall, over a few steps... against a boundary fence etc...'
					]
				]
			]
		]
	]
];
