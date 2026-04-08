<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_Admin {
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'maybe_category_notice' ) );
		add_filter( 'manage_igw_wp_speisekarte_posts_columns', array( __CLASS__, 'register_admin_columns' ) );
		add_action( 'manage_igw_wp_speisekarte_posts_custom_column', array( __CLASS__, 'render_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-igw_wp_speisekarte_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'handle_column_sorting' ) );
	}

	public static function maybe_category_notice() {
		if ( ! get_transient( 'igw_spk_category_required_' . get_current_user_id() ) ) {
			return;
		}
		delete_transient( 'igw_spk_category_required_' . get_current_user_id() );
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Mindestens eine Kategorie ist für Speisen erforderlich. Beitrag wurde als Entwurf gespeichert.', 'igw_wp_speisekarte' ) . '</p></div>';
	}

	public static function register_admin_columns( $columns ) {
		return array(
			'cb'         => $columns['cb'],
			'title'      => __( 'Title', 'igw_wp_speisekarte' ),
			'preis'      => __( 'Preis', 'igw_wp_speisekarte' ),
			'menu_order' => __( 'Reihenfolge', 'igw_wp_speisekarte' ),
			'aktive'     => __( 'Aktiv', 'igw_wp_speisekarte' ),
			'home'       => __( 'Home', 'igw_wp_speisekarte' ),
			'categories' => __( 'Kategorien', 'igw_wp_speisekarte' ),
			'stand'      => __( 'Stand', 'igw_wp_speisekarte' ),
			'date'       => $columns['date'],
		);
	}

	public static function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'preis':
				$preise = igw_spk_get_item_price_output( $post_id );
				echo esc_html( $preise ? $preise[0] : '—' );
				break;
			case 'menu_order':
				echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
				break;
			case 'aktive':
				echo (int) get_post_meta( $post_id, 'igw_spk_aktive', true ) ? esc_html__( 'Ja', 'igw_wp_speisekarte' ) : esc_html__( 'Nein', 'igw_wp_speisekarte' );
				break;
			case 'home':
				echo (int) get_post_meta( $post_id, 'igw_spk_home', true ) ? esc_html__( 'Ja', 'igw_wp_speisekarte' ) : esc_html__( 'Nein', 'igw_wp_speisekarte' );
				break;
			case 'categories':
				the_category( ', ', '', $post_id );
				break;
			case 'stand':
				echo esc_html( igw_spk_get_item_modified_date( $post_id ) );
				break;
		}
	}

	public static function sortable_columns( $columns ) {
		$columns['menu_order'] = 'menu_order';
		$columns['title']      = 'title';
		return $columns;
	}

	public static function handle_column_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'igw_wp_speisekarte' !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( 'menu_order' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		}
	}
}
