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

			$term = self::find_existing_standard_term( $taxonomy, $code, $description );
			if ( ! $term ) {
				$inserted = wp_insert_term(
					$description,
					$taxonomy,
					array(
						'slug' => sanitize_title( $description ),
					)
				);

				if ( is_wp_error( $inserted ) ) {
					if ( 'term_exists' === $inserted->get_error_code() ) {
						$term_id = (int) $inserted->get_error_data();
						$term    = $term_id > 0 ? get_term( $term_id, $taxonomy ) : null;
					}
				} else {
					$term = get_term( (int) $inserted['term_id'], $taxonomy );
				}
			}

			if ( $term && ! is_wp_error( $term ) ) {
				self::repair_standard_term_meta( $term->term_id, $code, $description );
			}
		}
	}

	private static function find_existing_standard_term( $taxonomy, $code, $description ) {
		$existing = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 1,
				'meta_query' => array(
					array(
						'key'   => 'igw_code',
						'value' => $code,
					),
				),
			)
		);
		if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
			return $existing[0];
		}

		$slug_term = get_term_by( 'slug', sanitize_title( $description ), $taxonomy );
		if ( $slug_term && ! is_wp_error( $slug_term ) ) {
			return $slug_term;
		}

		$name_term = get_term_by( 'name', $description, $taxonomy );
		if ( $name_term && ! is_wp_error( $name_term ) ) {
			return $name_term;
		}

		return null;
	}

	private static function repair_standard_term_meta( $term_id, $code, $description ) {
		$current_code = (string) get_term_meta( $term_id, 'igw_code', true );
		if ( '' === trim( $current_code ) ) {
			update_term_meta( $term_id, 'igw_code', $code );
		}

		$current_description = (string) get_term_meta( $term_id, 'igw_beschreibung', true );
		if ( '' === trim( $current_description ) ) {
			update_term_meta( $term_id, 'igw_beschreibung', $description );
		}

		if ( ! metadata_exists( 'term', $term_id, 'igw_icon' ) ) {
			update_term_meta( $term_id, 'igw_icon', '' );
		}
	}
}
