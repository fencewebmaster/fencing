<?php
$fences['glass_pool'] = [
	'title' => 'Glass Pool',
	'live' => TRUE,
	'name' => 'Glass Pool',
	'slug' => 'glass_pool',
	'panel_group' => 'a',
	'image' => 'assets/img/fences/webp/glass-pool.webp',
	'panel_count' => 4,
	'color' => ['matt_black', 'polished_stainless_steel', 'satin_stainless_steel'],
	'offcut' => [
		'panel' => FALSE,
		'gate' => FALSE,
	],
	'settings' => [
		'rail_options' => [],
		'left_side'	=> [
			'title' => 'Edit Left Side',
			'label' => 'Left Side',
			'action' => ['edit'],
			'class' => '',
			'notes' => [
				'title' => 'When To Use Swivel Brackets',
				'description' => 'Swivel brackets are used instead of the standards straight brackets. This allow you to connect this fence section at an angle. e.g. 45degs to the connecting fence section',
			],
			'fields' => [
				[
					'title' => 'Edit Left Side',
					'marker' => 'A',
					'slug' => 'left_option',
					'type' => 'range_option',
					'label' => '',
					'close_btn' => true,
					'options' => [
						[
							'slug' => 'side-gap',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'assets/img/gap-img.png',
							'default' => TRUE,
							'title' => 'Gap',
							'size' => [
								'width' => 0,
							]
						],
						[
							'slug' => 'side-90deg-panel-clamp',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'assets/img/clamp-round.png',
							'title' => '90deg Panel to Panel Clamp',
							'size' => [
								'width' => 0,
							]
						],
						[
							'slug' => 'side-angled-panel-clamp',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'assets/img/clamp-round.png',
							'title' => 'Angled Panel to Panel Clamp',
							'size' => [
								'width' => 0,
							]
						],
						[
							'slug' => 'side-panel-wall-clamp',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'assets/img/clamp-round.png',
							'title' => 'Panel to wall clamp',
							'size' => [
								'width' => 0,
							]
						],
					],
					'notes' => [
						'title' => '',
						'description' => '',
					],
				],
				[
					'title' => 'Post Options',
					'marker' => 'B',
					'slug' => 'post_option',
					'type' => 'image_option',
					'key' => 'left_side',
					'label' => '',
					'class' => 'd-none',
					'close_btn' => false,
					'options' => [
						[
							'slug'  => 'opt-1',
							'title' => 'Bolt Down',
							'image' => 'assets/img/webp/bolt-down-w.png',
							'extra' => '',
							'key' => 'post_options',
							'default' => TRUE,
						],
						[
							'slug'  => 'opt-2',
							'title' => 'Core Drill',
							'image' => 'assets/img/webp/core-drill-w.png',
							'extra' => '',
							'key' => 'post_options',
						],
					],
				],
				[
					'title' => 'Add Step-Up Panel',
					'slug' => 'left_raked',
					'marker' => 'B',
					'type' => 'dropdown_option',
					'key' => 'add_step_up_panels',
					'label' => 'Left Hand Step-Up Panel',
					'close_btn' => false,
					'options' => [
						[
							'slug' => 'none',
							'title' => 'Nil',
							'size' => [
								'width' => 0,
								'height' => 0,
							]
						],
						[
							'slug' => '1300x300',
							'title' => '1300H - 300 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1300,
							]
						],
						[
							'slug' => '1400x400',
							'title' => '1400H - 400 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1400,
							]
						],
						[
							'slug' => '1500x500',
							'title' => '1500H - 500 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1500,
							]
						],
						[
							'slug' => '1600x600',
							'title' => '1600H - 600 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1600,
							]
						],
						[
							'slug' => '1700x700',
							'title' => '1700H - 700 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1700,
							]
						],
						[
							'slug' => '1800x600',
							'title' => '1800H - 600 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1800,
							]
						],
					],
					'notes' => [
						'image' => 'assets/img/webp/poolsafe-step-up-panel-v3.webp',
						'title' => 'When To Use Step-Up Panels',
						'description' => 'Step-Up panels are used when you need to change the heights or go over an object. e,g, over a retaining wall, over a few steps... against a boundary fence etc...',
					],
				],						
			]
		],
		'gate' => [
			'title' => 'Add / Remove Gate',
			'label' => 'Add Gate',
			'action' => ['default'],
			'custom' => TRUE,
			'size' => [
				'width' => 970 // + 50 + 20 + 20
			],
			'fields' => [
				[
					'slug' => 'move',
					'type' => 'move',
					'label' => '',
				],			
			]
		],
		'edit_spacing'	=> [
			'title' => 'Max Panel Spacing',
			'label' => 'Edit Spacing',
			'action' => ['default'],
			'fields' => [
				[
					'title' => 'Max Panel Spacing',
					'image' => 'assets/img/center-gap.png',
					'slug' => 'panel_option',
					'type' => 'range_icon',
					'label' => '',
					'unit' => 'mm',
					'min' => 5,
					'max' => 100,
					'step' => 5,
					'default' => 50
				]
			]
		],
		'panel_options_custom'	=> [
			'title' => 'Panel Options',
			'label' => 'Panel Options',
			'action' => ['default'],
			'fields' => [
				[
					'title' => 'Maximum Panel Size',
					'slug' => 'panel_option',
					'type' => 'range_sub',
					'label' => '',
					'unit' => 'mm',
					'min' => 1000,
					'max' => 2000,
					'step' => 50,
					'default' => 2000,
					'weight' => [
						'default' => 72,
						'unit' => 'kg',
					]
				]
			]
		],
		'panel_options'	=> [
			'title' => 'Panel Options',
			'label' => 'Panel Options',
			'action' => ['default'],
			'class' => 'd-none',
			'notes' => [
				'title' => 'Panel Off-Cuts',
				'description' => 'The off-cut can be used for another fence section (where applicable). If the off-cut is used ensure you manually update the panel quantities to account for this as this planner does NOT use Off-Cuts.',
			],
			'info' => [
				[
					'title' => 'Even Size Panels',
					'description' => 'This option evenly spaces out the posts, which also means you will need to cut down every individual panel.',
				],
				[
					'title' => 'Full Size 2400W / 3000W Panels:',
					'description' => 'This option uses full length panels, which means you will ONLY need to cut down 1x panel. ',
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
							'slug' => 'full+3000',
							'type' => 'text_option',
							'title' => 'Full Size Panels 3000W',
							'desc' => 'ONLY Available In BLACK',
							'default' => TRUE,
							'size' => [
								'width' => 2000 + 50,
								'default' => 2000,
							]
						]
					],
					'notes' => [
						'title' => 'Panel Off-Cuts',
						'description' => 'The off-cut can be used for another fence section (where applicable). If the off-cut is used ensure you manually update the panel quantities to account for this as this planner does NOT use Off-Cuts.',
					],
					'info' => [
						[
							'title' => 'Even Size Panels',
							'description' => 'This option evenly spaces out the posts, which also means you will need to cut down every individual panel.',
						],
						[
							'title' => 'Use 2400W / 3000W Panels',
							'description' => 'This option uses full length panels, which means you will ONLY need to cut down 1x panel. ',
						]
					],
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
					'options' => [
						[
							'slug'  => 'opt-1',
							'title' => 'Bolt Down',
							'image' => 'assets/img/webp/bolt-down-w.png',
							'extra' => '',
							'key' => 'post_options',
							'default' => TRUE,
						],
						[
							'slug'  => 'opt-2',
							'title' => 'Core Drill',
							'image' => 'assets/img/webp/core-drill-w.png',
							'extra' => '',
							'key' => 'post_options',
						],
						[
							'slug'  => 'opt-3',
							'title' => 'Core-Drill Extended',
							'image' => 'assets/img/webp/core-drill-w.png',
							'extra' => '',
							'key' => 'post_options',
						],
					]
				]									
			]
		],
		'right_side'	=> [
			'title' => 'Edit Right Side',
			'label' => 'Right Side',
			'action' => ['edit'],
			'notes' => [
				'title' => 'When To Use Swivel Brackets',
				'description' => 'Swivel brackets are used instead of the standards straight brackets. This allow you to connect this fence section at an angle. e.g. 45degs to the connecting fence section',
			],
			'fields' => [
				[
					'title' => 'Edit Right Side',
					'marker' => 'A',
					'slug' => 'right_option',
					'type' => 'range_option',
					'label' => '',
					'close_btn' => true,
					'options' => [
						[
							'slug' => 'side-gap',
							'type' => 'range_option',
							'key' => 'right_side',
							'image' => 'assets/img/gap-img.png',
							'default' => TRUE,
							'title' => 'Gap',
							'size' => [
								'width' => 0,
							]
						],
						[
							'slug' => 'side-90deg-panel-clamp',
							'type' => 'range_option',
							'key' => 'right_side',
							'image' => 'assets/img/clamp-round.png',
							'title' => '90deg Panel to Panel Clamp',
							'size' => [
								'width' => 0,
							]
						],
						[
							'slug' => 'side-angled-panel-clamp',
							'type' => 'range_option',
							'key' => 'right_side',
							'image' => 'assets/img/clamp-round.png',
							'title' => 'Angled Panel to Panel Clamp',
							'size' => [
								'width' => 0,
							]
						],
						[
							'slug' => 'side-panel-wall-clamp',
							'type' => 'range_option',
							'key' => 'right_side',
							'image' => 'assets/img/clamp-round.png',
							'title' => 'Panel to wall clamp',
							'size' => [
								'width' => 0,
							]
						],
					],
					'notes' => [
						'title' => '',
						'description' => '',
					],
				],
				[
					'title' => 'Post Options',
					'marker' => 'B',
					'slug' => 'post_option',
					'type' => 'image_option',
					'key' => 'left_side',
					'label' => '',
					'class' => 'd-none',
					'close_btn' => false,
					'options' => [
						[
							'slug'  => 'opt-1',
							'title' => 'Bolt Down',
							'image' => 'assets/img/webp/bolt-down-w.png',
							'extra' => '',
							'key' => 'post_options',
							'default' => TRUE,
						],
						[
							'slug'  => 'opt-2',
							'title' => 'Core Drill',
							'image' => 'assets/img/webp/core-drill-w.png',
							'extra' => '',
							'key' => 'post_options',
						],
					]
				],
				[
					'title' => 'Add Step-Up Panel',
					'marker' => 'B',
					'slug' => 'right_raked',
					'type' => 'dropdown_option',
					'key' => 'add_step_up_panels',
					'label' => 'Right Hand Step-Up Panel',
					'close_btn' => false,
					'options' => [
						[
							'slug' => 'none',
							'title' => 'Nil',
							'size' => [
								'width' => 0,
								'height' => 0,
							]
						],
						[
							'slug' => '1300x300',
							'title' => '1300H - 300 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1300,
							]
						],
						[
							'slug' => '1400x400',
							'title' => '1400H - 400 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1400,
							]
						],
						[
							'slug' => '1500x500',
							'title' => '1500H - 500 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1500,
							]
						],
						[
							'slug' => '1600x600',
							'title' => '1600H - 600 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1600,
							]
						],
						[
							'slug' => '1700x700',
							'title' => '1700H - 700 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1700,
							]
						],
						[
							'slug' => '1800x600',
							'title' => '1800H - 600 Step-Up',
							'size' => [
								'width' => 1250,
								'height' => 1800,
							]
						],
					],
					'notes' => [
						'image' => 'assets/img/webp/poolsafe-step-up-panel-v3.webp',
						'title' => 'When To Use Step-Up Panels',
						'description' => 'Step-Up panels are used when you need to change the heights or go over an object. e,g, over a retaining wall, over a few steps... against a boundary fence etc...',
					],
				],						
			]
		],
	]
];