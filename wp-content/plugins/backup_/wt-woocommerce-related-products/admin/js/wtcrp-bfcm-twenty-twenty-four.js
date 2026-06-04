( function ($) {
    'use strict';
    $( function () {
        var wtcrp_bfcm_twenty_twenty_four_banner = {
            init: function () { 
                var data_obj = {
                    _wpnonce: wtcrp_bfcm_twenty_twenty_four_banner_js_params.nonce,
                    action: wtcrp_bfcm_twenty_twenty_four_banner_js_params.action,
                    wtcrp_bfcm_twenty_twenty_four_banner_action_type: '',
                };
                $( document ).on( 'click', '.wtcrp-bfcm-banner-2024 .wtcrp-bfcm-cta-button', function (e) { 
                    e.preventDefault(); 
                    var elm = $( this );
                    window.open( wtcrp_bfcm_twenty_twenty_four_banner_js_params.cta_link, '_blank' ); 
                    elm.parents( '.wtcrp-bfcm-banner-2024').hide();
                    data_obj['wtcrp_bfcm_twenty_twenty_four_banner_action_type'] = 3; // Clicked the button.
                    
                    $.ajax({
                        url: wtcrp_bfcm_twenty_twenty_four_banner_js_params.ajax_url,
                        data: data_obj,
                        type: 'POST'
                    });
                }).on( 'click', '.wtcrp-bfcm-banner-2024 .notice-dismiss', function(e) {
                    e.preventDefault();
                    data_obj['wtcrp_bfcm_twenty_twenty_four_banner_action_type'] = 2; // Closed by user
                    
                    $.ajax({
                        url: wtcrp_bfcm_twenty_twenty_four_banner_js_params.ajax_url,
                        data: data_obj,
                        type: 'POST',
                    });
                });
            }
        };
        wtcrp_bfcm_twenty_twenty_four_banner.init();
    });
})( jQuery );