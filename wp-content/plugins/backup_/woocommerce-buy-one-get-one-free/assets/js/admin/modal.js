/*
	jQuery modal dialog for Buy One Get One Free.
*/
;( function( $ ) {
	$.fn.wc_bogo_modal_dialog = function(option) {

		const methods = {
			addBackdrop: function() {
				if ( ! $('body').find('#wc-bogo-modal-backdrop').length ) {
					$('body').append('<div id="wc-bogo-modal-backdrop"></div>');
				}
			},
			removeBackdrop: function() {
				if ( $('#wc-bogo-modal-backdrop').length ) {
					$('#wc-bogo-modal-backdrop').remove();
				}
			},
			showDialog: function( $dialog ) {
				if ( ! this.triggerEvent($dialog, 'show' ) ) {
					return;
				}
				this.addBackdrop();
				$dialog.removeClass('hidden');
				$dialog.removeAttr('aria-hidden');
				$dialog.attr('tabindex', -1);
				$dialog.trigger('focus');
			},
			hideDialog: function( $dialog ) {
				if ( ! this.triggerEvent($dialog, 'hide' ) ) {
					return;
				}
				$dialog.removeAttr('tabindex');
				$dialog.attr('aria-hidden', true);
				this.removeBackdrop();
				$dialog.addClass('hidden');
			},
			toggleDialog: function( $dialog ) {
				if ( $dialog.hasClass('hidden') ) {
					this.showDialog($dialog);
				} else {
					this.hideDialog($dialog);
				}
			},
			triggerEvent: function( $dialog, eventName ) {
				const event = jQuery.Event(eventName + '.modal.wc-bogo');
				$dialog.trigger(event);
				return ! event.isDefaultPrevented();
			},
			init: function( $dialog ){
				if ( true === $dialog.data('modal-init') ) {
					return;
				}

				const that = this;
				const target = $dialog.attr('id');

				// Show hide via data attributes.
				$('body').on('click', '[data-toggle="wc-bogo-modal"][data-target="#'+target+'"]', function(e){
					e.preventDefault();
					that.showDialog($dialog);
				});

				$('body').on('click', '[data-dismiss="wc-bogo-modal"][data-target="#'+target+'"]', function(e){
					e.preventDefault();
					that.hideDialog($dialog);
				});

				// Hide on 'ESC'
				$dialog.on('keydown', function(e){
					if ( 27 === e.which ) {
						e.preventDefault();
						that.hideDialog($dialog);
					}
				});

				// Hide on click.
				$dialog.on('click', '[data-dismiss="wc-bogo-modal"]', function(event){
					event.preventDefault();
					that.hideDialog($dialog);
				});

				$dialog.data('modal-init', true);
			}
		}

		// Init.
		this.each(function(){
			const $that  = $(this);
			methods.init($that);
			switch (option) {
				case 'show':
					methods.showDialog($that);
					break;
				case 'hide':
					methods.hideDialog($that);
					break;
			}
		});

	};

})( jQuery );
