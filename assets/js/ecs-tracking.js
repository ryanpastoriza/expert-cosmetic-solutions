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

	function clinicFromPhone( digits ) {
		if ( digits.indexOf( '6135918' ) !== -1 || digits.indexOf( '035918' ) !== -1 ) {
			return 'pakenham';
		}
		if ( digits.indexOf( '61432323' ) !== -1 || digits.indexOf( '0432323' ) !== -1 ) {
			return 'warragul';
		}
		return 'unknown';
	}

	function linkLabel( link ) {
		var text = ( link.textContent || '' ).replace( /\s+/g, ' ' ).trim();
		return text.substring( 0, 80 );
	}

	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest( 'a' );
		if ( ! link ) {
			return;
		}

		var href = link.getAttribute( 'href' ) || '';

		if ( href.indexOf( 'tel:' ) === 0 ) {
			var phone = href.replace( 'tel:', '' );
			pushEvent( 'phone_click', {
				phone_number: phone,
				clinic_location: clinicFromPhone( phone.replace( /\D/g, '' ) ),
				link_text: linkLabel( link ),
				page_path: window.location.pathname,
			} );
			return;
		}

		if ( href.indexOf( 'mailto:' ) === 0 ) {
			pushEvent( 'email_click', {
				email: href.replace( 'mailto:', '' ),
				link_text: linkLabel( link ),
				page_path: window.location.pathname,
			} );
			return;
		}

		if ( /bookings\.gettimely\.com|book\.gettimely\.com/i.test( href ) ) {
			pushEvent( 'booking_click', {
				link_url: href,
				link_text: linkLabel( link ),
				page_path: window.location.pathname,
				content_type: document.body.classList.contains( 'single-post' ) ? 'blog_post' : 'page',
			} );
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

				var payload = {
					form_id: String( formId ),
					page_path: window.location.pathname,
				};
				pushEvent( 'form_submission', payload );
				pushEvent( 'contact_form_submit', payload );
			}
		);
	}
} )();
