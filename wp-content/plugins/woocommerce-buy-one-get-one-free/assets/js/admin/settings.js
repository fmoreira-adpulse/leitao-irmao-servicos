;( function( $ ) {
	/**
	 * Settings actions
	 */
	var wc_bogof_settings = {

		init: function() {
			$('input[name="wc_bogof_cyg_display_on"]').on('click', this.on_display_change );
			this.on_display_change();
		},

		on_display_change: function() {
			var show = 'custom_page' === $('input[name="wc_bogof_cyg_display_on"]:checked').val();
			$('#wc_bogof_cyg_page_id').closest('tr').toggle( show );
			$('#wc_bogof_cyg_title').closest('tr').toggle( ! show );
		}
	};
	wc_bogof_settings.init();
})( jQuery );