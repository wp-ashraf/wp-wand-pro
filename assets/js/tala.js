jQuery( function ( $ ) {
	'use strict';

	/**
	 * ---------------------------------------
	 * ------------- DOM Ready ---------------
	 * ---------------------------------------
	 */

	$( '.wpwand-pro-tala-form' ).on( 'submit', function ( event ) {
		event.preventDefault();
		const $form = $( this );
		const $tala = $form.find( '[name=tala_key]' );
		const $tala_key = $tala.val();
		const $submit_button = $form.find( '.wpwand-pro-check-tala' );

		$submit_button.text( '' );
		$submit_button.append(
			'<span class="dashicons dashicons-update"></span>'
		);
		if ( $tala.val().length == 0 ) {
			$tala.css( 'border-color', 'red' );
			$tala.click();
			return;
		}

		$submit_button.addClass( 'wpwand-pro-tala-disable' );
		$submit_button.attr( 'disabled' );
		$tala.attr( 'disabled' );

		const data = new FormData();
		data.append( 'action', 'wpwand_pro_tala_ajax' );
		data.append( 'security', wpwand_pro_vars.nonce );
		data.append( 'tala', $tala_key );

		fbthTalaajaxCall( data );
	} );

	$( '.wpwand-pro-tala-deactivate' ).on( 'click', function ( event ) {
		event.preventDefault();
		const $btn = $( this );

		$btn.attr( 'disabled' );
		$btn.text( '' );
		$btn.append( '<span class="dashicons dashicons-update"></span>' );

		const data = new FormData();
		data.append( 'action', 'wpwand_pro_tala_deactivate' );
		data.append( 'security', wpwand_pro_vars.nonce );

		$.ajax( {
			method: 'POST',
			url: wpwand_pro_vars.ajax_url,
			contentType: false,
			processData: false,
			data,
		} )
			.done( function ( response ) {
				console.log( response );
				location.reload();
			} )
			.fail( function ( error ) {
				// location.reload();
			} );
	} );

	function fbthTalaajaxCall( data ) {
		// console.log(data);
		$.ajax( {
			method: 'POST',
			url: wpwand_pro_vars.ajax_url,
			data,
			contentType: false,
			processData: false,
			beforeSend() {
				$( '.wpwand-pro-tala-form' ).css( 'opacity', '0.5' );
			},
		} )
			.done( function ( response ) {
				console.log( response );
				const welcome = $(
						'.wpwand-pro-tala .wpwand-pro-setup-welcome'
					),
					success = $( '.wpwand-pro-tala .wpwand-pro-setup-success' ),
					error = $( '.wpwand-pro-tala .wpwand-pro-setup-error' );
				welcome.slideUp();
				if ( response === true ) {
					success.slideDown();
				} else {
					error.slideDown();
				}
			} )
			.fail( function ( error ) {
				const welcome = $(
					'.wpwand-pro-tala .wpwand-pro-setup-welcome'
				);
				var error = $( '.wpwand-pro-tala .wpwand-pro-setup-error' );
				welcome.slideUp();
				error.slideDown( 300 );
			} );
	}
} );
