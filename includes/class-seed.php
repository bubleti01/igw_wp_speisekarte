<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_Seed {
	public static function seed_default_terms() {
		$file = IGW_SPK_PLUGIN_PATH . 'data/kennzeichnung-seed.txt';
		if ( ! file_exists( $file ) ) {
			return;
		}

		$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( ! $lines ) {
			return;
		}

		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( 3 !== count( $parts ) ) {
				continue;
			}
			list( $type, $code, $description ) = $parts;
			$taxonomy = 'allergen' === $type ? 'igw_spk_allergen' : ( 'zusatzstoff' === $type ? 'igw_spk_zusatzstoff' : '' );
			if ( '' === $taxonomy ) {
				continue;
			}

			$existing = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'meta_query' => array(
						array(
							'key'   => 'igw_code',
							'value' => $code,
						),
					),
				)
			);
			if ( ! empty( $existing ) && ! is_wp_error( $existing ) ) {
				continue;
			}

			$inserted = wp_insert_term( $description, $taxonomy );
			if ( is_wp_error( $inserted ) ) {
				continue;
			}

			$term_id = (int) $inserted['term_id'];
			update_term_meta( $term_id, 'igw_code', $code );
			update_term_meta( $term_id, 'igw_beschreibung', $description );
			update_term_meta( $term_id, 'igw_icon', '' );
		}
	}
}
