<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_Templates {
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'order_archive_query' ) );
	}

	public static function order_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! is_post_type_archive( 'igw_wp_speisekarte' ) ) {
			return;
		}

		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		$query->set( 'order', 'ASC' );
	}

	public static function template_include( $template ) {
		if ( is_post_type_archive( 'igw_wp_speisekarte' ) ) {
			$plugin_template = IGW_SPK_PLUGIN_PATH . 'templates/archive-igw_wp_speisekarte.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		if ( is_singular( 'igw_wp_speisekarte' ) ) {
			$plugin_template = IGW_SPK_PLUGIN_PATH . 'templates/single-igw_wp_speisekarte.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}
}
