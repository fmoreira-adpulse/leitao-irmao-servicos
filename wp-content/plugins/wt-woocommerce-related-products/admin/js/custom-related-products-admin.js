(function ($) {
    'use strict';
    $(function () {
        jQuery(".wt-crp-container #setting-error-settings_updated:eq(1)").hide();
    });

    // Copy to clipboard promo code
    jQuery(document).ready(function () {
        jQuery('.wt_rp_copy_content').click(function (e) {
            e.preventDefault();
            var promo_code = 'PROMO30';
            if (window.clipboardData && window.clipboardData.setData) {
                // IE specific code path to prevent textarea being shown while dialog is visible.
                clipboardData.setData("Text", promo_code);

            } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
                var textarea = document.createElement("textarea");
                textarea.textContent = promo_code;
                textarea.style.position = "fixed";  // Prevent scrolling to bottom of page in MS Edge.
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand("copy");  // Security exception may be thrown by some browsers.
                } catch (ex) {
                    console.warn("Copy to clipboard failed.", ex);
                } finally {
                    document.body.removeChild(textarea);
                }
            }

            jQuery('.wt_rp_copied').show();
            jQuery('.wt_rp_copied').css('color', 'green');
            setTimeout(function () {
                $(".wt_rp_copied").hide();
            }, 500);
        });

        //jQuery for toggle of sections in related products plugin settings 
        var crpAdvancedSettingsTable = jQuery( '.wt-crp-advanced-settings-toggle' ).nextAll( 'table.form-table:first' );
        crpAdvancedSettingsTable.hide();
        jQuery( '.wt-crp-advanced-settings-toggle , .wt-crp-widget-settings-toggle' ).on( 'click', function() {
            var crpArrow = $( this ).find( '.wt-crp-arrow' ); 
            var crpTable = $( this ).nextAll( 'table.form-table:first' );
    
            crpTable.toggle();
    
            if ( crpTable.is( ':visible' ) ) {
                crpArrow.removeClass( 'dashicons-arrow-right' ).addClass( 'dashicons-arrow-down' );
                $(this).addClass('active');
            } else {
                crpArrow.removeClass( 'dashicons-arrow-down' ).addClass( 'dashicons-arrow-right' );
                $(this).removeClass('active');
            }
        });

    });
})(jQuery);
