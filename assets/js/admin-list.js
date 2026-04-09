jQuery( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.igw-spk-home-toggle', function () {
		var $button = $( this );
		if ( $button.prop( 'disabled' ) ) {
			return;
		}

		var postId = parseInt( $button.data( 'post-id' ), 10 );
		var currentValue = parseInt( $button.data( 'value' ), 10 ) ? 1 : 0;
		var nextValue = currentValue ? 0 : 1;

		$button.prop( 'disabled', true ).addClass( 'is-loading' );

		$.post( ajaxurl, {
			action: 'igw_spk_toggle_home',
			nonce: igwSpkAdminList.homeNonce,
			post_id: postId,
			value: nextValue
		} ).done( function ( response ) {
			if ( response && response.success && response.data ) {
				var value = parseInt( response.data.value, 10 ) ? 1 : 0;
				$button
					.data( 'value', value )
					.attr( 'data-value', value )
					.attr( 'aria-pressed', value ? 'true' : 'false' )
					.toggleClass( 'is-on', value === 1 )
					.toggleClass( 'is-off', value === 0 );
				$button.find( '.igw-spk-home-toggle__status' ).text( response.data.label || ( value ? 'ON' : 'OFF' ) );
				return;
			}

			alert( 'Speichern fehlgeschlagen.' );
		} ).fail( function () {
			alert( 'Speichern fehlgeschlagen.' );
		} ).always( function () {
			$button.prop( 'disabled', false ).removeClass( 'is-loading' );
		} );
	} );

	$( document ).on( 'click', '.igw-spk-preis-save', function () {
		var $button = $( this );
		var $wrapper = $button.closest( '.igw-spk-preis-inline' );
		var $input = $wrapper.find( '.igw-spk-preis-input' );
		var $display = $wrapper.find( '.igw-spk-preis-display' );
		var postId = parseInt( $wrapper.data( 'post-id' ), 10 );
		var currentDisplay = $display.text();

		if ( $button.prop( 'disabled' ) ) {
			return;
		}

		$button.prop( 'disabled', true );
		$input.prop( 'disabled', true );

		$.post( ajaxurl, {
			action: 'igw_spk_save_basispreis',
			nonce: igwSpkAdminList.priceNonce,
			post_id: postId,
			value: $input.val()
		} ).done( function ( response ) {
			if ( response && response.success && response.data ) {
				$input.val( response.data.value || '' );
				$display.text( response.data.label || '—' );
				return;
			}

			$display.text( currentDisplay );
			alert( 'Preis konnte nicht gespeichert werden.' );
		} ).fail( function () {
			$display.text( currentDisplay );
			alert( 'Preis konnte nicht gespeichert werden.' );
		} ).always( function () {
			$button.prop( 'disabled', false );
			$input.prop( 'disabled', false );
		} );
	} );
} );
