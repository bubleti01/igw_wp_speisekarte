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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_list_assets' ) );
		add_action( 'wp_ajax_igw_spk_toggle_home', array( __CLASS__, 'ajax_toggle_home' ) );
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
				$value = (int) get_post_meta( $post_id, 'igw_spk_home', true ) ? 1 : 0;
				echo '<button type="button" class="button igw-spk-home-toggle ' . ( $value ? 'is-on' : 'is-off' ) . '" data-post-id="' . esc_attr( (string) $post_id ) . '" data-value="' . esc_attr( (string) $value ) . '" aria-pressed="' . esc_attr( $value ? 'true' : 'false' ) . '">';
				echo '<span class="igw-spk-home-toggle__status">' . esc_html( $value ? 'ON' : 'OFF' ) . '</span>';
				echo '</button>';
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

	public static function enqueue_list_assets( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'igw_wp_speisekarte' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'igw-spk-admin-list',
			IGW_SPK_PLUGIN_URL . 'assets/js/admin-list.js',
			array( 'jquery' ),
			IGW_SPK_VERSION,
			true
		);

		wp_localize_script(
			'igw-spk-admin-list',
			'igwSpkAdminList',
			array(
				'nonce' => wp_create_nonce( 'igw_spk_toggle_home' ),
			)
		);
	}

	public static function ajax_toggle_home() {
		check_ajax_referer( 'igw_spk_toggle_home', 'nonce' );

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiger Beitrag.', 'igw_wp_speisekarte' ) ), 400 );
		}

		if ( 'igw_wp_speisekarte' !== get_post_type( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiger Beitragstyp.', 'igw_wp_speisekarte' ) ), 400 );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'igw_wp_speisekarte' ) ), 403 );
		}

		$current = (int) get_post_meta( $post_id, 'igw_spk_home', true ) ? 1 : 0;
		$target  = isset( $_POST['value'] ) ? ( absint( $_POST['value'] ) ? 1 : 0 ) : ( $current ? 0 : 1 );

		update_post_meta( $post_id, 'igw_spk_home', $target );

		wp_send_json_success(
			array(
				'value' => $target,
				'label' => $target ? 'ON' : 'OFF',
			)
		);
	}
}
