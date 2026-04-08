<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function igw_spk_sanitize_price( $raw ) {
	$raw = sanitize_text_field( (string) $raw );
	return $raw;
}

function igw_spk_normalize_price_to_float( $raw ) {
	$value = str_replace( array( '€', ' ' ), '', (string) $raw );
	$value = str_replace( ',', '.', $value );
	$value = preg_replace( '/[^0-9.]/', '', $value );
	return is_numeric( $value ) ? number_format( (float) $value, 2, '.', '' ) : '';
}

function igw_spk_get_item_price_output( $post_id ) {
	$varianten = get_post_meta( $post_id, 'igw_spk_varianten', true );
	if ( ! empty( $varianten ) && is_array( $varianten ) ) {
		$rows = array();
		foreach ( $varianten as $variante ) {
			$label = isset( $variante['label'] ) ? sanitize_text_field( $variante['label'] ) : '';
			$preis = isset( $variante['preis'] ) ? sanitize_text_field( $variante['preis'] ) : '';
			if ( '' === $label && '' === $preis ) {
				continue;
			}
			$rows[] = trim( $label . ( $label && $preis ? ' – ' : '' ) . $preis );
		}
		if ( ! empty( $rows ) ) {
			return $rows;
		}
	}

	$basispreis = get_post_meta( $post_id, 'igw_spk_preis_basis', true );
	if ( ! empty( $basispreis ) ) {
		return array( sanitize_text_field( $basispreis ) );
	}

	return array();
}

function igw_spk_get_item_modified_date( $post_id ) {
	$timestamp = get_post_modified_time( 'U', true, $post_id );
	if ( ! $timestamp ) {
		return '';
	}
	return wp_date( 'd.m.Y', $timestamp );
}

function igw_spk_get_item_labels( $post_id ) {
	$labels = get_post_meta( $post_id, 'igw_spk_ernaehrungslabel', true );
	return is_array( $labels ) ? array_map( 'sanitize_text_field', $labels ) : array();
}

function igw_spk_get_item_ingredient_ids( $post_id ) {
	$ids = get_post_meta( $post_id, 'igw_spk_zutaten_ids', true );
	if ( ! is_array( $ids ) ) {
		return array();
	}
	return array_values( array_filter( array_map( 'absint', $ids ) ) );
}

function igw_spk_get_aggregated_markings( $post_id ) {
	$ingredient_ids = igw_spk_get_item_ingredient_ids( $post_id );
	if ( empty( $ingredient_ids ) ) {
		return array(
			'allergene'    => array(),
			'zusatzstoffe' => array(),
		);
	}

	$allergen_codes    = array();
	$zusatzstoff_codes = array();

	foreach ( $ingredient_ids as $ingredient_id ) {
		$allergen_terms = wp_get_object_terms( $ingredient_id, 'igw_spk_allergen' );
		if ( ! is_wp_error( $allergen_terms ) ) {
			foreach ( $allergen_terms as $term ) {
				$allergen_codes[] = (string) get_term_meta( $term->term_id, 'igw_code', true );
			}
		}

		$zusatz_terms = wp_get_object_terms( $ingredient_id, 'igw_spk_zusatzstoff' );
		if ( ! is_wp_error( $zusatz_terms ) ) {
			foreach ( $zusatz_terms as $term ) {
				$zusatzstoff_codes[] = (string) get_term_meta( $term->term_id, 'igw_code', true );
			}
		}
	}

	$allergen_codes = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $allergen_codes ) ) ) );
	usort(
		$allergen_codes,
		static function ( $a, $b ) {
			return (int) $a <=> (int) $b;
		}
	);

	$zusatzstoff_codes = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $zusatzstoff_codes ) ) ) );
	sort( $zusatzstoff_codes, SORT_NATURAL | SORT_FLAG_CASE );

	return array(
		'allergene'    => $allergen_codes,
		'zusatzstoffe' => $zusatzstoff_codes,
	);
}
