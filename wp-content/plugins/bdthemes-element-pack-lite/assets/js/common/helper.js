var debounce = function (func, wait, immediate) {
    // 'private' variable for instance
    // The returned function will be able to reference this due to closure.
    // Each call to the returned function will share this common timer.
    var timeout;

    // Calling debounce returns a new anonymous function
    return function () {
        // reference the context and args for the setTimeout function
        var context = this,
            args = arguments;

        // Should the function be called now? If immediate is true
        //   and not already in a timeout then the answer is: Yes
        var callNow = immediate && !timeout;

        // This is the basic debounce behaviour where you can call this
        //   function several times, but it will only execute once
        //   [before or after imposing a delay].
        //   Each time the returned function is called, the timer starts over.
        clearTimeout(timeout);

        // Set the new timeout
        timeout = setTimeout(function () {

            // Inside the timeout function, clear the timeout variable
            // which will let the next execution run when in 'immediate' mode
            timeout = null;

            // Check if the function already ran with the immediate flag
            if (!immediate) {
                // Call the original function with apply
                // apply lets you define the 'this' object as well as the arguments
                //    (both captured before setTimeout)
                func.apply(context, args);
            }
        }, wait);

        // Immediate mode and no wait timer? Execute the function..
        if (callNow) func.apply(context, args);
    };
};

function epObserveTarget(target, callback) {
    var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    // Set the rootMargin to trigger when the target is 10% past the viewport
    options.rootMargin = options.rootMargin || '10% 0px 0px 0px';
    var observer = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                callback(entry);

                if (!options.loop)
                    observer.unobserve(entry.target); // Unobserve after the first intersection
            }
        });
    }, options);
    observer.observe(target);
}

/**
 * Start Crypto Currency
 */

function returnCurrencySymbol(currency = null) {
    if (currency === null) return "";
    let currency_symbols = {
        USD: "$", // US Dollar
        EUR: "€", // Euro
        CRC: "₡", // Costa Rican Colón
        GBP: "£", // British Pound Sterling
        ILS: "₪", // Israeli New Sheqel
        INR: "₹", // Indian Rupee
        JPY: "¥", // Japanese Yen
        KRW: "₩", // South Korean Won
        NGN: "₦", // Nigerian Naira
        PHP: "₱", // Philippine Peso
        PLN: "zł", // Polish Zloty
        PYG: "₲", // Paraguayan Guarani
        THB: "฿", // Thai Baht
        UAH: "₴", // Ukrainian Hryvnia
        VND: "₫", // Vietnamese Dong
    };
    if (currency_symbols[currency] !== undefined) {
        return currency_symbols[currency];
    } else {
        return ""; // this is means there is not any
    }
}

/**
 * End Crypto Currency
 */

(function ($) {

    /**
     * Open Offcanvas on Mini Cart Update
     */

    jQuery(document).ajaxComplete(function (event, request, settings) {
        if (request.responseJSON && typeof request.responseJSON.cart_hash !== 'undefined' && request.responseJSON.cart_hash) {
            if (jQuery('.bdt-offcanvas').hasClass('__update_cart')) {
                let id = jQuery('.bdt-offcanvas.__update_cart').attr('id');
                bdtUIkit.util.ready(function () {
                    bdtUIkit.offcanvas('#' + id).show();
                });
            }
        }
    });

    /**
     * /Open Offcanvas on Mini Cart Update
     */

    jQuery(document).ready(function () {

        /** 
        * Start used on Social Share
        */

        jQuery(".bdt-ss-link").on("click", function () {
            var $temp = jQuery("<input>");
            jQuery("body").append($temp);
            $temp.val(jQuery(this).data("url")).select();
            document.execCommand("copy");
            $temp.remove();

            // Update the text to indicate that it has been copied
            jQuery(this).find('.bdt-social-share-title').html(jQuery(this).data('copied'));

            // Reset the text after a delay (e.g., 5 seconds)
            setTimeout(() => {
                jQuery(this).find('.bdt-social-share-title').html(jQuery(this).data('orginal'));
            }, 5000);
        });

        /**
         * end Social Share
         */

        /**
         * Open In a New Tab Feature
         */
        const element = {
            'elementor-widget-bdt-post-grid-tab': {
                'selectors': [
                    '.bdt-post-grid-desc-inner a',
                    '.bdt-post-grid-tab-readmore',
                ]
            },
            'elementor-widget-bdt-post-grid': {
                'selectors': [
                    '.bdt-post-grid-title a',
                    '.bdt-post-grid-readmore',
                ]
            },
            'elementor-widget-bdt-post-card': {
                'selectors': [
                    '.bdt-post-card-title a',
                    '.bdt-post-card-button',
                ]
            },
            'elementor-widget-bdt-post-block': {
                'selectors': [
                    '.bdt-post-block-title a',
                    '.bdt-post-block-read-more',
                ]
            },
            'elementor-widget-bdt-post-block-modern': {
                'selectors': [
                    '.bdt-post-block-modern-title a',
                    '.bdt-post-block-modern-read-more',
                ]
            },
            'elementor-widget-bdt-post-gallery': {
                'selectors': [
                    '.bdt-post-gallery-title-link',
                    '.bdt-gallery-item-link',
                ]
            },
            'elementor-widget-bdt-post-list': {
                'selectors': [
                    '.bdt-title a',
                    '.bdt-image a',
                ]
            },
            'elementor-widget-bdt-post-slider': {
                'selectors': [
                    '.bdt-post-slider-title-wrap a',
                    '.bdt-post-slider-button',
                ]
            },
        };

        Object.keys(element).forEach(function (key) {
            if (jQuery('.' + key).length > 0) {
                if (jQuery('.' + key).data('settings') !== undefined && jQuery('.' + key).data('settings').bdt_link_new_tab === 'yes') {
                    element[key].selectors.forEach(function (selector) {
                        jQuery(selector).attr('target', '_blank');
                    });
                }
            }
        });
        /**
         * /Open In a New Tab Feature
         */

        /** Toggle Pass */

        jQuery('.bdt-pass-input-wrapper').find('i').on('click', function () {
            if (jQuery(this).hasClass('fa-eye')) {
                jQuery(this).toggleClass("fa-eye-slash");
            }
            let input = jQuery(this).closest('.bdt-pass-input-wrapper').find('input');
            if (input.attr("type") == "password") {
                jQuery(input).attr("type", "text");
            } else {
                jQuery(input).attr("type", "password");
            }
        });

        /** /Toggle Pass */

        /** Read more */
        const readMoreWidgetHandler = function readMoreWidgetHandler($scope) {
            if (jQuery($scope).find('.bdt-ep-read-more-text').length) {
                jQuery($scope).find('.bdt-ep-read-more-text').each(function () {
                    var words_limit_settings = $(this).data('read-more');

                    var max_words = words_limit_settings.words_length || 20; // Set the maximum number of words

                    var content = $(this).html(); // Get the full content
                    var words = content.split(/\s+/); // Split content into words

                    if (words.length > max_words) {
                        var short_content = words.slice(0, max_words).join(' '); // Get the first part of the content
                        var long_content = words.slice(max_words).join(' '); // Get the remaining part of the content

                        $(this).html(`
                        ${short_content}
                        <a href="#" class="bdt_read_more">...<br>${ElementPackConfig.words_limit.read_more}</a>
                        <span class="bdt_more_text" style="display:none;">${long_content}</span>
                        <a href="#" class="bdt_read_less" style="display:none;">${ElementPackConfig.words_limit.read_less}</a>
                    `);

                        $(this).find('a.bdt_read_more').click(function (event) {
                            event.preventDefault();
                            $(this).hide(); // Hide the read more link
                            $(this).siblings('.bdt_more_text').show(); // Show the more text
                            $(this).siblings('a.bdt_read_less').show(); // Show the read less link
                        });

                        $(this).find('a.bdt_read_less').click(function (event) {
                            event.preventDefault();
                            $(this).hide(); // Hide the read less link
                            $(this).siblings('.bdt_more_text').hide(); // Hide the more text
                            $(this).siblings('a.bdt_read_more').show(); // Show the read more link
                        });
                    }
                });
            }
        }
        
        const readMoreWidgetsHanlders = {
            'bdt-review-card.default': readMoreWidgetHandler,
            'bdt-review-card-carousel.default': readMoreWidgetHandler,
            'bdt-review-card-grid.default': readMoreWidgetHandler
        };

        $.each(readMoreWidgetsHanlders, function (widgetName, handlerFn) {
            elementorFrontend.hooks.addAction('frontend/element_ready/' + widgetName, handlerFn);
        });
        /** /Read more */

    });

})(jQuery);