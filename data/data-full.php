<?php
$fences = [
	[
		'title' => 'Frameless Pool Fence',
		'slug' => 'FRAMELESS',
		'image' => 'img/frameless-pool-fence.jpg',
		'settings' => [
			'gate' => [
				'title' => 'Options',
				'label' => 'Gate',
				'action' => ['add', 'edit'],
				'fields' => [
					[
						'slug' => 'move',
						'type' => 'move',
						'label' => '',
					],
					[
						'slug' => 'gate_size',
						'type' => 'range',
						'label' => 'Gate Size',
						'unit' => 'mm',
						'min' => 700,
						'max' => 1000,
						'default' => 800
					],
					[
						'slug' => 'hinge',
						'type' => 'range',
						'label' => 'Hinge Panel Size',
						'unit' => 'mm',
						'min' => 900,
						'max' => 1800,
						'default' => 1800
					]			
				]
			],
			'left_side'	=> [
				'title' => 'Edge to Left Centre Point',
				'label' => 'Left Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'left_slider',
						'type' => 'range_icon',
						'label' => 'Left Side',
						'image' => 'img/control-left.png',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'left_option',
						'type' => 'range_option',
						'label' => 'Left Side',
						'options' => [
							[
								'slug' => 'gap',
								'type' => 'range_option',
								'image' => 'img/gap-img.png',
								'title' => 'Gap',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp-round.png',
								'title' => 'Clamp',
								'unit' => 'mm',
								'min' => 15,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			],
			'panel_options'	=> [
				'title' => 'Maximum Panel Size',
				'label' => 'Panel Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'panel_size',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 1000,
						'max' => 2000,
						'default' => 2000,
						'weight' => [
							'unit' => 'kg',
							'min' => 36,
							'max' => 72,
						]
					]
				]
			],
			'rail_options' => [],
			'spacing'	=> [
				'title' => 'Centre-to-Centre',
				'label' => 'Spacing',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'center_spacing',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 100,
						'default' => 50
					]
				]
			],
			'post_options' => [
				'title' => 'Options',
				'label' => 'Post Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'post_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'Black bolt-down',
								'image' => 'img/NFSB-SF-BLACK.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-2',
								'title' => 'Black core-drill',
								'image' => 'img/NFSP-SC-BLACK.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-3',
								'title' => 'S/S square bolt-down',
								'image' => 'img/NFSP-SF-P.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-4',
								'title' => 'S/S square core-drill',
								'image' => 'img/NFSP-SC-P.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-5',
								'title' => 'S/S round bolt-down',
								'image' => 'img/NFSP-RF-P.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-6',
								'title' => 'S/S round core-drill',
								'image' => 'img/NFSP-RC-P.jpg',
								'extra' => '',
							],
						]
					]									
				]
			],
			'right_side'	=> [
				'title' => 'Edge to Right Centre Point',
				'label' => 'Right Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'right_slider',
						'type' => 'range_icon',
						'image' => 'img/control-right.png',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'right_option',
						'type' => 'range_option',
						'label' => 'Right Side',
						'options' => [
							[
								'slug' => 'gap',
								'type' => 'range_option',
								'image' => 'img/gap-img.png',
								'title' => 'Gap',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp-round.png',
								'title' => 'Clamp',
								'unit' => 'mm',
								'min' => 15,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			],
		]
	],
	[
		'title' => 'Pin-Fixed Pool Fence',
		'slug' => 'PINFIXPOOL',
		'image' => 'img/pin-fixed-pool-fence.jpg',
		'settings' => [
			'gate' => [],
			'rail_options' => [],
			'left_side'	=> [
				'title' => 'Edge to Left Centre Point',
				'label' => 'Left Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'left_slider',
						'type' => 'range_icon',
						'label' => 'Left Side',
						'image' => 'img/control-left.png',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'left_option',
						'type' => 'range_option',
						'label' => 'Left Side',
						'options' => [
							[
								'slug' => 'gap',
								'type' => 'range_option',
								'image' => 'img/gap-img.png',
								'title' => 'Gap',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp-round.png',
								'title' => 'Clamp',
								'unit' => 'mm',
								'min' => 15,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			],
			'panel_options'	=> [
				'title' => 'Maximum Panel Size',
				'label' => 'Panel Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'panel_option',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 1000,
						'max' => 2000,
						'default' => 2000,
						'weight' => [
							'unit' => 'kg',
							'min' => 36,
							'max' => 72,
						]
					]
				]
			],
			'spacing'	=> [
				'title' => 'Centre-to-Centre',
				'label' => 'Spacing',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'center_spacing',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 100,
						'default' => 50
					]
				]
			],
			'post_options' => [
				'title' => 'Options',
				'label' => 'Post Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'post_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'Black 38x30',
								'image' => 'img/SOP38x30-BLACK.jpg',
								'extra' => '+$1.76',
							],
							[
								'slug'  => 'opt-2',
								'title' => 'S/S 38x30',
								'image' => 'img/SOP38x30.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-3',
								'title' => 'S/S 38x40',
								'image' => 'img/SOP38x40.jpg',
								'extra' => '+$0.30',
							],
							[
								'slug'  => 'opt-4',
								'title' => 'S/S 38x50',
								'image' => 'img/SOP38x50.jpg',
								'extra' => '-$2.38',
							],
							[
								'slug'  => 'opt-5',
								'title' => 'Black 50x30',
								'image' => 'img/SOP50X30-BL.jpg',
								'extra' => '+$0.41',
							],
							[
								'slug'  => 'opt-6',
								'title' => 'S/S 50x30',
								'image' => 'img/SOP50X30.jpg',
								'extra' => '+$0.80',
							],
						]
					]									
				]
			],
			'right_side'	=> [
				'title' => 'Edge to Right Centre Point',
				'label' => 'Right Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'right_slider',
						'type' => 'range_icon',
						'image' => 'img/control-right.png',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'right_option',
						'type' => 'range_option',
						'label' => 'Right Side',
						'options' => [
							[
								'slug' => 'gap',
								'type' => 'range_option',
								'image' => 'img/gap-img.png',
								'title' => 'Gap',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp-round.png',
								'title' => 'Clamp',
								'unit' => 'mm',
								'min' => 15,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			]
		]
	],
	[
		'title' => 'Face-Mount Pool Fence',
		'slug' => 'FACE',
		'image' => 'img/Face-Mount-Pool-Fence.jpg',
		'settings' => [
			'gate' => [
				'title' => 'Options',
				'label' => 'Gate',
				'action' => ['add', 'edit'],
				'fields' => [
					[
						'slug' => 'move',
						'type' => 'move',
						'label' => '',
					],
					[
						'slug' => 'gate_size',
						'type' => 'range',
						'label' => 'Gate Size',
						'unit' => 'mm',
						'min' => 700,
						'max' => 1000,
						'default' => 800
					],
					[
						'slug' => 'hinge',
						'type' => 'range',
						'label' => 'Hinge Panel Size',
						'unit' => 'mm',
						'min' => 900,
						'max' => 1800,
						'default' => 1800
					],					
				]
			],
			'left_side'	=> [
				'title' => 'Edge to Left Centre Point',
				'label' => 'Left Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'left_slider',
						'type' => 'range_icon',
						'label' => 'Left Side',
						'image' => 'img/control-left.png',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'left_option',
						'type' => 'range_option',
						'label' => 'Left Side',
						'options' => [
							[
								'slug' => '1_way',
								'type' => 'range_option',
								'image' => 'img/1way.png',
								'title' => '1 Way',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 15
							],
							[
								'slug' => '90_degree',
								'type' => 'range_option',
								'image' => 'img/90deg.png',
								'title' => '90 Degre',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 10
							],
							[
								'slug' => '135_degree',
								'type' => 'range_option',
								'image' => 'img/135deg.png',
								'title' => '135 Degre',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 45.8
							],
							[
								'slug' => 'wall',
								'type' => 'range_option',
								'image' => 'img/wall.png',
								'title' => 'Wall',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 30
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp.png',
								'title' => 'Clamp',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 15
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none.png',
								'title' => 'None',
							],
						]
					]				
				]
			],
			'rail_options' => [],
			'panel_options'	=> [
				'title' => 'Maximum Panel Size',
				'label' => 'Panel Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'panel_size',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 800,
						'max' => 1600,
						'default' => 1600,
						'weight' => [
							'unit' => 'kg',
							'min' => 33.1,
							'max' => 66.2,
						]
					]
				]
			],
			'spacing'	=> [
				'title' => 'Centre-to-Centre',
				'label' => 'Spacing',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'center_spacing',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 3,
						'max' => 93,
						'default' => 43
					]
				]
			],
			'post_options' => [
				'title' => 'Options',
				'label' => 'Post Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'post_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'Black face-mount',
								'image' => 'img/NFSB-VERT-BLACK.png',
								'extra' => '$3.14',
							],
							[
								'slug'  => 'opt-2',
								'title' => 'Polished face-mount',
								'image' => 'img/NFSB-VERT.png',
								'extra' => '$3.14',
							]
						]
					]									
				]
			],
			'right_side'	=> [
				'title' => 'Edge to right Centre Point',
				'label' => 'Right Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'right_slider',
						'type' => 'range_icon',
						'label' => 'Right Side',
						'image' => 'img/control-right.png',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'right_option',
						'type' => 'range_option',
						'label' => 'Right Side',
						'options' => [
							[
								'slug' => '1_way',
								'type' => 'range_option',
								'image' => 'img/1way.png',
								'title' => '1 Way',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 15
							],
							[
								'slug' => '90_degree',
								'type' => 'range_option',
								'image' => 'img/90deg.png',
								'title' => '90 Degre',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 10
							],
							[
								'slug' => '135_degree',
								'type' => 'range_option',
								'image' => 'img/135deg.png',
								'title' => '135 Degre',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 45.8
							],
							[
								'slug' => 'wall',
								'type' => 'range_option',
								'image' => 'img/wall.png',
								'title' => 'Wall',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 30
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp.png',
								'title' => 'Clamp',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 15
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none.png',
								'title' => 'None',
							],
						]
					]				
				]
			],
		]
	],
	[
		'title' => 'Balustrade',
		'slug' => 'BALUSTRADE',
		'image' => 'img/balustrade.jpg',
		'settings' => [
			'gate' => [],
			'rail_options' => [
				'title' => 'Options',
				'label' => 'Rail Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'rail_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'No rail',
								'image' => 'img/none.jpg',
								'extra' => '-$26.07/m',
							],
							[
								'slug'  => 'opt-2',
								'title' => '21x25 Cap',
								'image' => 'img/21X25CAPX5.8.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-3',
								'title' => '21x25 Black',
								'image' => 'img/21X25CAPX5.8BLACK.jpg',
								'extra' => '+$4.15/m',
							],
							[
								'slug'  => 'opt-3',
								'title' => '25.4 Cap',
								'image' => 'img/25.4CAPX5.8.jpg',
								'extra' => '-$0.00/m',
							],
							[
								'slug'  => 'opt-4',
								'title' => '50x10 Handrail',
								'image' => 'img/50x10HRPSx5.8M.jpg',
								'extra' => '+$31.77/m',
							]
						]
					]
				]
			],
			'left_side'	=> [
				'title' => 'Edge to Left Centre Point',
				'label' => 'Left Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'left_slider',
						'type' => 'range_icon',
						'label' => 'Left Side',
						'image' => 'img/control-left.png',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'left_option',
						'type' => 'range_option',
						'label' => 'Left Side',
						'options' => [
							[
								'slug' => 'gap',
								'type' => 'range_option',
								'image' => 'img/gap-img.png',
								'title' => 'Gap',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp-round.png',
								'title' => 'Clamp',
								'unit' => 'mm',
								'min' => 15,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			],

			'panel_options'	=> [
				'title' => 'Maximum Panel Size',
				'label' => 'Panel Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'panel_size',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 1000,
						'max' => 2000,
						'default' => 2000,
						'weight' => [
							'unit' => 'kg',
							'min' => 36,
							'max' => 72,
						]
					]
				]
			],
			'spacing'	=> [
				'title' => 'Centre-to-Centre',
				'label' => 'Spacing',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'center_spacing',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 100,
						'default' => 42.9
					]
				]
			],
			'post_options' => [
				'title' => 'Options',
				'label' => 'Post Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'post_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'Black bolt-down',
								'image' => 'img/NFSB-SF-BLACK.jpg',
								'extra' => '+$11.55',
							],
							[
								'slug'  => 'opt-2',
								'title' => 'Black core-drill',
								'image' => 'img/NFSP-SC-BLACK.jpg',
								'extra' => '-$6.60',
							],
							[
								'slug'  => 'opt-3',
								'title' => 'S/S square bolt-down',
								'image' => 'img/NFSP-SF-P.jpg',
								'extra' => '+$1.65',
							],
							[
								'slug'  => 'opt-4',
								'title' => 'S/S square core-drill',
								'image' => 'img/NFSP-SC-P.jpg',
								'extra' => '+$0.55',
							],
							[
								'slug'  => 'opt-5',
								'title' => 'S/S round bolt-down',
								'image' => 'img/NFSP-RF-P.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-6',
								'title' => 'S/S round core-drill',
								'image' => 'img/NFSP-RC-P.jpg',
								'extra' => '-$1.37',
							]
						]
					]
				]
			],
			'right_side'	=> [
				'title' => 'Edge to Right Centre Point',
				'label' => 'Right Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'right_slider',
						'type' => 'range_icon',
						'image' => 'img/control-right.png',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'right_option',
						'type' => 'range_option',
						'label' => 'Right Side',
						'options' => [
							[
								'slug' => 'gap',
								'type' => 'range_option',
								'image' => 'img/gap-img.png',
								'title' => 'Gap',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp-round.png',
								'title' => 'Clamp',
								'unit' => 'mm',
								'min' => 15,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			],
		]
	],
	[
		'title' => 'Pin-Fixed Balustrade',
		'slug' => 'PINFIXBALUSTRADE',
		'image' => 'img/pin-fixed-balustrade.jpg',
		'settings' => [
			'gate' => [],
			'rail_options' => [
				'title' => 'Options',
				'label' => 'Rail Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'rail_options',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'No rail',
								'image' => 'img/none.jpg',
								'extra' => '-$26.07/m',
							],
							[
								'slug'  => 'opt-2',
								'title' => '21x25 Cap',
								'image' => 'img/21X25CAPX5.8.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-3',
								'title' => '21x25 Black',
								'image' => 'img/21X25CAPX5.8BLACK.jpg',
								'extra' => '+$4.15/m',
							],
							[
								'slug'  => 'opt-4',
								'title' => '25.4 Cap',
								'image' => 'img/25.4CAPX5.8.jpg',
								'extra' => '-$0.00/m',
							],
							[
								'slug'  => 'opt-5',
								'title' => '50x10 Handrail',
								'image' => 'img/50x10HRPSx5.8M.jpg',
								'extra' => '+$31.77/m',
							]
						]
					]
				]
			],
			'left_side'	=> [
				'title' => 'Edge to Left Centre Point',
				'label' => 'Left Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'left_slider',
						'type' => 'range_icon',
						'label' => 'Left Side',
						'image' => 'img/control-left.png',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'left_option',
						'type' => 'range_option',
						'label' => 'Left Side',
						'options' => [
							[
								'slug' => 'gap',
								'type' => 'range_option',
								'image' => 'img/gap-img.png',
								'title' => 'Gap',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp-round.png',
								'title' => 'Clamp',
								'unit' => 'mm',
								'min' => 15,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			],
			'panel_options'	=> [
				'title' => 'Maximum Panel Size',
				'label' => 'Panel Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'panel_size',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 1000,
						'max' => 1500,
						'default' => 2000,
						'weight' => [
							'unit' => 'kg',
							'min' => 36.9,
							'max' => 55.4,
						]
					]
				]
			],
			'spacing'	=> [
				'title' => 'Centre-to-Centre',
				'label' => 'Spacing',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'center_spacing',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 90.9,
						'default' => 40.9
					]
				]
			],
			'post_options' => [
				'title' => 'Options',
				'label' => 'Post Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'post_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'Black 38x30',
								'image' => 'img/SOP38x30-BLACK.jpg',
								'extra' => '+$1.76',
							],
							[
								'slug'  => 'opt-2',
								'title' => 'S/S 38x30',
								'image' => 'img/SOP38x30.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-3',
								'title' => 'S/S 38x40',
								'image' => 'img/SOP38x40.jpg',
								'extra' => '+$0.30',
							],
							[
								'slug'  => 'opt-4',
								'title' => 'S/S 38x50',
								'image' => 'img/SOP38x50.jpg',
								'extra' => '-$2.38',
							],
							[
								'slug'  => 'opt-5',
								'title' => 'Black 50x30',
								'image' => 'img/SOP50X30-BL.jpg',
								'extra' => '+$0.41',
							],
							[
								'slug'  => 'opt-6',
								'title' => 'S/S 50x30',
								'image' => 'img/SOP50X30.jpg',
								'extra' => '+$0.80',
							]
						]
					]
				]
			],
			'right_side'	=> [
				'title' => 'Edge to Right Centre Point',
				'label' => 'Right Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'right_slider',
						'type' => 'range_icon',
						'image' => 'img/control-right.png',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'right_option',
						'type' => 'range_option',
						'label' => 'Right Side',
						'options' => [
							[
								'slug' => 'gap',
								'type' => 'range_option',
								'image' => 'img/gap-img.png',
								'title' => 'Gap',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp-round.png',
								'title' => 'Clamp',
								'unit' => 'mm',
								'min' => 15,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			],
		]
	],
	[
		'title' => 'Face-Mount Balustrade',
		'slug' => 'FACEB',
		'image' => 'img/faced-fixed.jpg',
			'settings' => [
			'gate' => [],
			'rail_options' => [
				'title' => 'Options',
				'label' => 'Rail Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'rail_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'No rail',
								'image' => 'img/none.jpg',
								'extra' => '-$26.07/m',
							],
							[
								'slug'  => 'opt-2',
								'title' => '21x25 Cap',
								'image' => 'img/21X25CAPX5.8.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-3',
								'title' => '21x25 Black',
								'image' => 'img/21X25CAPX5.8BLACK.jpg',
								'extra' => '+$4.15/m',
							],
							[
								'slug'  => 'opt-4',
								'title' => '25.4 Cap',
								'image' => 'img/25.4CAPX5.8.jpg',
								'extra' => '-$0.00/m',
							],
							[
								'slug'  => 'opt-5',
								'title' => '50x10 Handrail',
								'image' => 'img/50x10HRPSx5.8M.jpg',
								'extra' => '+$31.77/m',
							]
						]
					]
				]
			],
			'left_side'	=> [
				'title' => 'Edge to Left Centre Point',
				'label' => 'Left Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'left_slider',
						'type' => 'range_icon',
						'label' => 'Left Side',
						'image' => 'img/control-left.png',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'left_option',
						'type' => 'range_option',
						'label' => 'Left Side',
						'options' => [
							[
								'slug' => '1_way',
								'type' => 'range_option',
								'image' => 'img/1way.png',
								'title' => '1 Way',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 15
							],
							[
								'slug' => '90_degree',
								'type' => 'range_option',
								'image' => 'img/90deg.png',
								'title' => '90 Degre',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 10
							],
							[
								'slug' => '135_degree',
								'type' => 'range_option',
								'image' => 'img/135deg.png',
								'title' => '135 Degre',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 45.8
							],
							[
								'slug' => 'wall',
								'type' => 'range_option',
								'image' => 'img/wall.png',
								'title' => 'Wall',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 30
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp.png',
								'title' => 'Clamp',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 15
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none.png',
								'title' => 'None',
							],
						]
					]				
				]
			],
			'panel_options'	=> [
				'title' => 'Maximum Panel Size',
				'label' => 'Panel Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'panel_size',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 1000,
						'max' => 1500,
						'default' => 2000,
						'weight' => [
							'unit' => 'kg',
							'min' => 36.9,
							'max' => 55.4,
						]
					]
				]
			],
			'spacing'	=> [
				'title' => 'Centre-to-Centre',
				'label' => 'Spacing',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'center_spacing',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 100,
						'default' => 43.8
					]
				]
			],
			'post_options' => [
				'title' => 'Options',
				'label' => 'Post Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'post_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'Black face-mount',
								'image' => 'img/NFSB-VERT-BLACK.png',
								'extra' => '+$3.14',
							],
							[
								'slug'  => 'opt-2',
								'title' => 'Polished face-mount',
								'image' => 'img/NFSB-VERT.png',
								'extra' => '-$3.14',
							]
						]
					]
				]
			],
			'right_side'	=> [
				'title' => 'Edge to right Centre Point',
				'label' => 'Right Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'right_slider',
						'type' => 'range_icon',
						'label' => 'Right Side',
						'image' => 'img/control-right.png',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'right_option',
						'type' => 'range_option',
						'label' => 'Right Side',
						'options' => [
							[
								'slug' => '1_way',
								'type' => 'range_option',
								'image' => 'img/1way.png',
								'title' => '1 Way',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 15
							],
							[
								'slug' => '90_degree',
								'type' => 'range_option',
								'image' => 'img/90deg.png',
								'title' => '90 Degre',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 10
							],
							[
								'slug' => '135_degree',
								'type' => 'range_option',
								'image' => 'img/135deg.png',
								'title' => '135 Degre',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 45.8
							],
							[
								'slug' => 'wall',
								'type' => 'range_option',
								'image' => 'img/wall.png',
								'title' => 'Wall',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 30
							],
							[
								'slug' => 'clamp',
								'type' => 'range_option',
								'image' => 'img/clamp.png',
								'title' => 'Clamp',
								'label' => '',
								'unit' => 'mm',
								'min' => 10,
								'max' => 30,
								'default' => 15
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none.png',
								'title' => 'None',
							],
						]
					]				
				]
			],
		]
	],
	[
		'title' => 'Aluminium',
		'slug' => 'ALUMINUM',
		'image' => 'img/aluminium.jpg',
		'settings' => [
			'gate' => [
				'title' => 'Options',
				'label' => 'Gate',
				'action' => ['add', 'edit'],
				'fields' => [
					[
						'slug' => 'move',
						'type' => 'move',
						'label' => '',
					],
					[
						'slug' => 'gate_size',
						'type' => 'range',
						'label' => 'Gate Size',
						'unit' => 'mm',
						'min' => 700,
						'max' => 1000,
						'default' => 800
					]				
				]
			],
			'rail_options' => [
				'title' => 'Options',
				'label' => 'Rail Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'rail_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'No rail',
								'image' => 'img/none.jpg',
								'extra' => '-$26.07/m',
							],
							[
								'slug'  => 'opt-2',
								'title' => '21x25 Cap',
								'image' => 'img/21X25CAPX5.8.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-3',
								'title' => '21x25 Black',
								'image' => 'img/21X25CAPX5.8BLACK.jpg',
								'extra' => '+$4.15/m',
							],
							[
								'slug'  => 'opt-4',
								'title' => '25.4 Cap',
								'image' => 'img/25.4CAPX5.8.jpg',
								'extra' => '-$0.00/m',
							],
							[
								'slug'  => 'opt-5',
								'title' => '50x10 Handrail',
								'image' => 'img/50x10HRPSx5.8M.jpg',
								'extra' => '+$31.77/m',
							]
						]
					]
				]
			],
			'left_side'	=> [
				'title' => 'Edge to Left Centre Point',
				'label' => 'Left Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'left_slider',
						'type' => 'range_icon',
						'label' => 'Left Side',
						'image' => 'img/control-left.png',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'left_option',
						'type' => 'range_option',
						'label' => 'Left Side',
						'options' => [
							[
								'slug' => '1way',
								'type' => 'range_option',
								'image' => 'img/1way.png',
								'title' => '1 Way',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			],
			'panel_options'	=> [
				'title' => 'Maximum Panel Size',
				'label' => 'Panel Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'panel_option',
						'type' => 'text_option',
						'label' => 'Panel Cut Option',
						'options' => [
							[
								'slug' => 'even',
								'type' => 'text_option',
								'title' => 'EVEN PANEL SIZE',
							],
							[
								'slug' => 'full',
								'type' => 'text_option',
								'title' => 'FULL PANEL',
							],
							[
								'slug' => 'user_3000',
								'type' => 'text_option',
								'title' => 'USE 3000 PANELS',
							]
						]
					]
				]
			],
			'spacing'	=> [
				'title' => 'Centre-to-Centre',
				'label' => 'Spacing',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'center_spacing',
						'type' => 'range',
						'label' => '',
						'unit' => 'mm',
						'min' => 55,
						'max' => 70,
						'default' => 60
					]
				]
			],
			'post_options' => [
				'title' => 'Options',
				'label' => 'Post Options',
				'action' => ['default'],
				'fields' => [
					[
						'slug' => 'post_option',
						'type' => 'image_option',
						'label' => '',
						'options' => [
							[
								'slug'  => 'opt-1',
								'title' => 'Black post 1800mm',
								'image' => 'img/AP1800B.jpg',
								'extra' => '',
							],
							[
								'slug'  => 'opt-2',
								'title' => 'Black post 2100mm',
								'image' => 'img/AP2100B.jpg',
								'extra' => '+$3.85',
							],
							[
								'slug'  => 'opt-3',
								'title' => 'Bolt-down Black 1300mm',
								'image' => 'img/APBPB1300.jpg',
								'extra' => '+$16.23',
							],
							[
								'slug'  => 'opt-4',
								'title' => 'Black bolt-down 1600mm',
								'image' => 'img/APBPB1600.jpg',
								'extra' => '+$19.09',
							]
						]
					]									
				]
			],
			'right_side'	=> [
				'title' => 'Edge to Right Centre Point',
				'label' => 'Right Side',
				'action' => ['edit'],
				'fields' => [
					[
						'slug' => 'right_slider',
						'type' => 'range_icon',
						'image' => 'img/control-right.png',
						'label' => '',
						'unit' => 'mm',
						'min' => 0,
						'max' => 200,
						'default' => 55
					],
					[
						'slug' => 'right_option',
						'type' => 'range_option',
						'label' => 'Right Side',
						'options' => [
							[
								'slug' => '1way',
								'type' => 'range_option',
								'image' => 'img/1way.png',
								'title' => '1 Way',
								'label' => '',
								'unit' => 'mm',
								'min' => 0,
								'max' => 200,
								'default' => 55
							],
							[
								'slug' => 'none',
								'type' => 'range_option',
								'image' => 'img/none-round.png',
								'title' => 'None',
							],
						]
					],					
				]
			],
		]
	]
];