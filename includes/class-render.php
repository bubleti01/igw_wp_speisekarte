<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_Render {
	public static function render_home_list( $args = array() ) {
		$query_args = wp_parse_args(
			$args,
			array(
				'post_type'      => 'igw_wp_speisekarte',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => 'igw_spk_home',
						'value' => 1,
					),
				),
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
			)
		);
		$query_args = igw_spk_add_active_visibility_to_query_args( $query_args );

		return self::render_items( $query_args, 'home' );
	}

	public static function render_abschnitt_list( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'abschnitt' => '',
			),
			$atts,
			'igw_spk_abschnitte'
		);

		$abschnitt = sanitize_title( $atts['abschnitt'] );
		if ( '' === $abschnitt ) {
			return '<!-- igw_spk_abschnitte: abschnitt fehlt -->';
		}

		$term = get_term_by( 'slug', $abschnitt, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			return '';
		}

		$query_args = array(
			'post_type'      => 'igw_wp_speisekarte',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => $abschnitt,
				),
			),
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		);
		$query_args = igw_spk_add_active_visibility_to_query_args( $query_args );

		return self::render_items( $query_args, 'abschnitt' );
	}

	private static function render_items( $query_args, $context ) {
		$query = new WP_Query( $query_args );
		if ( ! $query->have_posts() ) {
			return '';
		}

		ob_start();
		echo '<div class="igw-spk-list igw-spk-list-' . esc_attr( $context ) . '">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id    = get_the_ID();
			$permalink  = get_permalink( $post_id );
			$has_thumb  = has_post_thumbnail( $post_id );

			echo '<article class="igw-spk-item">';
			echo '<div class="igw-spk-item__image">';
			if ( $has_thumb ) {
				echo '<a class="igw-spk-item__image-link" href="' . esc_url( $permalink ) . '">';
				echo get_the_post_thumbnail( $post_id, 'medium', array( 'loading' => 'lazy' ) );
				echo '</a>';
			}
			echo '</div>';
			echo '<div class="igw-spk-item__content">';
			echo '<h3 class="igw-spk-item__title">' . esc_html( get_the_title() ) . '</h3>';
			$excerpt = igw_spk_get_item_excerpt_text( $post_id );
			if ( '' !== $excerpt ) {
				echo '<div class="igw-spk-item__excerpt">' . esc_html( $excerpt ) . '</div>';
			}
			echo '</div>';
			echo '<div class="igw-spk-item__price">';
			$prices = igw_spk_get_item_price_output( $post_id );
			foreach ( $prices as $price_line ) {
				echo '<div class="igw-spk-item__price-line">' . esc_html( $price_line ) . '</div>';
			}
			echo '</div>';
			echo '</article>';
		}
		echo '</div>';
		wp_reset_postdata();

		return ob_get_clean();
	}
}

function igw_spk_render_home_list( $args = array() ) {
	return IGW_SPK_Render::render_home_list( $args );
}

function igw_spk_render_abschnitt_list( $atts = array() ) {
	return IGW_SPK_Render::render_abschnitt_list( $atts );
}
