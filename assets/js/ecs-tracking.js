/**
 * ECS conversion events → dataLayer (P4-04).
 * GTM maps these to GA4 / Meta Pixel tags.
 */
( function () {
	'use strict';

	window.dataLayer = window.dataLayer || [];

	function pushEvent( event, data ) {
		window.dataLayer.push(
			Object.assign( { event: event }, data || {} )
		);
	}

	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest( 'a' );
		if ( ! link ) {
			return;
		}

		var href = link.getAttribute( 'href' ) || '';

		if ( href.indexOf( 'tel:' ) === 0 ) {
			pushEvent( 'phone_click', {
				phone_number: href.replace( 'tel:', '' ),
			} );
			return;
		}

		if ( href.indexOf( 'mailto:' ) === 0 ) {
			pushEvent( 'email_click', {
				email: href.replace( 'mailto:', '' ),
			} );
			return;
		}

		if ( /bookings\.gettimely\.com|book\.gettimely\.com/i.test( href ) ) {
			pushEvent( 'booking_click', { link_url: href } );
		}
	} );

	if ( window.jQuery ) {
		window.jQuery( document ).on(
			'wpformsAjaxSubmitSuccess',
			function ( event, response ) {
				var formId =
					( response && response.data && response.data.form_id ) ||
					( event.detail && event.detail.form_id ) ||
					'';

				pushEvent( 'form_submission', { form_id: String( formId ) } );
			}
		);
	}
} )();
