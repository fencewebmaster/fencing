<?php
$fences = [
	[
		'title' => 'Frameless Pool Fence',
		'name' => 'FRAMELESS',
		'slug' => 'frameless',
		'panel_group' => 'a',
		'image' => 'img/frameless-pool-fence.jpg',
		'panel_count' => 5,
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
						'slug' => 'post_option',
						'type' => 'image_option',
						'key' => 'left_side',
						'label' => '',
						'close_btn' => false,
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => '',
								'image' => 'img/base-plate-posts.png',
								'extra' => '',
								'key' => 'post_options',
								'default' => TRUE,
							],
							[
								'slug'  => 'opt-2',
								'title' => '',
								'image' => 'img/cement-in-posts.png',
								'extra' => '',
								'key' => 'post_options',
							],
						]
					],
					[
						'title' => 'Add Step-Up Panel',
						'slug' => 'left_raked',
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
						]
					],						
				]
			],
			'panel_options'	=> [
				'title' => 'Panel Options',
				'label' => 'Panel Options',
				'action' => ['default'],
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
									'width' => 2450,
								]
							],
							[
								'slug' => 'full_2400',
								'type' => 'text_option',
								'title' => 'Use 2400W Panels',
								'size' => [
									'width' => 2450,
								]
							],
							[
								'slug' => 'full_3000',
								'type' => 'text_option',
								'title' => 'Use 3000W Panels',
								'size' => [
									'width' => 3050,
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
				'label' => 'Post Options',
				'action' => ['default'],
				'fields' => [
					[
						'title' => 'Post Options',
						'slug' => 'post_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => '',
								'image' => 'img/base-plate-posts.png',
								'extra' => '',
								'default' => TRUE,
							],
							[
								'slug'  => 'opt-2',
								'title' => '',
								'image' => 'img/cement-in-posts.png',
								'extra' => '',
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
							],
							[
								'slug' => 'no-post',
								'type' => 'range_option',
								'key' => 'right_side',
								'image' => 'img/no-post-1.png',
								'title' => '',
								'size' => [
									'width' => -50,
								]
							],
							[
								'slug' => 'no-post-swivel-bracket',
								'type' => 'range_option',
								'key' => 'right_side',
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
						'slug' => 'post_option',
						'type' => 'image_option',
						'key' => 'left_side',
						'label' => '',
						'close_btn' => false,
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => '',
								'image' => 'img/base-plate-posts.png',
								'extra' => '',
								'key' => 'post_options',
								'default' => TRUE,
							],
							[
								'slug'  => 'opt-2',
								'title' => '',
								'image' => 'img/cement-in-posts.png',
								'extra' => '',
								'key' => 'post_options',
							],
						]
					],
					[
						'title' => 'Add Step-Up Panel',
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
						]
					],						
				]
			],
			'gate' => [
				'title' => 'Add / Remove Gate',
				'label' => 'Gate',
				'action' => ['add', 'edit'],
				'size' => [
					'width' => 1060
				],
				'fields' => [
					[
						'slug' => 'move',
						'type' => 'move',
						'label' => '',
					],			
				]
			],
			/**
			 * @TODO - re-check on how to disable from the settings
			 */
			'add_step_up_panels' => [
				'title' => 'Add Step-Up Panel',
				'label' => 'Step-Up Panels',
				'disabled' => true,
				'action' => ['add'],
				'fields' => [
					[
						'title' => 'Left Raked',
						'slug' => 'left_raked',
						'type' => 'dropdown_option',
						'label' => 'Left Hand Step-Up Panel',
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
						]
					],					
					[
						'title' => 'Right Raked',
						'slug' => 'right_raked',
						'type' => 'dropdown_option',
						'label' => 'Right Hand Step-Up Panel',
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
						]
					],	
				]
			],
		]
	],
	[
		'title' => 'Aluminium',
		'name' => 'ALUMINUM',
		'slug' => 'aluminum',
		'panel_group' => 'b',
		'image' => 'img/aluminium.jpg',
		'panel_count' => 4,
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
						'slug' => 'post_option',
						'type' => 'image_option',
						'key' => 'left_side',
						'label' => '',
						'close_btn' => false,
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => '',
								'image' => 'img/base-plate-posts.png',
								'extra' => '',
								'key' => 'post_options',
								'default' => TRUE,
							],
							[
								'slug'  => 'opt-2',
								'title' => '',
								'image' => 'img/cement-in-posts.png',
								'extra' => '',
								'key' => 'post_options',
							],
						]
					],
					[
						'title' => 'Add Step-Up Panel',
						'slug' => 'left_raked',
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
						]
					],						
				]
			],
			'panel_options'	=> [
				'title' => 'Panel Options',
				'label' => 'Panel Options',
				'action' => ['default'],
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
									'width' => 2450,
								]
							],
							[
								'slug' => 'full_2400',
								'type' => 'text_option',
								'title' => 'Use 2400W Panels',
								'size' => [
									'width' => 2450,
								]
							],
							[
								'slug' => 'full_3000',
								'type' => 'text_option',
								'title' => 'Use 3000W Panels',
								'size' => [
									'width' => 3050,
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
				'label' => 'Post Options',
				'action' => ['default'],
				'fields' => [
					[
						'title' => 'Post Options',
						'slug' => 'post_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => '',
								'image' => 'img/base-plate-posts.png',
								'extra' => '',
								'default' => TRUE,
							],
							[
								'slug'  => 'opt-2',
								'title' => '',
								'image' => 'img/cement-in-posts.png',
								'extra' => '',
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
							],
							[
								'slug' => 'no-post',
								'type' => 'range_option',
								'key' => 'right_side',
								'image' => 'img/no-post-1.png',
								'title' => '',
								'size' => [
									'width' => -50,
								]
							],
							[
								'slug' => 'no-post-swivel-bracket',
								'type' => 'range_option',
								'key' => 'right_side',
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
						'slug' => 'post_option',
						'type' => 'image_option',
						'key' => 'left_side',
						'label' => '',
						'close_btn' => false,
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => '',
								'image' => 'img/base-plate-posts.png',
								'extra' => '',
								'key' => 'post_options',
								'default' => TRUE,
							],
							[
								'slug'  => 'opt-2',
								'title' => '',
								'image' => 'img/cement-in-posts.png',
								'extra' => '',
								'key' => 'post_options',
							],
						]
					],
					[
						'title' => 'Add Step-Up Panel',
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
						]
					],						
				]
			],
			'gate' => [
				'title' => 'Add / Remove Gate',
				'label' => 'Gate',
				'action' => ['add', 'edit'],
				'size' => [
					'width' => 1060
				],
				'fields' => [
					[
						'slug' => 'move',
						'type' => 'move',
						'label' => '',
					],			
				]
			],
			/**
			 * @TODO - re-check on how to disable from the settings
			 */
			'add_step_up_panels' => [
				'title' => 'Add Step-Up Panel',
				'label' => 'Step-Up Panels',
				'disabled' => true,
				'action' => ['add'],
				'fields' => [
					[
						'title' => 'Left Raked',
						'slug' => 'left_raked',
						'type' => 'dropdown_option',
						'label' => 'Left Hand Step-Up Panel',
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
						]
					],					
					[
						'title' => 'Right Raked',
						'slug' => 'right_raked',
						'type' => 'dropdown_option',
						'label' => 'Right Hand Step-Up Panel',
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
						]
					],	
				]
			],
		]
	]
];


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
	];

	return ($val) ? $data[$val] : $data;
} 

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

function fc_timeframe($val ='') {
 
	$data = [
		'asap'    => 'ASAP - Within 24hrs',
		'soon'    => 'SOON - This Week',
		'later'   => 'LATER - This Month',
		'looking' => 'NIL - Just Looking',
	];

	return ($val) ? $data[$val] : $data;
} 

function fc_installer($val ='') {
 
	$data = [
		'yes' => 'YES - I need an installer',
		'no' => 'NO - I can install it myself',
	];

	return ($val) ? $data[$val] : $data;
} 

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
        return 'Not Found';
    }

} 



$products = [
	[
		'sku' => '',
		'price' => '',
		'name' => 'Panel - 1200H x 2400W',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Panel - 1200H x 3000W',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Panel Raked - 1300H x 1200W',	
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Panel Raked - 1400H x 1200W',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Panel Raked - 1500H x 1200W',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Panel Raked - 1600H x 1200W',	
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Panel Raked - 1700H x 1200W',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Panel Raked - 1800H x 1200W',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Gate - 1200H x 970W',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Gate Converter - 1200H',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Post - 1300L - Base Plated',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Post - 1800L - Cemented In',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Post - 2100L - Base Plated',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Post - 2400L - Cemented In',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Post Covers',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Brackets Set x4',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Flexi Bracket x1',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Hinge & Latch Kit',
	],
	[
		'sku' => '',
		'price' => '',
		'name' => 'Fixing Kit - Dynabolts',
	]
];