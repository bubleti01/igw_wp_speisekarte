<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_Templates {
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_public_queries' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'order_archive_query' ) );
		add_filter( 'the_posts', array( __CLASS__, 'filter_mixed_query_posts' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'handle_inactive_single_as_404' ) );
	}

	public static function filter_public_queries( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if ( self::is_pure_speisekarte_query( $query ) ) {
			$query_args = array(
				'meta_query' => $query->get( 'meta_query' ),
			);
			$query_args = igw_spk_add_active_visibility_to_query_args( $query_args );
			$query->set( 'meta_query', $query_args['meta_query'] );
			return;
		}

		if ( self::is_mixed_speisekarte_query( $query ) ) {
			$query->set( 'igw_spk_filter_inactive', 1 );
		}
	}

	public static function filter_mixed_query_posts( $posts, $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->get( 'igw_spk_filter_inactive' ) ) {
			return $posts;
		}

		if ( empty( $posts ) || ! is_array( $posts ) ) {
			return $posts;
		}

		return array_values(
			array_filter(
				$posts,
				static function ( $post ) {
					if ( ! isset( $post->post_type ) || 'igw_wp_speisekarte' !== $post->post_type ) {
						return true;
					}

					return igw_spk_is_publicly_visible_item( $post->ID );
				}
			)
		);
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

	private static function is_pure_speisekarte_query( $query ) {
		if ( $query->is_post_type_archive( 'igw_wp_speisekarte' ) ) {
			return true;
		}

		$post_type = $query->get( 'post_type' );
		if ( 'igw_wp_speisekarte' === $post_type ) {
			return true;
		}

		if ( is_array( $post_type ) ) {
			$post_types = array_values( array_unique( array_filter( array_map( 'strval', $post_type ) ) ) );
			return 1 === count( $post_types ) && 'igw_wp_speisekarte' === $post_types[0];
		}

		return false;
	}

	private static function is_mixed_speisekarte_query( $query ) {
		if ( ! self::query_targets_speisekarte( $query ) || self::is_pure_speisekarte_query( $query ) ) {
			return false;
		}

		return $query->is_search() || $query->is_category() || $query->is_tag() || $query->is_tax();
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
