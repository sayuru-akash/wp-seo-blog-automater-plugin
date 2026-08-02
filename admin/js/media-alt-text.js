/* global wpSeoAutomaterMediaAlt, wp */
( function ( $ ) {
	"use strict";

	var config = window.wpSeoAutomaterMediaAlt;
	if ( ! config ) {
		return;
	}

	function uniqueIds( ids ) {
		var seen = {};
		var cleaned = [];

		$.each( ids, function ( _, value ) {
			var id = parseInt( value, 10 );
			if ( id > 0 && ! seen[ id ] ) {
				seen[ id ] = true;
				cleaned.push( id );
			}
		} );

		return cleaned;
	}

	function selectedGridIds() {
		var ids = [];

		$( "#wp-media-grid .attachments .attachment.selected" ).each( function () {
			var $attachment = $( this );
			ids.push( $attachment.data( "id" ) || $attachment.attr( "data-id" ) );
		} );

		if ( window.wp && wp.media && wp.media.frame && wp.media.frame.state ) {
			try {
				var selection = wp.media.frame.state().get( "selection" );
				if ( selection && selection.each ) {
					selection.each( function ( model ) {
						ids.push( model.get( "id" ) );
					} );
				}
			} catch ( error ) {
				// The DOM collection above remains a compatible fallback.
			}
		}

		return uniqueIds( ids );
	}

	function selectedListIds() {
		var ids = [];
		$( '#posts-filter input[name="media[]"]:checked' ).each( function () {
			ids.push( $( this ).val() );
		} );

		return uniqueIds( ids );
	}

	function createProgressBox( $host ) {
		var $existing = $( ".wp-seo-media-alt-progress" ).first();
		if ( $existing.length ) {
			$existing.remove();
		}

		var $box = $( '<div class="notice notice-info wp-seo-media-alt-progress" role="status" aria-live="polite"></div>' );
		$box.append( '<p class="wp-seo-media-alt-progress-summary"></p>' );
		$box.append( '<p class="wp-seo-media-alt-progress-detail"></p>' );
		$box.append( '<p class="wp-seo-media-alt-progress-actions"></p>' );

		if ( $host && $host.length ) {
			$host.after( $box );
		} else {
			$( "#wpbody-content" ).prepend( $box );
		}

		return $box;
	}

	function renderProgress( $box, state, detail ) {
		var complete = state.generated + state.failed + state.skipped;
		var total = state.ids.length;
		var summary = config.i18n.processing + " " + complete + "/" + total + ". " + state.generated + " " + config.i18n.generated + ", " + state.failed + " " + config.i18n.failed + ", " + state.skipped + " " + config.i18n.skipped + ".";

		$box.removeClass( "notice-success notice-error" ).addClass( "notice-info" );
		$box.find( ".wp-seo-media-alt-progress-summary" ).text( summary );
		$box.find( ".wp-seo-media-alt-progress-detail" ).text( detail || "" );
	}

	function updateVisibleAttachmentFields( attachmentId, data ) {
		if ( window.wp && wp.media && wp.media.attachment ) {
			var attachment = wp.media.attachment( attachmentId );
			if ( attachment && attachment.set ) {
				attachment.set( {
					alt: data.alt_text,
					caption: data.caption,
					description: data.description
				} );
			}
		}

		$( '.wp-seo-generate-image-text[data-attachment-id="' + attachmentId + '"]' ).each( function () {
			var $button = $( this );
			var $scope = $button.closest( ".attachment-details" );

			if ( ! $scope.length ) {
				$scope = $button.closest( ".media-modal" );
			}

			if ( ! $scope.length ) {
				$scope = $button.closest( "#poststuff" );
			}

			if ( ! $scope.length ) {
				$scope = $button.closest( ".compat-item" ).parent();
			}

			$button.text( config.i18n.regenerateButton );
			$scope.find( '[data-setting="alt"] input, input[name*="[image_alt]"]' ).val( data.alt_text ).trigger( "change" );
			$scope.find( '[data-setting="caption"] textarea, textarea[name*="[post_excerpt]"]' ).val( data.caption ).trigger( "change" );
			$scope.find( '[data-setting="description"] textarea, textarea[name*="[post_content]"]' ).val( data.description ).trigger( "change" );
			$button.siblings( ".wp-seo-media-alt-result" ).text( data.alt_text );
		} );
	}

	function normalizeError( response, fallback ) {
		if ( response && response.data && response.data.message ) {
			return response.data.message;
		}

		return fallback || config.i18n.analysisError;
	}

	function processQueue( ids, options ) {
		ids = uniqueIds( ids );
		if ( ! ids.length ) {
			window.alert( config.i18n.noImagesSelected );
			return;
		}

		if ( options.confirm && ! window.confirm( config.i18n.confirmOverwrite ) ) {
			return;
		}

		var state = {
			ids: ids,
			index: 0,
			generated: 0,
			failed: 0,
			skipped: 0,
			failedIds: []
		};
		var $box = createProgressBox( options.$host );
		var $buttons = options.$buttons || $();

		$buttons.prop( "disabled", true ).addClass( "is-busy" );
		renderProgress( $box, state, "" );

		function finish() {
			var message = config.i18n.complete + " " + state.generated + " " + config.i18n.generated + ", " + state.failed + " " + config.i18n.failed + ", " + state.skipped + " " + config.i18n.skipped + ".";

			$box.removeClass( "notice-info notice-error" ).addClass( state.failed ? "notice-warning" : "notice-success" );
			$box.find( ".wp-seo-media-alt-progress-summary" ).text( message );
			$buttons.prop( "disabled", false ).removeClass( "is-busy" );

			if ( state.failedIds.length ) {
				var $retry = $( '<button type="button" class="button button-secondary wp-seo-media-alt-retry"></button>' ).text( config.i18n.retryFailed );
				$retry.on( "click", function () {
					processQueue( state.failedIds, {
						confirm: false,
						$host: $box,
						$buttons: $retry
					} );
				} );
				$box.find( ".wp-seo-media-alt-progress-actions" ).empty().append( $retry );
			}
		}

		function next() {
			if ( state.index >= state.ids.length ) {
				finish();
				return;
			}

			var attachmentId = state.ids[ state.index ];
			state.index += 1;
			renderProgress( $box, state, config.i18n.imageProgress.replace( "%1$d", state.index ).replace( "%2$d", state.ids.length ) );

			$.ajax( {
				url: config.ajaxUrl,
				type: "POST",
				dataType: "json",
				data: {
					action: config.action,
					nonce: config.nonce,
					attachment_id: attachmentId
				}
			} ).done( function ( response ) {
				if ( response && response.success && response.data ) {
					state.generated += 1;
					updateVisibleAttachmentFields( attachmentId, response.data );
					renderProgress( $box, state, response.data.alt_text );
					return;
				}

				var code = response && response.data ? response.data.code : "";
				if ( "not_an_image" === code || "invalid_attachment" === code ) {
					state.skipped += 1;
				} else {
					state.failed += 1;
					state.failedIds.push( attachmentId );
				}

				renderProgress( $box, state, normalizeError( response ) );
			} ).fail( function ( xhr ) {
				state.failed += 1;
				state.failedIds.push( attachmentId );

				var response = xhr.responseJSON || null;
				renderProgress( $box, state, normalizeError( response, config.i18n.requestFailed ) );
			} ).always( function () {
				next();
			} );
		}

		next();
	}

	function ensureGridButton() {
		var $toolbar = $( "#wp-media-grid .media-toolbar-secondary" ).first();
		if ( ! $toolbar.length ) {
			$toolbar = $( "#wp-media-grid .media-toolbar-primary" ).first();
		}

		if ( ! $toolbar.length || $toolbar.find( ".wp-seo-media-alt-grid-button" ).length ) {
			return;
		}

		$( '<button type="button" class="button button-secondary wp-seo-media-alt-grid-button"></button>' )
			.text( config.i18n.gridButton )
			.appendTo( $toolbar );
	}

	function selectedBulkAction( $form ) {
		var action = $form.find( 'select[name="action"]' ).val();
		if ( ! action || "-1" === action ) {
			action = $form.find( 'select[name="action2"]' ).val();
		}

		return action;
	}

	$( document ).on( "click", ".wp-seo-generate-image-text", function ( event ) {
		event.preventDefault();

		var $button = $( this );
		var attachmentId = $button.data( "attachment-id" );
		var $container = $button.closest( ".wp-seo-media-alt-action" );

		processQueue( [ attachmentId ], {
			confirm: false,
			$host: $container,
			$buttons: $button
		} );
	} );

	$( document ).on( "click", ".wp-seo-media-alt-grid-button", function () {
		var $button = $( this );
		processQueue( selectedGridIds(), {
			confirm: true,
			$host: $button.closest( ".media-toolbar" ),
			$buttons: $button
		} );
	} );

	$( document ).on( "submit", "#posts-filter", function ( event ) {
		var $form = $( this );
		if ( "wp_seo_automater_generate_image_text" !== selectedBulkAction( $form ) ) {
			return;
		}

		event.preventDefault();
		processQueue( selectedListIds(), {
			confirm: true,
			$host: $form.find( ".tablenav.top" ),
			$buttons: $form.find( 'input[type="submit"]' )
		} );
	} );

	$( function () {
		ensureGridButton();

		if ( window.MutationObserver && document.getElementById( "wp-media-grid" ) ) {
			new MutationObserver( ensureGridButton ).observe( document.getElementById( "wp-media-grid" ), {
				childList: true,
				subtree: true
			} );
		}
	} );
} )( jQuery );
