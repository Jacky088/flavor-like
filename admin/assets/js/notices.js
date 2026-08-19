/**
 * Flavor Like admin notice interactions (vanilla JS).
 */
( function () {
	'use strict';

	function getNoticeWrapper( element ) {
		return element.closest( '.flavor-like-notice-wrapper' );
	}

	function hideNoticeWrapper( wrapper ) {
		if ( ! wrapper ) {
			return;
		}

		wrapper.style.transition = 'opacity 0.2s ease';
		wrapper.style.opacity = '0';

		window.setTimeout( function () {
			wrapper.style.display = 'none';
		}, 200 );
	}

	function postNoticeRequest( payload ) {
		var body = new URLSearchParams( payload );

		return fetch( window.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
		} ).then( function ( response ) {
			return response.json();
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var ctaButton = event.target.closest( '.flavor-like-notice-cta-btn' );

		if ( ctaButton ) {
			var ajaxAction = ctaButton.getAttribute( 'data-ajax-action' );
			var noticeId = ctaButton.getAttribute( 'data-notice-id' );
			var nonce = ctaButton.getAttribute( 'data-notice-nonce' );

			if ( ! ajaxAction || ! noticeId || ! nonce ) {
				return;
			}

			event.preventDefault();

			ctaButton.classList.add( 'flavor-like-btn-is-loading' );

			postNoticeRequest( {
				action: ajaxAction,
				nonce: nonce,
				id: noticeId,
			} )
				.then( function ( response ) {
					ctaButton.classList.remove( 'flavor-like-btn-is-loading' );
					hideNoticeWrapper( getNoticeWrapper( ctaButton ) );

					if ( response && response.success ) {
						window.location.reload();
					}
				} )
				.catch( function () {
					ctaButton.classList.remove( 'flavor-like-btn-is-loading' );
				} );

			return;
		}

		var skipButton = event.target.closest( '.flavor-like-skip-notice' );

		if ( ! skipButton ) {
			return;
		}

		event.preventDefault();

		var wrapper = getNoticeWrapper( skipButton );
		var dismissId = wrapper ? wrapper.getAttribute( 'data-notice-id' ) : '';
		var dismissNonce = wrapper ? wrapper.getAttribute( 'data-dismiss-nonce' ) : '';
		var expiration =
			skipButton.getAttribute( 'data-expiration' ) ||
			( wrapper ? wrapper.getAttribute( 'data-dismiss-expiration' ) : '' );

		if ( ! dismissId || ! dismissNonce ) {
			return;
		}

		postNoticeRequest( {
			action: 'flavor_like_dismissed_notice',
			id: dismissId,
			nonce: dismissNonce,
			expiration: expiration || '',
		} ).then( function ( response ) {
			if ( response && response.success ) {
				hideNoticeWrapper( wrapper );
			}
		} );
	} );
} )();
