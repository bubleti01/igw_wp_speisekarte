<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_CPT {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 6 );
	}

	public static function register_taxonomies() {
		register_taxonomy(
			'igw_spk_allergen',
			array( 'igw_spk_zutat' ),
			array(
				'labels'       => array(
					'name'          => __( 'Allergene', 'igw_wp_speisekarte' ),
					'singular_name' => __( 'Allergen', 'igw_wp_speisekarte' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'hierarchical' => false,
			)
		);

		register_taxonomy(
			'igw_spk_zusatzstoff',
			array( 'igw_spk_zutat' ),
			array(
				'labels'       => array(
					'name'          => __( 'Zusatzstoffe', 'igw_wp_speisekarte' ),
					'singular_name' => __( 'Zusatzstoff', 'igw_wp_speisekarte' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'hierarchical' => false,
			)
		);
	}

	public static function register_post_types() {
		register_post_type(
			'igw_wp_speisekarte',
			array(
				'labels'             => array(
					'name'          => __( 'Speisekarte', 'igw_wp_speisekarte' ),
					'singular_name' => __( 'Speise', 'igw_wp_speisekarte' ),
					'add_new'       => __( 'Speise hinzufügen', 'igw_wp_speisekarte' ),
					'add_new_item'  => __( 'Speise hinzufügen', 'igw_wp_speisekarte' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'has_archive'        => true,
				'rewrite'            => array(
					'slug'       => 'speisekarte',
					'with_front' => false,
				),
				'menu_position'      => 21,
				'menu_icon'          => 'dashicons-excerpt-view',
				'hierarchical'       => false,
				'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'page-attributes', 'custom-fields' ),
				'taxonomies'         => array( 'category', 'post_tag' ),
			)
		);

		register_post_type(
			'igw_spk_zutat',
			array(
				'labels'             => array(
					'name'          => __( 'Zutaten', 'igw_wp_speisekarte' ),
					'singular_name' => __( 'Zutat', 'igw_wp_speisekarte' ),
					'add_new'       => __( 'Zutat hinzufügen', 'igw_wp_speisekarte' ),
					'add_new_item'  => __( 'Zutat hinzufügen', 'igw_wp_speisekarte' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'menu_icon'          => 'dashicons-drumstick',
				'has_archive'        => false,
				'rewrite'            => false,
				'supports'           => array( 'title', 'custom-fields' ),
			)
		);
	}
}
