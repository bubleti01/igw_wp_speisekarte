<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_Templates {
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_public_queries' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'order_archive_query' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_inactive_single_as_404' ) );
	}

	public static function filter_public_queries( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if ( ! self::query_targets_speisekarte( $query ) ) {
			return;
		}

		$query_args = array(
			'meta_query' => $query->get( 'meta_query' ),
		);
		$query_args = igw_spk_add_active_visibility_to_query_args( $query_args );
		$query->set( 'meta_query', $query_args['meta_query'] );
	}

	public static function order_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( 'igw_wp_speisekarte' ) ) {
			return;
		}

		$query->set( 'orderby', 'menu_order title' );
		$query->set( 'order', 'ASC' );
	}

	public static function handle_inactive_single_as_404() {
		if ( is_admin() || ! is_singular( 'igw_wp_speisekarte' ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id || igw_spk_is_publicly_visible_item( $post_id ) ) {
			return;
		}

		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();

		$template = get_404_template();
		if ( $template ) {
			include $template;
			exit;
		}

		wp_die( esc_html__( 'Seite nicht gefunden.', 'igw_wp_speisekarte' ), '404', array( 'response' => 404 ) );
	}

	private static function query_targets_speisekarte( $query ) {
		if ( $query->is_post_type_archive( 'igw_wp_speisekarte' ) || $query->is_singular( 'igw_wp_speisekarte' ) ) {
			return true;
		}

		$post_type = $query->get( 'post_type' );
		if ( 'igw_wp_speisekarte' === $post_type || 'any' === $post_type ) {
			return true;
		}

		if ( is_array( $post_type ) && in_array( 'igw_wp_speisekarte', $post_type, true ) ) {
			return true;
		}

		if ( null === $post_type || '' === $post_type ) {
			return $query->is_category() || $query->is_tag() || $query->is_tax() || $query->is_search();
		}

		return false;
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
