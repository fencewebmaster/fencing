<?php
$fences['barr'] = [
	'title' => 'Barr',
	'live' => FALSE,
	'name' => 'BARR',
	'slug' => 'barr',
	'panel_group' => 'b',
	'image' => 'img/fences/barr.png',
	'panel_count' => 4,
	'color' => ['black', 'white'],
	'form' => [
		[
			'slug' => 'fence_height',
			'title' => 'Fence Height',
			'target' => '.step-2_field',
			'type' => 'select-field',
			'option' => [
				1000 => '1000mm',
				1200 => '1200mm',
				1800 => '1800mm',
			], 
			'default' => 1200,
		],
		[
			'slug' => 'important_note',
			'title' => 'Fence Height',
			'description' => '<span class="mb-1 d-block">To meet pool compliance your fence height MUST BE above 1200H</span>',
			'target' => '.step-2_notes',
			'type' => 'important-note',
		]
	],
	'settings' => [
		'rail_options' => [],
		'left_side'	=> [
			'title' => 'Edit Left Side',
			'label' => 'Left Side',
			'action' => ['edit'],
			'notes' => [
				'title' => 'When To Use Swivel Brackets',
				'description' => 'Swivel brackets are used instead of the standards straight brackets. This allow you to connect this fence section at an angle. e.g. 45degs to the connecting fence section',
			],
			'fields' => [
				[
					'title' => 'Edit Left Side',
					'marker' => 'A',
					'class' => 'd-none',
					'slug' => 'left_option',
					'type' => 'range_option',
					'label' => '',
					'close_btn' => true,
					'options' => [
						[
							'slug' => 'yes-post',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'img/yes-post.png',
							'default' => TRUE,
							'title' => '',
							'size' => [
								'width' => 0,
							]
						],
						[
							'slug' => 'no-post',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'img/no-post-1.png',
							'title' => '',
							'size' => [
								'width' => -50,
							]
						],
						[
							'slug' => 'no-post-swivel-bracket',
							'type' => 'range_option',
							'key' => 'left_side',
							'image' => 'img/no-post-2.png',
							'title' => '',
							'size' => [
								'width' => -50,
							]
						],
					],
					'notes' => [
						'title' => 'When To Use Swivel Brackets',
						'description' => 'Swivel brackets are used instead of the standards straight brackets. This allow you to connect this fence section at an angle. e.g. 45degs to the connecting fence section',
					],
				],
				[
					'title' => 'Post Options',
					'marker' => 'A',
					'slug' => 'post_option',
					'type' => 'image_option',
					'key' => 'left_side',
					'label' => '',
					'close_btn' => false,
					'notes' => [
						'title' => '1200mm',
						'description' => '
							Base Plated Posts = 50x50mm Posts<br>
							Cemented In Posts = 50x25mm Posts<br>
							*Due to the 1800H height for Base Plated posts we need to use 50x50mm Base Plated Posts',
					],
					'options' => [
						[
							'slug'  => 'opt-1',
							'title' => '',
							'image' => 'img/base-plate-posts.png',
							'extra' => '',
							'key' => 'post_options',
						],
						[
							'slug'  => 'opt-2',
							'title' => '',
							'image' => 'img/cement-in-posts.png',
							'extra' => '',
							'key' => 'post_options',
							'default' => TRUE,
						],
					],
				],
				[
					'title' => 'Add Step-Up Panel',
					'slug' => 'left_raked',
					'marker' => 'C',
					'class' => 'd-none',
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
						'image' => 'img/poolsafe-step-up panel-v3.jpg',
						'title' => 'When To Use Step-Up Panels',
						'description' => 'Step-Up panels are used when you need to change the heights or go over an object. e,g, over a retaining wall, over a few steps... against a boundary fence etc...',
					],
				],						
			]
		],
		'gate' => [
			'title' => 'Add / Remove Gate',
			'label' => 'Gate',
			'action' => ['add', 'edit'],
			'custom' => TRUE,
			'size' => [
				'width' => 975
			],
			'fields' => [
				[
					'slug' => 'move',
					'type' => 'move',
					'label' => '',
				],			
			]
		],
		'panel_options'	=> [
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
								'width' => 2205 + 50,
								'width_based_height' => [
									1000 => 1733 + 50,
									1200 => 2205 + 50,
									1800 => 1969 + 50,
								],
							]
						],
						[
							'slug' => 'full',
							'type' => 'text_option',
							'title' => 'Full Size Panels',
							'size' => [
								'width' => 2205 + 50,
								'width_based_height' => [
									1000 => 1733 + 50,
									1200 => 2205 + 50,
									1800 => 1969 + 50,
								],
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
						]
					],
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
							*Due to the 1800H height for Base Plated posts we need to use 50x50mm Base Plated Posts',
					],
					'options' => [
						[
							'slug'  => 'opt-1',
							'title' => '',
							'image' => 'img/base-plate-posts.png',
							'extra' => '',
						],
						[
							'slug'  => 'opt-2',
							'title' => '',
							'image' => 'img/cement-in-posts.png',
							'extra' => '',
							'default' => TRUE,
						],
					]
				]									
			]
		],
		'right_side'	=> [
			'title' => 'Edit Right Side',
			'label' => 'Right Side',
			'action' => ['edit'],
			'fields' => [
				[
					'title' => 'Edit Right Side',
					'marker' => 'A',
					'class' => 'd-none',
					'slug' => 'right_option',
					'type' => 'range_option',
					'label' => '',
					'close_btn' => true,
					'options' => [
						[
							'slug' => 'yes-post',
							'type' => 'range_option',
							'key' => 'right_side',
							'image' => 'img/yes-post.png',
							'default' => TRUE,
							'title' => '',
							'size' => [
								'width' => 0,
							]
						]
					]
				],
				[
					'title' => 'Post Options',
					'marker' => 'A',
					'slug' => 'post_option',
					'type' => 'image_option',
					'key' => 'left_side',
					'label' => '',
					'close_btn' => false,
					'notes' => [
						'title' => '1200mm',
						'description' => '
							Base Plated Posts = 50x50mm Posts<br>
							Cemented In Posts = 50x25mm Posts<br>
							*Due to the 1800H height for Base Plated posts we need to use 50x50mm Base Plated Posts',
					],
					'options' => [
						[
							'slug'  => 'opt-1',
							'title' => '',
							'image' => 'img/base-plate-posts.png',
							'extra' => '',
							'key' => 'post_options',
						],
						[
							'slug'  => 'opt-2',
							'title' => '',
							'image' => 'img/cement-in-posts.png',
							'extra' => '',
							'key' => 'post_options',
							'default' => TRUE,
						],
					]
				],
				[
					'title' => 'Add Step-Up Panel',
					'marker' => 'C',
					'class' => 'd-none',
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
						'image' => 'img/poolsafe-step-up panel-v3.jpg',
						'title' => 'When To Use Step-Up Panels',
						'description' => 'Step-Up panels are used when you need to change the heights or go ever an object. e,g, over a retaining wall, over a few steps... against a boundary fence etc...',
					],
				],						
			]
		],
	]
];