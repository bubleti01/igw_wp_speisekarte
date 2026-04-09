<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_Meta {
	const NONCE_ACTION = 'igw_spk_save_meta';
	const NONCE_NAME   = 'igw_spk_meta_nonce';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_igw_wp_speisekarte', array( __CLASS__, 'save_post' ), 10, 3 );
		add_action( 'wp_after_insert_post', array( __CLASS__, 'validate_required_category' ), 20, 4 );
		add_action( 'save_post_igw_spk_zutat', array( __CLASS__, 'save_zutat_meta' ) );
	}

	public static function register_meta() {
		$common = array(
			'single'            => true,
			'show_in_rest'      => true,
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
			'sanitize_callback' => 'sanitize_text_field',
		);

		register_post_meta( 'igw_wp_speisekarte', 'igw_spk_preis_basis', $common );
		register_post_meta( 'igw_wp_speisekarte', 'igw_spk_hauptzutaten', $common );
		register_post_meta( 'igw_wp_speisekarte', 'igw_spk_portionsgroesse', $common );

		register_post_meta( 'igw_wp_speisekarte', 'igw_spk_home', array_merge( $common, array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 0 ) ) );
		register_post_meta( 'igw_wp_speisekarte', 'igw_spk_aktive', array_merge( $common, array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 1 ) ) );
		register_post_meta( 'igw_wp_speisekarte', 'igw_spk_cached_min_price', $common );
		register_post_meta(
			'igw_wp_speisekarte',
			'igw_spk_varianten',
			array_merge(
				$common,
				array(
					'type'              => 'array',
					'show_in_rest'      => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'label' => array( 'type' => 'string' ),
									'preis' => array( 'type' => 'string' ),
								),
								'additionalProperties' => false,
							),
						),
					),
					'sanitize_callback' => array( __CLASS__, 'sanitize_varianten' ),
				)
			)
		);
		register_post_meta(
			'igw_wp_speisekarte',
			'igw_spk_ernaehrungslabel',
			array_merge(
				$common,
				array(
					'type'              => 'array',
					'show_in_rest'      => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
					),
					'sanitize_callback' => array( __CLASS__, 'sanitize_string_array' ),
				)
			)
		);
		register_post_meta(
			'igw_wp_speisekarte',
			'igw_spk_zutaten_ids',
			array_merge(
				$common,
				array(
					'type'              => 'array',
					'show_in_rest'      => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'integer',
							),
						),
					),
					'sanitize_callback' => array( __CLASS__, 'sanitize_int_array' ),
				)
			)
		);

		register_post_meta(
			'igw_spk_zutat',
			'igw_spk_zutat_hinweis',
			array(
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		foreach ( array( 'igw_spk_allergen', 'igw_spk_zusatzstoff' ) as $taxonomy ) {
			register_term_meta( $taxonomy, 'igw_code', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
			register_term_meta( $taxonomy, 'igw_beschreibung', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
			register_term_meta( $taxonomy, 'igw_icon', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
		}
	}

	public static function add_meta_boxes() {
		add_meta_box( 'igw_spk_prices', __( 'Preis / Varianten', 'igw_wp_speisekarte' ), array( __CLASS__, 'render_meta_box_prices' ), 'igw_wp_speisekarte', 'normal', 'high' );
		add_meta_box( 'igw_spk_flags', __( 'Anzeigeoptionen', 'igw_wp_speisekarte' ), array( __CLASS__, 'render_meta_box_flags' ), 'igw_wp_speisekarte', 'side' );
		add_meta_box( 'igw_spk_labels', __( 'Inhaltsangaben', 'igw_wp_speisekarte' ), array( __CLASS__, 'render_meta_box_labels' ), 'igw_wp_speisekarte', 'normal' );
		add_meta_box( 'igw_spk_ingredients', __( 'Zutaten', 'igw_wp_speisekarte' ), array( __CLASS__, 'render_meta_box_ingredients' ), 'igw_wp_speisekarte', 'normal' );
		add_meta_box( 'igw_spk_zutat_hinweis', __( 'Besondere Kennzeichnung', 'igw_wp_speisekarte' ), array( __CLASS__, 'render_zutat_hinweis_meta_box' ), 'igw_spk_zutat' );
	}

	public static function render_meta_box_prices( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$basispreis = get_post_meta( $post->ID, 'igw_spk_preis_basis', true );
		$varianten  = get_post_meta( $post->ID, 'igw_spk_varianten', true );
		$varianten  = is_array( $varianten ) ? $varianten : array();
		echo '<p><label><strong>' . esc_html__( 'Basispreis', 'igw_wp_speisekarte' ) . '</strong></label><br><input type="text" class="widefat" name="igw_spk_preis_basis" value="' . esc_attr( $basispreis ) . '" placeholder="2,50 €"></p>';
		echo '<p><strong>' . esc_html__( 'Varianten', 'igw_wp_speisekarte' ) . '</strong></p>';
		for ( $i = 0; $i < 5; $i++ ) {
			$label = isset( $varianten[ $i ]['label'] ) ? $varianten[ $i ]['label'] : '';
			$preis = isset( $varianten[ $i ]['preis'] ) ? $varianten[ $i ]['preis'] : '';
			echo '<div class="igw-spk-variante-row">';
			echo '<input type="text" name="igw_spk_varianten[' . esc_attr( $i ) . '][label]" value="' . esc_attr( $label ) . '" placeholder="' . esc_attr__( 'Bezeichnung', 'igw_wp_speisekarte' ) . '"> ';
			echo '<input type="text" name="igw_spk_varianten[' . esc_attr( $i ) . '][preis]" value="' . esc_attr( $preis ) . '" placeholder="' . esc_attr__( 'Preis', 'igw_wp_speisekarte' ) . '">';
			echo '</div>';
		}
	}

	public static function render_meta_box_flags( $post ) {
		$home   = (int) get_post_meta( $post->ID, 'igw_spk_home', true );
		$aktive = metadata_exists( 'post', $post->ID, 'igw_spk_aktive' ) ? (int) get_post_meta( $post->ID, 'igw_spk_aktive', true ) : 1;
		echo '<label><input type="checkbox" name="igw_spk_home" value="1" ' . checked( 1, $home, false ) . '> ' . esc_html__( 'Zeige in Home', 'igw_wp_speisekarte' ) . '</label><br>';
		echo '<label><input type="checkbox" name="igw_spk_aktive" value="1" ' . checked( 1, $aktive, false ) . '> ' . esc_html__( 'Aktive', 'igw_wp_speisekarte' ) . '</label>';
	}

	public static function render_meta_box_labels( $post ) {
		$hauptzutaten  = get_post_meta( $post->ID, 'igw_spk_hauptzutaten', true );
		$portionsgroe  = get_post_meta( $post->ID, 'igw_spk_portionsgroesse', true );
		$selected      = igw_spk_get_item_labels( $post->ID );
		$label_options = array( 'vegetarisch', 'vegan', 'scharf', 'glutenfrei', 'laktosefrei', 'halal' );
		echo '<p><label><strong>' . esc_html__( 'Hauptzutaten', 'igw_wp_speisekarte' ) . '</strong></label><br><textarea class="widefat" name="igw_spk_hauptzutaten">' . esc_textarea( $hauptzutaten ) . '</textarea></p>';
		echo '<p><label><strong>' . esc_html__( 'Portionsgröße', 'igw_wp_speisekarte' ) . '</strong></label><br><input class="widefat" type="text" name="igw_spk_portionsgroesse" value="' . esc_attr( $portionsgroe ) . '"></p>';
		echo '<p><strong>' . esc_html__( 'Ernährungslabel', 'igw_wp_speisekarte' ) . '</strong></p>';
		foreach ( $label_options as $option ) {
			echo '<label><input type="checkbox" name="igw_spk_ernaehrungslabel[]" value="' . esc_attr( $option ) . '" ' . checked( in_array( $option, $selected, true ), true, false ) . '> ' . esc_html( ucfirst( $option ) ) . '</label><br>';
		}
	}

	public static function render_meta_box_ingredients( $post ) {
		$selected = igw_spk_get_item_ingredient_ids( $post->ID );
		$items    = get_posts(
			array(
				'post_type'      => 'igw_spk_zutat',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		echo '<p>' . esc_html__( 'Zutaten auswählen', 'igw_wp_speisekarte' ) . '</p><div class="igw-spk-ingredient-list">';
		foreach ( $items as $item ) {
			echo '<label><input type="checkbox" name="igw_spk_zutaten_ids[]" value="' . esc_attr( $item->ID ) . '" ' . checked( in_array( $item->ID, $selected, true ), true, false ) . '> ' . esc_html( $item->post_title ) . '</label><br>';
		}
		echo '</div>';
	}

	public static function render_zutat_hinweis_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$hinweis = get_post_meta( $post->ID, 'igw_spk_zutat_hinweis', true );
		echo '<textarea name="igw_spk_zutat_hinweis" class="widefat">' . esc_textarea( $hinweis ) . '</textarea>';
	}

	public static function save_post( $post_id, $post, $update ) {
		if ( ! self::can_save( $post_id ) ) {
			return;
		}

		if ( ! $update ) {
			update_post_meta( $post_id, 'igw_spk_home', 0 );
			update_post_meta( $post_id, 'igw_spk_aktive', 1 );
		}

		update_post_meta( $post_id, 'igw_spk_preis_basis', igw_spk_sanitize_price( wp_unslash( $_POST['igw_spk_preis_basis'] ?? '' ) ) );
		update_post_meta( $post_id, 'igw_spk_hauptzutaten', sanitize_textarea_field( wp_unslash( $_POST['igw_spk_hauptzutaten'] ?? '' ) ) );
		update_post_meta( $post_id, 'igw_spk_portionsgroesse', sanitize_text_field( wp_unslash( $_POST['igw_spk_portionsgroesse'] ?? '' ) ) );
		update_post_meta( $post_id, 'igw_spk_home', isset( $_POST['igw_spk_home'] ) ? 1 : 0 );
		update_post_meta( $post_id, 'igw_spk_aktive', isset( $_POST['igw_spk_aktive'] ) ? 1 : 0 );
		update_post_meta( $post_id, 'igw_spk_varianten', self::sanitize_varianten( wp_unslash( $_POST['igw_spk_varianten'] ?? array() ) ) );
		update_post_meta( $post_id, 'igw_spk_ernaehrungslabel', self::sanitize_string_array( wp_unslash( $_POST['igw_spk_ernaehrungslabel'] ?? array() ) ) );
		update_post_meta( $post_id, 'igw_spk_zutaten_ids', self::sanitize_int_array( wp_unslash( $_POST['igw_spk_zutaten_ids'] ?? array() ) ) );
	}

	public static function validate_required_category( $post_id, $post, $update, $post_before ) {
		static $is_validating = false;

		if ( $is_validating || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( 'igw_wp_speisekarte' !== $post->post_type || 'auto-draft' === $post->post_status || 'trash' === $post->post_status ) {
			return;
		}

		$categories = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'ids' ) );
		if ( ! empty( $categories ) ) {
			return;
		}

		$is_validating = true;
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		$is_validating = false;

		if ( get_current_user_id() > 0 ) {
			set_transient( 'igw_spk_category_required_' . get_current_user_id(), 1, 60 );
		}
	}

	public static function save_zutat_meta( $post_id ) {
		if ( ! self::can_save( $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, 'igw_spk_zutat_hinweis', sanitize_text_field( wp_unslash( $_POST['igw_spk_zutat_hinweis'] ?? '' ) ) );
	}

	private static function can_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return false;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}
		if ( empty( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return false;
		}
		return true;
	}

	public static function sanitize_varianten( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$sanitized = array();
		foreach ( $value as $row ) {
			$label = sanitize_text_field( $row['label'] ?? '' );
			$preis = sanitize_text_field( $row['preis'] ?? '' );
			if ( '' === $label && '' === $preis ) {
				continue;
			}
			$sanitized[] = array(
				'label' => $label,
				'preis' => $preis,
			);
		}
		return $sanitized;
	}

	public static function sanitize_string_array( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $value ) ) ) );
	}

	public static function sanitize_int_array( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}
}
