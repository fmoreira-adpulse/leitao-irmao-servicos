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
  var options =
    arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  // Set the rootMargin to trigger when the target is 10% past the viewport
  options.rootMargin = options.rootMargin || "10% 0px 0px 0px";
  var observer = new IntersectionObserver(function (entries, observer) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        callback(entry);

        if (!options.loop) observer.unobserve(entry.target); // Unobserve after the first intersection
      }
    });
  }, options);
  observer.observe(target);
}

/**
 * Safe HTML
 */
function EP_SAFE_HTML(input) {
  /**
   * Expanded list of allowed tags and attributes for a more flexible sanitization
   */
  const allowedTags = {
    'img': ['src', 'alt', 'title', 'width', 'height', 'style'],
    'a': ['href', 'title', 'target', 'rel'],
    'p': ['class', 'style', 'id'],
    'b': ['class', 'style'],
    'i': ['class', 'style'],
    'u': ['class', 'style'],
    'strong': ['class', 'style'],
    'em': ['class', 'style'],
    'br': [],
    'hr': ['class', 'style'],
    'ul': ['class', 'style'],
    'ol': ['class', 'style'],
    'li': ['class', 'style'],
    'div': ['class', 'style', 'id'],
    'span': ['class', 'style', 'id'],
    'blockquote': ['cite', 'class', 'style'],
    'code': ['class', 'style'],
    'pre': ['class', 'style'],
    'h1': ['class', 'style', 'id'],
    'h2': ['class', 'style', 'id'],
    'h3': ['class', 'style', 'id'],
    'h4': ['class', 'style', 'id'],
    'h5': ['class', 'style', 'id'],
    'h6': ['class', 'style', 'id'],
    'table': ['class', 'style', 'id'],
    'thead': ['class', 'style'],
    'tbody': ['class', 'style'],
    'tfoot': ['class', 'style'],
    'tr': ['class', 'style'],
    'th': ['class', 'style', 'scope'],
    'td': ['class', 'style', 'colspan', 'rowspan'],
  };

  /**
   * Main sanitization process
   */
  const tagPattern = /<\/?([a-zA-Z0-9]+)([^>]*)>/g;
  input = input.replace(tagPattern, (match, tagName, attributes) => {
    tagName = tagName.toLowerCase();

    /**
     * Remove the tag if it's not allowed
     */
    if (!allowedTags.hasOwnProperty(tagName)) {
      return '';
    }

    /**
     * Filter attributes for allowed tags only
     */
    const allowedAttributes = allowedTags[tagName];
    const filteredAttributes = attributes.replace(/([a-zA-Z0-9-]+)\s*=\s*("[^"]*"|'[^']*'|[^\s>]*)/g, (match, attrName, attrValue) => {
      attrName = attrName.toLowerCase();

      /**
       * Only keep attributes in the allowed list and ignore any "on" events or disallowed protocols
       */
      if (!allowedAttributes.includes(attrName) || attrName.startsWith("on") || /^javascript:/i.test(attrValue)) {
        return '';
      }

      return `${attrName}=${attrValue}`;
    });

    return `<${tagName}${filteredAttributes}>`;
  });

  return input;
}
/**
 * /Safe HTML
 */

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
    if (
      request.responseJSON &&
      typeof request.responseJSON.cart_hash !== "undefined" &&
      request.responseJSON.cart_hash
    ) {
      if (jQuery(".bdt-offcanvas").hasClass("__update_cart")) {
        let id = jQuery(".bdt-offcanvas.__update_cart").attr("id");
        bdtUIkit.util.ready(function () {
          bdtUIkit.offcanvas("#" + id).show();
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
      jQuery(this)
        .find(".bdt-social-share-title")
        .html(jQuery(this).data("copied"));

      // Reset the text after a delay (e.g., 5 seconds)
      setTimeout(() => {
        jQuery(this)
          .find(".bdt-social-share-title")
          .html(jQuery(this).data("orginal"));
      }, 5000);
    });

    /**
     * end Social Share
     */

    /**
     * Open In a New Tab Feature
     */
    const element = {
      "elementor-widget-bdt-post-grid-tab": {
        selectors: [
          ".bdt-post-grid-desc-inner a",
          ".bdt-post-grid-tab-readmore",
        ],
      },
      "elementor-widget-bdt-post-grid": {
        selectors: [".bdt-post-grid-title a", ".bdt-post-grid-readmore"],
      },
      "elementor-widget-bdt-post-card": {
        selectors: [".bdt-post-card-title a", ".bdt-post-card-button"],
      },
      "elementor-widget-bdt-post-block": {
        selectors: [".bdt-post-block-title a", ".bdt-post-block-read-more"],
      },
      "elementor-widget-bdt-post-block-modern": {
        selectors: [
          ".bdt-post-block-modern-title a",
          ".bdt-post-block-modern-read-more",
        ],
      },
      "elementor-widget-bdt-post-gallery": {
        selectors: [".bdt-post-gallery-title-link", ".bdt-gallery-item-link"],
      },
      "elementor-widget-bdt-post-list": {
        selectors: [".bdt-title a", ".bdt-image a"],
      },
      "elementor-widget-bdt-post-slider": {
        selectors: [".bdt-post-slider-title-wrap a", ".bdt-post-slider-button"],
      },
    };

    Object.keys(element).forEach(function (key) {
      if (jQuery("." + key).length > 0) {
        if (
          jQuery("." + key).data("settings") !== undefined &&
          jQuery("." + key).data("settings").bdt_link_new_tab === "yes"
        ) {
          element[key].selectors.forEach(function (selector) {
            jQuery(selector).attr("target", "_blank");
          });
        }
      }
    });
    /**
     * /Open In a New Tab Feature
     */

    /** Toggle Pass */

    jQuery(".bdt-pass-input-wrapper")
      .find("i")
      .on("click", function () {
        if (jQuery(this).hasClass("fa-eye")) {
          jQuery(this).toggleClass("fa-eye-slash");
        }
        let input = jQuery(this)
          .closest(".bdt-pass-input-wrapper")
          .find("input");
        if (input.attr("type") == "password") {
          jQuery(input).attr("type", "text");
        } else {
          jQuery(input).attr("type", "password");
        }
      });

    /** /Toggle Pass */
  });
})(jQuery);

/**
 * Start accordion widget script
 */

(function ($, elementor) {
  "use strict";
  var widgetAccordion = function ($scope, $) {
    var $accrContainer = $scope.find(".bdt-ep-accordion-container"),
      $accordion = $accrContainer.find(".bdt-ep-accordion"),
      $activeItem = $accrContainer.find(".bdt-ep-accordion-item.bdt-open");
    if (!$accrContainer.length) {
      return;
    }
    var $settings = $accordion.data("settings"),
      activeHash = $settings.activeHash,
      hashTopOffset = $settings.hashTopOffset,
      hashScrollspyTime = $settings.hashScrollspyTime,
      activeScrollspy = $settings.activeScrollspy,
      closeAllItemsOnMobile = $settings.closeAllItemsOnMobile;

    if (closeAllItemsOnMobile) {
      if (isMobileDevice()) {
        $activeItem.removeClass("bdt-open");
        $activeItem.find(".bdt-ep-accordion-content").prop("hidden", true);
      }
    }

    function isMobileDevice() {
      return window.matchMedia("(max-width: 767px)").matches;
    }

    if (activeScrollspy === null || typeof activeScrollspy === "undefined") {
      activeScrollspy = "no";
    }

    function hashHandler($accordion, hashScrollspyTime, hashTopOffset) {
      if (window.location.hash) {
        if (
          $($accordion).find(
            '[data-title="' + window.location.hash.substring(1) + '"]'
          ).length
        ) {
          var hashTarget = $(
            '[data-title="' + window.location.hash.substring(1) + '"]'
          )
            .closest($accordion)
            .attr("id");

          if (activeScrollspy == "yes") {
            $("html, body")
              .animate(
                {
                  easing: "slow",
                  scrollTop: $("#" + hashTarget).offset().top - hashTopOffset,
                },
                hashScrollspyTime,
                function () {}
              )
              .promise()
              .then(function () {
                bdtUIkit
                  .accordion($accordion)
                  .toggle(
                    $(
                      '[data-title="' + window.location.hash.substring(1) + '"]'
                    ).data("accordion-index"),
                    false
                  );
              });
          } else {
            bdtUIkit
              .accordion($accordion)
              .toggle(
                $(
                  '[data-title="' + window.location.hash.substring(1) + '"]'
                ).data("accordion-index"),
                true
              );
          }
        }
      }
    }
    if (activeHash == "yes") {
      $(window).on("load", function () {
        if (activeScrollspy == "yes") {
          hashHandler($accordion, hashScrollspyTime, hashTopOffset);
        } else {
          bdtUIkit
            .accordion($accordion)
            .toggle(
              $(
                '[data-title="' + window.location.hash.substring(1) + '"]'
              ).data("accordion-index"),
              false
            );
        }
      });
      $($accordion)
        .find(".bdt-ep-accordion-title")
        .off("click")
        .on("click", function (event) {
          window.location.hash = $.trim($(this).attr("data-title"));
          hashHandler($accordion, (hashScrollspyTime = 1000), hashTopOffset);
        });
      $(window).on("hashchange", function (e) {
        hashHandler($accordion, (hashScrollspyTime = 1000), hashTopOffset);
      });
    }
  };

  jQuery(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/bdt-accordion.default",
      widgetAccordion
    );
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/bdt-acf-accordion.default",
      widgetAccordion
    );
  });
})(jQuery, window.elementorFrontend);

/**
 * End accordion widget script
 */

; (function ($, elementor) {

$(window).on('elementor/frontend/init', function () {

    elementorFrontend.hooks.addAction('frontend/element_ready/widget', function ($scope) {

        $scope.hasClass('elementor-element-edit-mode') && $scope.addClass('bdt-background-overlay-yes');

    });

});

}) (jQuery, window.elementorFrontend);
/**
 * Start advanced divider widget script
 */

(function($, elementor) {
    'use strict'; 
    var widgetBusinessHours = function($scope, $) {
        var $businessHoursContainer = $scope.find('.bdt-ep-business-hours'),
        $businessHours = $businessHoursContainer.find('.bdt-ep-business-hours-current-time');
        if (!$businessHoursContainer.length) {
            return;
        }
        var $settings = $businessHoursContainer.data('settings');
        var dynamic_timezone = $settings.dynamic_timezone;
        var timeNotation = $settings.timeNotation;
        var business_hour_style = $settings.business_hour_style;

        if (business_hour_style != 'dynamic') return;

        $(document).ready(function() {
            var offset_val;
            var timeFormat = '%H:%M:%S', timeZoneFormat; 
            var dynamic_timezone = $settings.dynamic_timezone;
            
            if(business_hour_style == 'static'){
                offset_val = $settings.dynamic_timezone_default;
            }else{
                offset_val = dynamic_timezone;
            }

            // console.log(offset_val);
            if(timeNotation == '12h'){
                timeFormat = '%I:%M:%S %p';
            } 
            if (offset_val == '') return;
            var options = {
                // format:'<span class=\"dt\">%A, %d %B %I:%M:%S %P</span>',
                //    format:'<span class=\"dt\">  %I:%M:%S </span>',
                format: timeFormat,
                timeNotation: timeNotation, //'24h',
                am_pm: true,
                utc: true,
                utc_offset: offset_val
            }
            $($businessHoursContainer).find('.bdt-ep-business-hours-current-time').jclock(options);

        });

    };
    jQuery(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-business-hours.default', widgetBusinessHours);
    });
}(jQuery, window.elementorFrontend));

/**
 * End business hours widget script
 */


/**
 * Start contact form widget script
 */

(function ($, elementor) {

    'use strict';

    var widgetSimpleContactForm = function ($scope, $) {

        var $contactForm = $scope.find('.bdt-contact-form .without-recaptcha');

        // Validate tel type input field
        var $inputFieldTel = $scope.find('.bdt-contact-form input[type="tel"]');
        $inputFieldTel.on('input', function(e) {
            this.value = this.value.replace(/[^0-9\+]/g, ''); // Allow only numbers and the plus sign
        });


        if (!$contactForm.length) {
            return;
        }

        $contactForm.submit(function (e) {
            sendContactForm($contactForm);
            return false;
        });

        return false;

    };

    function sendContactForm($contactForm) {
        var langStr = window.ElementPackConfig.contact_form;

        $.ajax({
            url: $contactForm.attr('action'),
            type: 'POST',
            data: $contactForm.serialize(),
            beforeSend: function () {
                bdtUIkit.notification({
                    message: '<div bdt-spinner></div> ' + langStr.sending_msg,
                    timeout: false,
                    status: 'primary'
                });
            },
            success: function (data) {
                var redirectURL = $(data).data('redirect'),
                    isExternal = $(data).data('external'),
                    resetStatus = $(data).data('resetstatus');

                bdtUIkit.notification.closeAll();
                var notification = bdtUIkit.notification({
                    message: data
                });

                if (redirectURL) {
                    if (redirectURL != 'no') {
                        bdtUIkit.util.on(document, 'close', function (evt) {
                            if (evt.detail[0] === notification) {
                                window.open(redirectURL, isExternal);
                            }
                        });
                    }
                }

                localStorage.setItem("bdtCouponCode", $contactForm.attr('id'));

                if (resetStatus) {
                    if (resetStatus !== 'no') {
                        $contactForm[0].reset();
                    }
                }

                // $contactForm[0].reset();
            }
        });
        return false;
    }

    // google invisible captcha
    function elementPackGIC() {

        var langStr = window.ElementPackConfig.contact_form;

        return new Promise(function (resolve, reject) {

            if (grecaptcha === undefined) {
                bdtUIkit.notification({
                    message: '<div bdt-spinner></div> ' + langStr.captcha_nd,
                    timeout: false,
                    status: 'warning'
                });
                reject();
            }

            var response = grecaptcha.getResponse();

            if (!response) {
                bdtUIkit.notification({
                    message: '<div bdt-spinner></div> ' + langStr.captcha_nr,
                    timeout: false,
                    status: 'warning'
                });
                reject();
            }

            var $contactForm = $('textarea.g-recaptcha-response').filter(function () {
                return $(this).val() === response;
            }).closest('form.bdt-contact-form-form');

            var contactFormAction = $contactForm.attr('action');

            if (contactFormAction && contactFormAction !== '') {
                sendContactForm($contactForm);
            } else {
                // console.log($contactForm);
            }

            grecaptcha.reset();

        }); //end promise

    }

    //Contact form recaptcha callback, if needed
    window.elementPackGICCB = elementPackGIC;

    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-contact-form.default', widgetSimpleContactForm);
    });


}(jQuery, window.elementorFrontend));

/**
 * End contact form widget script
 */
/**
 * Start cookie consent widget script
 */

(function ($, elementor) {

	'use strict';

	var widgetCookieConsent = function ($scope, $) {

		var $cookieConsent = $scope.find('.bdt-cookie-consent'),
			$settings = $cookieConsent.data('settings'),
			editMode = Boolean(elementorFrontend.isEditMode()),
			gtagSettings = $cookieConsent.data('gtag');

		if (!$cookieConsent.length || editMode) {
			return;
		}

		window.cookieconsent.initialise($settings);

		$('.cc-compliance').append(
			`<button class="btn-denyCookie bdt-cc-close-btn cc-btn cc-dismiss">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                </svg>
           </button>`
		);

		/**
		 * Dismiss if user click close button
		 */
		$('.cc-btn.btn-denyCookie').on('click', function () {
			$('.bdt-cookie-consent').hide();
			document.cookie = 'element_pack_cookie_widget_gtag=denied; max-age=' + 60 * 60 * 24 * 7 + '; path=/';
			return;
		});

		if (document.cookie.indexOf('element_pack_cookie_widget_gtag=denied') !== -1) {
			$('.bdt-cookie-consent').hide();
			return;
		}

		/**
		 * gtag consent update
		 */
		if (gtagSettings === undefined) {
			return;
		}

		if (gtagSettings.gtag_enabled !== true) {
			return;
		}

		function consentGrantedAdStorage(args) {
			gtag('consent', 'update', args);
		}

		let gtagAttrObj = {
			'ad_user_data': gtagSettings.ad_user_data,
			'ad_personalization': gtagSettings.ad_personalization,
			'ad_storage': gtagSettings.ad_storage,
			'analytics_storage': gtagSettings.analytics_storage,
		};

		$('.cc-btn.cc-dismiss').on('click', function () {
			consentGrantedAdStorage(gtagAttrObj);
		});

		$('.cc-btn.btn-denyCookie').on('click', function () {
			consentGrantedAdStorage({
				'ad_storage': 'denied',
				'analytics_storage': 'denied'
			});
		});
	};

	jQuery(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/bdt-cookie-consent.default', widgetCookieConsent);
	});

}(jQuery, window.elementorFrontend));

/**
 * End cookie consent widget script
 */

/**
 * Start countdown widget script
 */
 
(function ($, elementor) {
    'use strict';
    var widgetCountdown = function ($scope, $) {
        var $countdown = $scope.find('.bdt-countdown-wrapper');
        if (!$countdown.length) {
            return;
        }
        var $settings = $countdown.data('settings'),
            endTime = $settings.endTime,
            loopHours = $settings.loopHours,
            isLogged = $settings.isLogged;

           
 
        var countDownObj = {
            setCookie: function (name, value, hours) {
                var expires = "";
                if (hours) {
                    var date = new Date();
                    date.setTime(date.getTime() + (hours * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + (value || "") + expires + "; path=/";
            },
            getCookie: function (name) {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                }
                return null;
            },
            randomIntFromInterval: function (min, max) { // min and max included 
                return Math.floor(Math.random() * (max - min + 1) + min)
            },
            getTimeSpan: function (date) {
                var total = date - Date.now();

                return {
                    total,
                    seconds: total / 1000 % 60,
                    minutes: total / 1000 / 60 % 60,
                    hours: total / 1000 / 60 / 60 % 24,
                    days: total / 1000 / 60 / 60 / 24
                };
            },
            showPost: function (endTime) {
                jQuery.ajax({
                    url: $settings.adminAjaxUrl,
                    type: 'post',
                    data: {
                        action: 'element_pack_countdown_end',
                        endTime: endTime,
                        couponTrickyId: $settings.couponTrickyId
                    },
                    success: function (data) {
                        if (data == 'ended') {
                            if ($settings.endActionType == 'message') {
                                jQuery($settings.msgId).css({
                                    'display': 'block'
                                });
                                jQuery($settings.id + '-timer').css({
                                    'display': 'none'
                                });
                            }
                            if ($settings.endActionType == 'url' && $settings.redirectUrl) {
                                setInterval(function () {
                                    jQuery(location).attr('href', $settings.redirectUrl);
                                }, $settings.redirectDelay);
                            }
                        } 
                    },
                    error: function () {
                        console.log("Error");
                    }
                });
            },
            couponCode: function(){
                jQuery.ajax({
                    url: $settings.adminAjaxUrl,
                    type: 'post',
                    data: {
                        action: 'element_pack_countdown_end',
                        endTime: endTime,
                        couponTrickyId: $settings.couponTrickyId
                    },
                    success: function (data) {
                    },
                    error: function () {
                    }
                });
            },
            triggerFire : function(){
                jQuery.ajax({
                    url: $settings.adminAjaxUrl,
                    type: 'post',
                    data: {
                        action: 'element_pack_countdown_end',
                        endTime: endTime,
                        couponTrickyId: $settings.couponTrickyId
                    },
                    success: function (data) {
                         if (data == 'ended') {
                             setTimeout(function () {
                                if ($settings.triggerId){
                                    document.getElementById($settings.triggerId).click();
                                    
                                }
                                // document.getElementById($settings.triggerId).click();
                                //  jQuery('#' + $settings.triggerId).trigger('click');
                             }, 1500);
                         }
                    },
                    error: function () {
                        //console.log("Error");
                    }
                });
            },
            clearInterVal: function (myInterVal) {
                clearInterval(myInterVal);
            }

        };


        if (loopHours == false) {
            var countdown = bdtUIkit.countdown($($settings.id + '-timer'), {
                date: $settings.finalTime
            });

            var myInterVal = setInterval(function () {
                var seconds = countDownObj.getTimeSpan(countdown.date).seconds.toFixed(0);
                var finalSeconds = parseInt(seconds);
                if (finalSeconds < 0) {
                    if (!jQuery('body').hasClass('elementor-editor-active')) {
                        jQuery($settings.id + '-msg').css({
                            'display': 'none'
                        });
                        if ($settings.endActionType != 'none') {
                            countDownObj.showPost(endTime)
                        };
                    }
                    countDownObj.clearInterVal(myInterVal);
                }
            }, 1000);
            
            // for coupon code
            if ($settings.endActionType == 'coupon-code') {
                var myInterVal2 = setInterval(function () {
                    var seconds = countDownObj.getTimeSpan(countdown.date).seconds.toFixed(0);
                    var finalSeconds = parseInt(seconds);
                    if (finalSeconds < 0) {
                        if (!jQuery('body').hasClass('elementor-editor-active')) {
                            if ($settings.endActionType == 'coupon-code') {
                                countDownObj.couponCode(endTime)
                            };
                        }
                        countDownObj.clearInterVal(myInterVal2);
                    }
                }, 1000);
            }
            // custom trigger on the end

            if ($settings.triggerId !== false) {
                var myInterVal2 = setInterval(function () {
                    var seconds = countDownObj.getTimeSpan(countdown.date).seconds.toFixed(0);
                    var finalSeconds = parseInt(seconds);
                    if (finalSeconds < 0) {
                        if (!jQuery('body').hasClass('elementor-editor-active')) {
                                countDownObj.triggerFire();
                        }
                        countDownObj.clearInterVal(myInterVal2);
                    }
                }, 1000);
            }
 
        }


        if (loopHours !== false) {
            var now = new Date(),
                randMinute = countDownObj.randomIntFromInterval(6, 14),
                hours = loopHours * 60 * 60 * 1000 - (randMinute * 60 * 1000),
                timer = new Date(now.getTime() + hours),
                loopTime = timer.toISOString(),
                getCookieLoopTime = countDownObj.getCookie('bdtCountdownLoopTime');


            if ((getCookieLoopTime == null || getCookieLoopTime == 'undefined') && isLogged === false) {
                countDownObj.setCookie('bdtCountdownLoopTime', loopTime, loopHours);
            }

            var setLoopTimer;

            if (isLogged === false) {
                setLoopTimer = countDownObj.getCookie('bdtCountdownLoopTime');
            } else {
                setLoopTimer = loopTime;
            }

            $($settings.id + '-timer').attr('data-bdt-countdown', 'date: ' + setLoopTimer);
            var countdown = bdtUIkit.countdown($($settings.id + '-timer'), {
                date: setLoopTimer
            });

            var countdownDate = countdown.date;

            setInterval(function () {
                var seconds = countDownObj.getTimeSpan(countdownDate).seconds.toFixed(0);
                var finalSeconds = parseInt(seconds);
                // console.log(finalSeconds);
                if (finalSeconds > 0) {
                    if ((getCookieLoopTime == null || getCookieLoopTime == 'undefined') && isLogged === false) {
                        countDownObj.setCookie('bdtCountdownLoopTime', loopTime, loopHours);
                        bdtUIkit.countdown($($settings.id + '-timer'), {
                            date: setLoopTimer
                        });
                    }
                }

            }, 1000);


        }


    };
    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-countdown.default', widgetCountdown);
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-countdown.bdt-tiny-countdown', widgetCountdown);
    });
}(jQuery, window.elementorFrontend));

/**
 * End countdown widget script
 */
/**
 * Start bdt custom gallery widget script
 */

(function($, elementor) {

    'use strict';

    var widgetCustomGallery = function($scope, $) {

        var $customGallery = $scope.find('.bdt-custom-gallery'),
            $settings 	= $customGallery.data('settings');
          
        if (!$customGallery.length) {
            return;
        }

        if ($settings.tiltShow == true) {
            var elements = document.querySelectorAll($settings.id + " [data-tilt]");
            VanillaTilt.init(elements);
        }

    };

    jQuery(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-custom-gallery.default', widgetCustomGallery);
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-custom-gallery.bdt-abetis', widgetCustomGallery);
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-custom-gallery.bdt-fedara', widgetCustomGallery);
    });

}(jQuery, window.elementorFrontend));

/**
 * End bdt custom gallery widget script
 */


;(function ($, elementor) {
    'use strict';
    $(window).on('elementor/frontend/init', function () {

        var ModuleHandler = elementorModules.frontend.handlers.Base, FloatingEffect;

        FloatingEffect = ModuleHandler.extend({

            bindEvents: function () {
                this.run();
            },

            getDefaultSettings: function () {
                return {
                    direction: 'alternate',
                    easing   : 'easeInOutSine',
                    loop     : true
                };
            },

            settings: function (key) {
                return this.getElementSettings('ep_floating_effects_' + key);
            },

            onElementChange: debounce(function (prop) {
                if ( prop.indexOf('ep_floating') !== -1 ) {
                    this.anime && this.anime.restart();
                    this.run();
                }
            }, 400),

            run: function () {
                var options = this.getDefaultSettings(),
                    element = this.findElement('.elementor-widget-container').get(0);
                    

                if ( this.settings('translate_toggle') ) {
                    if ( this.settings('translate_x.sizes.from').length !== 0 || this.settings('translate_x.sizes.to').length !== 0 ) {
                        options.translateX = {
                            value   : [this.settings('translate_x.sizes.from') || 0, this.settings('translate_x.sizes.to') || 0],
                            duration: this.settings('translate_duration.size'),
                            delay   : this.settings('translate_delay.size') || 0
                        };
                    }
                    // console.log(options);

                    if ( this.settings('translate_y.sizes.from').length !== 0 || this.settings('translate_y.sizes.to').length !== 0 ) {
                        options.translateY = {
                            value   : [this.settings('translate_y.sizes.from') || 0, this.settings('translate_y.sizes.to') || 0],
                            duration: this.settings('translate_duration.size'),
                            delay   : this.settings('translate_delay.size') || 0
                        };
                    }
                }

                if ( this.settings('rotate_toggle') ) {
                    if ( this.settings('rotate_infinite') !== 'yes' ) {
                        if ( this.settings('rotate_x.sizes.from').length !== 0 || this.settings('rotate_x.sizes.to').length !== 0 ) {
                            options.rotateX = {
                                value   : [this.settings('rotate_x.sizes.from') || 0, this.settings('rotate_x.sizes.to') || 0],
                                duration: this.settings('rotate_duration.size'),
                                delay   : this.settings('rotate_delay.size') || 0
                            };
                        }
                        if ( this.settings('rotate_y.sizes.from').length !== 0 || this.settings('rotate_y.sizes.to').length !== 0 ) {
                            options.rotateY = {
                                value   : [this.settings('rotate_y.sizes.from') || 0, this.settings('rotate_y.sizes.to') || 0],
                                duration: this.settings('rotate_duration.size'),
                                delay   : this.settings('rotate_delay.size') || 0
                            };
                        }
                        if ( this.settings('rotate_z.sizes.from').length !== 0 || this.settings('rotate_z.sizes.to').length !== 0 ) {
                            options.rotateZ = {
                                value   : [this.settings('rotate_z.sizes.from') || 0, this.settings('rotate_z.sizes.to') || 0],
                                duration: this.settings('rotate_duration.size'),
                                delay   : this.settings('rotate_delay.size') || 0
                            };
                        }
                    }
                }

                if ( this.settings('scale_toggle') ) {
                    if ( this.settings('scale_x.sizes.from').length !== 0 || this.settings('scale_x.sizes.to').length !== 0 ) {
                        options.scaleX = {
                            value   : [this.settings('scale_x.sizes.from') || 0, this.settings('scale_x.sizes.to') || 0],
                            duration: this.settings('scale_duration.size'),
                            delay   : this.settings('scale_delay.size') || 0
                        };
                    }
                    if ( this.settings('scale_y.sizes.from').length !== 0 || this.settings('scale_y.sizes.to').length !== 0 ) {
                        options.scaleY = {
                            value   : [this.settings('scale_y.sizes.from') || 0, this.settings('scale_y.sizes.to') || 0],
                            duration: this.settings('scale_duration.size'),
                            delay   : this.settings('scale_delay.size') || 0
                        };
                    }
                }

                if ( this.settings('skew_toggle') ) {
                    if ( this.settings('skew_x.sizes.from').length !== 0 || this.settings('skew_x.sizes.to').length !== 0 ) {
                        options.skewX = {
                            value   : [this.settings('skew_x.sizes.from') || 0, this.settings('skew_x.sizes.to') || 0],
                            duration: this.settings('skew_duration.size'),
                            delay   : this.settings('skew_delay.size') || 0
                        };
                    }
                    if ( this.settings('skew_y.sizes.from').length !== 0 || this.settings('skew_y.sizes.to').length !== 0 ) {
                        options.skewY = {
                            value   : [this.settings('skew_y.sizes.from') || 0, this.settings('skew_y.sizes.to') || 0],
                            duration: this.settings('skew_duration.size'),
                            delay   : this.settings('skew_delay.size') || 0
                        };
                    }
                }

                if ( this.settings('border_radius_toggle') ) {
                    jQuery(element).css('overflow', 'hidden');
                    if ( this.settings('border_radius.sizes.from').length !== 0 || this.settings('border_radius.sizes.to').length !== 0 ) {
                        options.borderRadius = {
                            value   : [this.settings('border_radius.sizes.from') || 0, this.settings('border_radius.sizes.to') || 0],
                            duration: this.settings('border_radius_duration.size'),
                            delay   : this.settings('border_radius_delay.size') || 0
                        };
                    }
                }

                if ( this.settings('opacity_toggle') ) {
                    if ( this.settings('opacity_start.size').length !== 0 || this.settings('opacity_end.size').length !== 0 ) {
                        options.opacity = {
                            value   : [this.settings('opacity_start.size') || 1, this.settings('opacity_end.size') || 0],
                            duration: this.settings('opacity_duration.size'),
                            easing  : 'linear'
                        };
                    }
                }

                if ( this.settings('easing') ) {
                    options.easing = this.settings('easing');
                }


                if ( this.settings('show') ) {
                    options.targets = element;
                    if (
                        this.settings('translate_toggle') ||
                        this.settings('rotate_toggle') ||
                        this.settings('scale_toggle') ||
                        this.settings('skew_toggle') ||
                        this.settings('border_radius_toggle') ||
                        this.settings('opacity_toggle')
                    ) {
                        this.anime = window.anime && window.anime(options);
                    }
                }

            }
        });

        elementorFrontend.hooks.addAction('frontend/element_ready/widget', function ($scope) {
            elementorFrontend.elementsHandler.addHandler(FloatingEffect, {
                $element: $scope
            });
        });
    });
}(jQuery, window.elementorFrontend));

/**
 * Start Flip Box widget script
 */

(function ($, elementor) {
    'use strict';
    var widgetFlipBox = function ($scope, $) {
        var $flipBox = $scope.find('.bdt-flip-box'),
            $settings = $flipBox.data('settings');
        if (!$flipBox.length) {
            return;
        }

        if ('click' === $settings.flipTrigger) {
            $($flipBox).on('click', function () {
                $(this).toggleClass('bdt-active');
            });
        }
        if ('hover' === $settings.flipTrigger) {
            $($flipBox).on('mouseenter', function () {
                $(this).addClass('bdt-active');
            });
            $($flipBox).on('mouseleave', function () {
                $(this).removeClass('bdt-active');
            });
        }


    };
    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-flip-box.default', widgetFlipBox);
    });
}(jQuery, window.elementorFrontend));

/**
 * End Flip Box widget script
 */
/**
 * Start image accordion widget script
 */

(function ($, elementor) {
  "use strict";

  var widgetImageAccordion = function ($scope, $) {
    var $imageAccordion = $scope.find(".bdt-ep-image-accordion"),
      $settings = $imageAccordion.data("settings");

    var accordionItem = $imageAccordion.find(".bdt-ep-image-accordion-item");
    var totalItems = $imageAccordion.children().length;

    if (
      $settings.activeItem == true &&
      $settings.activeItemNumber <= totalItems
    ) {
      $imageAccordion
        .find(".bdt-ep-image-accordion-item")
        .removeClass("active");
      $imageAccordion
        .children()
        .eq($settings.activeItemNumber - 1)
        .addClass("active");
    }

    $(accordionItem).on($settings.mouse_event, function () {
      $(this).siblings().removeClass("active");
      $(this).addClass("active");
    });

    if ($settings.activeItem != true) {
      $("body").on($settings.mouse_event, function (e) {
        if (
          e.target.$imageAccordion == "bdt-ep-image-accordion" ||
          $(e.target).closest(".bdt-ep-image-accordion").length
        ) {
        } else {
          $imageAccordion
            .find(".bdt-ep-image-accordion-item")
            .removeClass("active");
        }
      });
    }

    // Swiping
    function handleSwipe(event) {
      var deltaX = touchendX - touchstartX;

      var hasPrev = $(event.currentTarget).prev();
      var hasNext = $(event.currentTarget).next();
      // Horizontal swipe
      if (deltaX > 50) {
        // Swiped right
        if (hasPrev.length) {
          $(accordionItem).removeClass("active");
        }
        $(event.currentTarget).prev().addClass("active");
      } else if (deltaX < -50) {
        // Swiped left
        if (hasNext.length) {
          $(accordionItem).removeClass("active");
        }
        $(event.currentTarget).next().addClass("active");
      }
    }

    if ($settings.swiping) {
      var touchstartX = 0;
      var touchendX = 0;
      var touchstartY = 0;
      var touchendY = 0;

      $(accordionItem).on("touchstart", function (event) {
        touchstartX = event.changedTouches[0].screenX;
      });

      $(accordionItem).on("touchend", function (event) {
        touchendX = event.changedTouches[0].screenX;
        handleSwipe(event);
      });
    }

    // Inactive Item
    if ($settings.inactiveItemOverlay) {
      console.log("inactiveItemOverlay");
      $(accordionItem).on($settings.mouse_event, function (event) {
        event.stopPropagation();
        if ($(this).hasClass("active")) {
          $(this)
            .removeClass("bdt-inactive")
            .siblings()
            .addClass("bdt-inactive");
        } else {
          $(this).siblings().removeClass("bdt-inactive");
        }
      });
      $(document).on($settings.mouse_event, function () {
        $(accordionItem).removeClass("bdt-inactive");
      });
    }
  };

  jQuery(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/bdt-image-accordion.default",
      widgetImageAccordion
    );
  });
})(jQuery, window.elementorFrontend);

/**
 * End image accordion widget script
 */

/**
 * Start image compare widget script
 */

( function( $, elementor ) {

	'use strict';

	var widgetImageCompare = function( $scope, $ ) {
        var $image_compare_main = $scope.find('.bdt-image-compare');
        var $image_compare      = $scope.find('.image-compare');
        if ( !$image_compare.length ) {
            return;
        }

        var $settings        = $image_compare.data('settings');
        
        var 
        default_offset_pct   = $settings.default_offset_pct,
        orientation          = $settings.orientation,
        before_label         = $settings.before_label,
        after_label          = $settings.after_label,
        no_overlay           = $settings.no_overlay,
        on_hover             = $settings.on_hover,
        add_circle_blur      = $settings.add_circle_blur,
        add_circle_shadow    = $settings.add_circle_shadow,
        add_circle           = $settings.add_circle,
        smoothing            = $settings.smoothing,
        smoothing_amount     = $settings.smoothing_amount,
        bar_color            = $settings.bar_color,
        move_slider_on_hover = $settings.move_slider_on_hover;
      
        var viewers = document.querySelectorAll('#' + $settings.id);
  
        var options = {

            // UI Theme Defaults
            controlColor : bar_color,
            controlShadow: add_circle_shadow,
            addCircle    : add_circle,
            addCircleBlur: add_circle_blur,
          
            // Label Defaults
            showLabels   : no_overlay,
            labelOptions : {
              before       : before_label,
              after        : after_label,
              onHover      : on_hover
            },
          
            // Smoothing
            smoothing      : smoothing,
            smoothingAmount: smoothing_amount,
          
            // Other options
            hoverStart     : move_slider_on_hover,
            verticalMode   : orientation,
            startingPoint  : default_offset_pct,
            fluidMode      : false
          };

          viewers.forEach(function (element){
            var view = new ImageCompare(element, options).mount();
          });

	};

	jQuery(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bdt-image-compare.default', widgetImageCompare );
	});

}( jQuery, window.elementorFrontend ) );

/**
 * End image compare widget script
 */


/**
 * Start image magnifier widget script
 */

( function( $, elementor ) {

	'use strict';

	var widgetImageMagnifier = function( $scope, $ ) {

		var $imageMagnifier = $scope.find( '.bdt-image-magnifier' ),
            settings        = $imageMagnifier.data('settings'),
            magnifier       = $imageMagnifier.find('> .bdt-image-magnifier-image');

        if ( ! $imageMagnifier.length ) {
            return;
        }

        $(magnifier).ImageZoom(settings);

	};


	jQuery(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bdt-image-magnifier.default', widgetImageMagnifier );
	});

}( jQuery, window.elementorFrontend ) );

/**
 * End image magnifier widget script
 */


/**
 * Start price table widget script
 */

 ( function( $, elementor ) {

	'use strict';

	var widgetImageStack = function( $scope, $ ) {

		var $imageStack = $scope.find( '.bdt-image-stack' );

        if ( ! $imageStack.length ) {
            return;
        }

        var $tooltip = $imageStack.find('.bdt-tippy-tooltip'),
        	widgetID = $scope.data('id');
		
		$tooltip.each( function( index ) {
			tippy( this, {
				allowHTML: true,
				theme: 'bdt-tippy-' + widgetID
			});				
		});

    };
    
	jQuery(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bdt-image-stack.default', widgetImageStack );
	});

}( jQuery, window.elementorFrontend ) );

/**
 * End price table widget script
 */
/**
 * Start logo grid widget script
 */

(function($, elementor) {

    'use strict'; 

    var widgetLogoGrid = function($scope, $) {

        var $logogrid = $scope.find('.bdt-logo-grid-wrapper');

        if (!$logogrid.length) {
            return;
        }

        var $tooltip = $logogrid.find('> .bdt-tippy-tooltip'),
            widgetID = $scope.data('id');

        $tooltip.each(function(index) {
            tippy(this, {
                allowHTML: true,
                theme: 'bdt-tippy-' + widgetID
            });
        });

    };


    jQuery(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-logo-grid.default', widgetLogoGrid);
    });

}(jQuery, window.elementorFrontend));

/**
 * Start open street map widget script
 */

( function( $, elementor ) {

	'use strict';

	var widgetOpenStreetMap = function( $scope, $ ) {

		var $openStreetMap = $scope.find( '.bdt-open-street-map' ),
            settings       = $openStreetMap.data('settings'),
            markers        = $openStreetMap.data('map_markers'),
            tileSource = '';

        if ( ! $openStreetMap.length ) {
            return;
        }

        var avdOSMap = L.map($openStreetMap[0], {
                zoomControl: settings.zoomControl,
                scrollWheelZoom: false
            }).setView([
                    settings.lat,
                    settings.lng
                ], 
                settings.zoom
            );

        if (settings.mapboxToken !== '') {
          tileSource = 'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=' + settings.mapboxToken;
            L.tileLayer( tileSource, {
                maxZoom: 18,
                attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery &copy; <a href="https://www.mapbox.com/">Mapbox</a>',
                id: 'mapbox/streets-v11',
                tileSize: 512,
                zoomOffset: -1
            }).addTo(avdOSMap);
        } else {
            L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(avdOSMap);
        }


        for (var i in markers) { 
            if( (markers[i]['iconUrl']) != '' && typeof (markers[i]['iconUrl']) !== 'undefined'){ 
                var LeafIcon = L.Icon.extend({
                    options: {
                        iconSize   : [25, 41],
                        iconAnchor : [12, 41],
                        popupAnchor: [2, -41]
                    }
                });
                var greenIcon = new LeafIcon({iconUrl: markers[i]['iconUrl'] });
                L.marker( [markers[i]['lat'], markers[i]['lng']], {icon: greenIcon} ).bindPopup(markers[i]['infoWindow']).addTo(avdOSMap);
            } else {
                if( (markers[i]['lat']) != '' && typeof (markers[i]['lat']) !== 'undefined'){ 
                    L.marker( [markers[i]['lat'], markers[i]['lng']] ).bindPopup(markers[i]['infoWindow']).addTo(avdOSMap);
                }
            }
        }

	};


	jQuery(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bdt-open-street-map.default', widgetOpenStreetMap );
	});

}( jQuery, window.elementorFrontend ) );

/**
 * End open street map widget script
 */


/**
 * Start panel slider widget script
 */

(function ($, elementor) {

	'use strict';

	var widgetPanelSlider = function ($scope, $) {

		var $slider = $scope.find('.bdt-panel-slider');

		if (!$slider.length) {
			return;
		}

		var $sliderContainer = $slider.find('.swiper-carousel'),
			$settings = $slider.data('settings'),
			$widgetSettings = $slider.data('widget-settings');

		const Swiper = elementorFrontend.utils.swiper;
		initSwiper();
		async function initSwiper() {
			var swiper = await new Swiper($sliderContainer, $settings);

			if ($settings.pauseOnHover) {
				$($sliderContainer).hover(function () {
					(this).swiper.autoplay.stop();
				}, function () {
					(this).swiper.autoplay.start();
				});
			}
		};

		if ($widgetSettings.mouseInteractivity == true) {
			setTimeout(() => {
				var data = $($widgetSettings.id).find('.bdt-panel-slide-item');
				$(data).each((index, element) => {
					var scene = $(element).get(0);
					var parallaxInstance = new Parallax(scene, {
						selector: '.bdt-panel-slide-thumb',
						hoverOnly: true,
						pointerEvents: true
					});
				});
			}, 2000);
		}

	};


	jQuery(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/bdt-panel-slider.default', widgetPanelSlider);
		elementorFrontend.hooks.addAction('frontend/element_ready/bdt-panel-slider.bdt-middle', widgetPanelSlider);
		elementorFrontend.hooks.addAction('frontend/element_ready/bdt-panel-slider.always-visible', widgetPanelSlider);
	});

}(jQuery, window.elementorFrontend));

/**
 * End panel slider widget script
 */
/**
 * Start progress pie widget script
 */

(function ($, elementor) {

    'use strict';

    var widgetProgressPie = function ($scope, $) {

        var $progressPie = $scope.find('.bdt-progress-pie');

        if (!$progressPie.length) {
            return;
        }

        epObserveTarget($scope[0], function () {
            var $this = $($progressPie);

            $this.asPieProgress({
                namespace: 'pieProgress',
                classes: {
                    svg: 'bdt-progress-pie-svg',
                    number: 'bdt-progress-pie-number',
                    content: 'bdt-progress-pie-content'
                }
            });

            $this.asPieProgress('start');

        }, {
            root: null, // Use the viewport as the root
            rootMargin: '0px', // No margin around the root
            threshold: 1 // 80% visibility (1 - 0.8)
        });

    };


    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-progress-pie.default', widgetProgressPie);
    });

}(jQuery, window.elementorFrontend));

/**
 * End progress pie widget script
 */


/**
 * Start reading progress widget script
 */

 (function($, elementor) {

    'use strict';

    var readingProgressWidget = function($scope, $) {

        var $readingProgress = $scope.find('.bdt-reading-progress');

        if (!$readingProgress.length) {
            return;
        }
        var $settings = $readingProgress.data('settings');

        jQuery(document).ready(function(){

            var settings = {
                borderSize: 10,
                mainBgColor: '#E6F4F7',
                lightBorderColor: '#A2ECFB',
                darkBorderColor: '#39B4CC'
            };

            var colorBg = $settings.progress_bg;  //'red'
            var progressColor = $settings.scroll_bg; //'green';
            var innerHeight, offsetHeight, netHeight,
            self = this,
            container = $($readingProgress),
            borderContainer = 'bdt-reading-progress-border',
            circleContainer = 'bdt-reading-progress-circle',
            textContainer = 'bdt-reading-progress-text';

            var getHeight = function () {
                innerHeight = window.innerHeight;
                offsetHeight = document.body.offsetHeight;
                netHeight = offsetHeight - innerHeight;
            };

            var addEvent = function () {
                var e = document.createEvent('Event');
                e.initEvent('scroll', false, false);
                window.dispatchEvent(e);
            };
            var updateProgress = function (percnt) {
                var per = Math.round(100 * percnt);
                var deg = per * 360 / 100;
                if (deg <= 180) {
                    $('.' + borderContainer, container).css('background-image', 'linear-gradient(' + (90 + deg) + 'deg, transparent 50%, ' + colorBg + ' 50%),linear-gradient(90deg, ' + colorBg + ' 50%, transparent 50%)');
                } else {
                    $('.' + borderContainer, container).css('background-image', 'linear-gradient(' + (deg - 90) + 'deg, transparent 50%, ' + progressColor + ' 50%),linear-gradient(90deg, ' + colorBg + ' 50%, transparent 50%)');
                }
                $('.' + textContainer, container).text(per + '%');
            };
            var prepare = function () {
                    $(container).html("<div class='" + borderContainer + "'><div class='" + circleContainer + "'><span class='" + textContainer + "'></span></div></div>");

                    $('.' + borderContainer, container).css({
                        'background-color': progressColor,
                        'background-image': 'linear-gradient(91deg, transparent 50%,' + settings.lightBorderColor + '50%), linear-gradient(90deg,' + settings.lightBorderColor + '50%, transparent 50%'
                    });
                    $('.' + circleContainer, container).css({
                        'width': settings.width - settings.borderSize,
                        'height': settings.height - settings.borderSize
                    });

                };
            var init = function () {
                    prepare();
                    $(window).on('scroll', function () {
                        var getOffset = window.pageYOffset || document.documentElement.scrollTop,
                        per = Math.max(0, Math.min(1, getOffset / netHeight));
                        updateProgress(per);
                    });
                    $(window).on('resize', function () {
                        getHeight();
                        addEvent();
                    });
                    $(window).on('load', function () {
                        getHeight();
                        addEvent();
                    });
                };
                 init();
            });

    };
    //	start progress with cursor
    var readingProgressCursorSkin = function($scope, $) {

        var $readingProgress = $scope.find('.bdt-progress-with-cursor');

        if (!$readingProgress.length) {
            return;
        }

        document.getElementsByTagName('body')[0].addEventListener('mousemove', function(n) {
            t.style.left = n.clientX + 'px';
            t.style.top = n.clientY + 'px';
            e.style.left = n.clientX + 'px';
            e.style.top = n.clientY + 'px';
            i.style.left = n.clientX + 'px';
            i.style.top = n.clientY + 'px';
        });
        var t = document.querySelector('.bdt-cursor'),
        e = document.querySelector('.bdt-cursor2'),
        i = document.querySelector('.bdt-cursor3');

        function n(t) {
            e.classList.add('hover'), i.classList.add('hover');
        }

        function s(t) {
            e.classList.remove('hover'), i.classList.remove('hover');
        }
        s();
        for (var r = document.querySelectorAll('.hover-target'), a = r.length - 1; a >= 0; a--) {
            o(r[a]);
        }

        function o(t) {
            t.addEventListener('mouseover', n);
            t.addEventListener('mouseout', s);
        }

        $(document).ready(function() {
            //Scroll indicator
            var progressPath = document.querySelector('.bdt-progress-wrap path');
            var pathLength = progressPath.getTotalLength();
            progressPath.style.transition = progressPath.style.WebkitTransition = 'none';
            progressPath.style.strokeDasharray = pathLength + ' ' + pathLength;
            progressPath.style.strokeDashoffset = pathLength;
            progressPath.getBoundingClientRect();
            progressPath.style.transition = progressPath.style.WebkitTransition = 'stroke-dashoffset 10ms linear';
            var updateProgress = function() {
                var scroll = $(window).scrollTop();
                var height = $(document).height() - $(window).height();
                var progress = pathLength - (scroll * pathLength / height);
                progressPath.style.strokeDashoffset = progress;
            };
            updateProgress();
            jQuery(window).on('scroll', updateProgress);


        });

    };
    //	end  progress with cursor

    // start progress horizontal 


    var readingProgressHorizontalSkin = function($scope, $) {

        var $readingProgress = $scope.find('.bdt-horizontal-progress');

        if (!$readingProgress.length) {
            return;
        }

        $('#bdt-progress').progress({ size: '3px', wapperBg: '#eee', innerBg: '#DA4453' });

    };

    // end progress horizontal 

    // start  progress back to top 


    var readingProgressBackToTopSkin = function($scope, $) {

        var $readingProgress = $scope.find('.bdt-progress-with-top');

        if (!$readingProgress.length) {
            return;
        }

        var progressPath = document.querySelector('.bdt-progress-wrap path');
        var pathLength = progressPath.getTotalLength();
        progressPath.style.transition = progressPath.style.WebkitTransition = 'none';
        progressPath.style.strokeDasharray = pathLength + ' ' + pathLength;
        progressPath.style.strokeDashoffset = pathLength;
        progressPath.getBoundingClientRect();
        progressPath.style.transition = progressPath.style.WebkitTransition = 'stroke-dashoffset 10ms linear';
        var updateProgress = function() {
            var scroll = jQuery(window).scrollTop();
            var height = jQuery(document).height() - jQuery(window).height();
            var progress = pathLength - (scroll * pathLength / height);
            progressPath.style.strokeDashoffset = progress;
        };
        updateProgress();
        jQuery(window).on('scroll', updateProgress);
        var offset = 50;
        var duration = 550;
        jQuery(window).on('scroll', function() {
            if (jQuery(this).scrollTop() > offset) {
                jQuery('.bdt-progress-wrap').addClass('active-progress');
            } else {
                jQuery('.bdt-progress-wrap').removeClass('active-progress');
            }
        });
        jQuery('.bdt-progress-wrap').on('click', function(event) {
            event.preventDefault();
            jQuery('html, body').animate({ scrollTop: 0 }, duration);
            return false;
        });

    };

    // end progress back to top

    jQuery(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-reading-progress.default', readingProgressWidget);
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-reading-progress.bdt-progress-with-cursor', readingProgressCursorSkin);
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-reading-progress.bdt-horizontal-progress', readingProgressHorizontalSkin);
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-reading-progress.bdt-back-to-top-with-progress', readingProgressBackToTopSkin);
    });

}(jQuery, window.elementorFrontend));

/**
 * End reading progress widget script
 */


(function ($, elementor) {
  $(window).on("elementor/frontend/init", function () {
    let ModuleHandler = elementorModules.frontend.handlers.Base,
      ReadingTimer;

    ReadingTimer = ModuleHandler.extend({
      bindEvents: function () {
        this.run();
      },
      getDefaultSettings: function () {
        return {
          allowHTML: true,
        };
      },

      settings: function (key) {
        return this.getElementSettings("reading_timer_" + key);
      },

      calculateReadingTime: function (ReadingContent) {
        let wordCount = ReadingContent.split(/\s+/).filter(function (word) {
            return word !== "";
          }).length,
          averageReadingSpeed = this.settings("avg_words_per_minute")
            ? this.settings("avg_words_per_minute").size
            : 200,
          readingTime = Math.floor(wordCount / averageReadingSpeed),
          reading_seconds = Math.floor(
            (wordCount % averageReadingSpeed) / (averageReadingSpeed / 60)
          ),
          minText = this.settings("minute_text")
            ? this.settings("minute_text")
            : "min read",
          secText = this.settings("seconds_text")
            ? this.settings("seconds_text")
            : "sec read";

        if (wordCount >= averageReadingSpeed) {
          return `${readingTime} ${minText}`;
        } else {
          return `${reading_seconds} ${secText}`;
        }
      },

      run: function () {
        const widgetID = this.$element.data("id"),
          widgetContainer = `.elementor-element-${widgetID} .bdt-reading-timer`,
          contentSelector = this.settings("content_id");
        let minText = this.settings("minute_text")
          ? this.settings("minute_text")
          : "min read";

        var editMode = Boolean(elementorFrontend.isEditMode());
        if (editMode) {
          $(widgetContainer).append("2 " + minText + "");
          return;
        }
        if (contentSelector) {
          ReadingContent = $(document).find(`#${contentSelector}`).text();
          var readTime = this.calculateReadingTime(ReadingContent);
          $(widgetContainer).append(readTime);
        } else return;
      },
    });

    elementorFrontend.hooks.addAction(
      "frontend/element_ready/bdt-reading-timer.default",
      function ($scope) {
        elementorFrontend.elementsHandler.addHandler(ReadingTimer, {
          $element: $scope,
        });
      }
    );
  });
})(jQuery, window.elementorFrontend);

/**
 * Start twitter carousel widget script
 */

(function ($, elementor) {

	'use strict';

	var widgetReviewCardCarousel = function ($scope, $) {

		var $reviewCardCarousel = $scope.find('.bdt-review-card-carousel');

		if (!$reviewCardCarousel.length) {
			return;
		}

		var $reviewCardCarouselContainer = $reviewCardCarousel.find('.swiper-carousel'),
			$settings = $reviewCardCarousel.data('settings');

		const Swiper = elementorFrontend.utils.swiper;
		initSwiper();
		async function initSwiper() {
			var swiper = await new Swiper($reviewCardCarouselContainer, $settings); // this is an example

			if ($settings.pauseOnHover) {
				$($reviewCardCarouselContainer).hover(function () {
					(this).swiper.autoplay.stop();
				}, function () {
					(this).swiper.autoplay.start();
				});
			}

		};


	};


	jQuery(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/bdt-review-card-carousel.default', widgetReviewCardCarousel);
	});

}(jQuery, window.elementorFrontend));

/**
 * End twitter carousel widget script
 */


/**
 * Start scroll button widget script
 */

( function( $, elementor ) {

	'use strict';

	var widgetScrollButton = function( $scope, $ ) {
	    
			var $scrollButton = $scope.find('.bdt-scroll-button'),
			$selector         = $scrollButton.data('selector'),
			$settings         =  $scrollButton.data('settings');

	    if ( ! $scrollButton.length ) {
	    	return;
	    }

	    //$($scrollButton).find('.bdt-scroll-button').unbind();
	    
	    if ($settings.HideOnBeforeScrolling == true) {

			$(window).scroll(function() {
			  if ($(window).scrollTop() > 300) {
			    $scrollButton.css("opacity", "1");
			  } else {
			    $scrollButton.css("opacity", "0");
			  }
			});
	    }

	    $($scrollButton).on('click', function(event){
	    	event.preventDefault();
	    	bdtUIkit.scroll($scrollButton, $settings ).scrollTo($($selector));

	    });

	};

	jQuery(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bdt-scroll-button.default', widgetScrollButton );
	});

}( jQuery, window.elementorFrontend ) );

/**
 * End scroll button widget script
 */


/**
 * Start search widget script
 */

(function ($, elementor) {
  'use strict';
  var serachTimer;
  var widgetAjaxSearch = function ($scope, $) {
    var $searchContainer = $scope.find('.bdt-search-container'),
      $searchWidget = $scope.find('.bdt-ajax-search');

    $($scope).find('.bdt-navbar-dropdown-close').on('click', function () {
      bdtUIkit.drop($scope.find('.bdt-navbar-dropdown')).hide();
    });

    let $search;

    if (!$searchWidget.length) {
      return;
    }

    var $resultHolder = $($searchWidget).find('.bdt-search-result'),
      $settings = $($searchWidget).data('settings'),
      $connectSettings = $($searchContainer).data('settings'),
      $target = $($searchWidget).attr('anchor-target');

    if ('yes' === $target) {
      $target = '_blank';
    } else {
      $target = '_self';
    }

    clearTimeout(serachTimer);

    if ($connectSettings && $connectSettings.element_connect) {
      $($connectSettings.element_selector).hide();
    }

    $($searchWidget).on('keyup keypress', function (e) {
      var keyCode = e.keyCode || e.which;
      if (keyCode === 13) {
        e.preventDefault();
        return false;
      }
    });

    $searchWidget.find('.bdt-search-input').keyup(function () {
      $search = $(this).val();
      serachTimer = setTimeout(function () {
        $($searchWidget).addClass('bdt-search-loading');
        jQuery.ajax({
          url: window.ElementPackConfig.ajaxurl,
          type: 'post',
          data: {
            action: 'element_pack_search',
            s: $search,
            settings: $settings,
          },
          success: function (response) {
            var response = $.parseJSON(response);

            if (response.results.length > 0) {
              if ($search.length >= 3) {
                var output = `<div class="bdt-search-result-inner">
                          <h3 class="bdt-search-result-header">${window.ElementPackConfig.search.search_result}<i class="ep-icon-close bdt-search-result-close-btn"></i></h3>
                          <ul class="bdt-list bdt-list-divider">`;
                for (let i = 0; i < response.results.length; i++) {
                  const element = response.results[i];
                  output += `<li class="bdt-search-item" data-url="${element.url}">
                            <a href="${element.url}" target="${$target}">
                            <div class="bdt-search-title">${element.title}</div>
                            <div class="bdt-search-text">${element.text}</div>
                            </a>
                          </li>`;
                }
                output += `</ul><a class="bdt-search-more">${window.ElementPackConfig.search.more_result}</a></div>`;

                $resultHolder.html(output);
                $resultHolder.show();
                $(".bdt-search-result-close-btn").on("click", function (e) {
                  $(".bdt-search-result").hide();
                  $(".bdt-search-input").val("");
                });

                $($searchWidget).removeClass("bdt-search-loading");
                $(".bdt-search-more").on("click", function (event) {
                  event.preventDefault();
                  $($searchWidget).submit();
                });
              } else {
                $resultHolder.hide();
              }
            } else {
              if ($search.length > 3) {
                var not_found = `<div class="bdt-search-result-inner">
                                  <h3 class="bdt-search-result-header">${window.ElementPackConfig.search.search_result}<i class="ep-icon-close bdt-search-result-close-btn"></i></h3>
                                  <div class="bdt-search-text">${$search} ${window.ElementPackConfig.search.not_found}</div>
                                </div>`;
                $resultHolder.html(not_found);
                $resultHolder.show();
                $(".bdt-search-result-close-btn").on("click", function (e) {
                  $(".bdt-search-result").hide();
                  $(".bdt-search-input").val("");
                });
                $($searchWidget).removeClass("bdt-search-loading");

                if ($connectSettings && $connectSettings.element_connect) {
                  $resultHolder.hide();
                  setTimeout(function () {
                    $($connectSettings.element_selector).show();
                  }, 1500);
                }

              } else {
                $resultHolder.hide();
                $($searchWidget).removeClass("bdt-search-loading");
              }

            }
          }
        });
      }, 450);
    });

  };


  jQuery(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/bdt-search.default', widgetAjaxSearch);
  });

  //window.elementPackAjaxSearch = widgetAjaxSearch;

})(jQuery, window.elementorFrontend);

/**
 * End search widget script
 */
/**
 * Start slider widget script
 */

( function( $, elementor ) {

	'use strict';

	var widgetSlider = function( $scope, $ ) {

		var $slider = $scope.find( '.bdt-slider' );
				
        if ( ! $slider.length ) {
            return;
        }

        var $sliderContainer = $slider.find('.swiper-carousel'),
			$settings 		 = $slider.data('settings');

		// Access swiper class
        const Swiper = elementorFrontend.utils.swiper;
        initSwiper();
        
        async function initSwiper() {

			var swiper = await new Swiper($sliderContainer, $settings);

			if ($settings.pauseOnHover) {
				 $($sliderContainer).hover(function() {
					(this).swiper.autoplay.stop();
				}, function() {
					(this).swiper.autoplay.start();
				});
			}
		};

	};


	jQuery(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bdt-slider.default', widgetSlider );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bdt-acf-slider.default', widgetSlider );
	});

}( jQuery, window.elementorFrontend ) );

/**
 * End slider widget script
 */


/**
 * Start twitter carousel widget script
 */

( function( $, elementor ) {

	'use strict';

	var widgetStaticCarousel = function( $scope, $ ) {

		var $StaticCarousel = $scope.find( '.bdt-static-carousel' );
				
        if ( ! $StaticCarousel.length ) {
            return;
        }

		var $StaticCarouselContainer = $StaticCarousel.find('.swiper-carousel'),
			$settings 		 = $StaticCarousel.data('settings');

		// Access swiper class
        const Swiper = elementorFrontend.utils.swiper;
        initSwiper();
        
        async function initSwiper() {

			var swiper = await new Swiper($StaticCarouselContainer, $settings);

			if ($settings.pauseOnHover) {
				 $($StaticCarouselContainer).hover(function() {
					(this).swiper.autoplay.stop();
				}, function() {
					(this).swiper.autoplay.start();
				});
			}
		};

	};


	jQuery(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bdt-static-carousel.default', widgetStaticCarousel );
	});

}( jQuery, window.elementorFrontend ) );

/**
 * End twitter carousel widget script
 */


/**
 * Start post grid tab widget script
 */

;
(function ($, elementor) {

	'use strict';

	var widgetStaticPostTab = function ($scope, $) {

		var $postGridTab = $scope.find('.bdt-static-grid-tab'),
			gridTab = $postGridTab.find('.gridtab');

		var $settings = $postGridTab.data('settings');

		if (!$postGridTab.length) {
			return;
		}

		$(gridTab).gridtab($settings);

	};


	jQuery(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/bdt-static-grid-tab.default', widgetStaticPostTab);
	});

}(jQuery, window.elementorFrontend));

/**
 * End post grid tab widget script
 */
/**
 * Start step flow widget script
 */

(function ($, elementor) {

    'use strict';

    var widgetStepFlow = function ($scope, $) {

        var $avdDivider = $scope.find('.bdt-step-flow'),
            divider = $($avdDivider).find('.bdt-title-separator-wrapper > img');

        if (!$avdDivider.length) {
            return;
        }

        epObserveTarget($scope[0], function () {
            bdtUIkit.svg(divider, {
                strokeAnimation: true
            });
        }, {
            root: null, // Use the viewport as the root
            rootMargin: '0px', // No margin around the root
            threshold: 0.8 // 80% visibility (1 - 0.8)
        });

    };


    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-step-flow.default', widgetStepFlow);
    });

}(jQuery, window.elementorFrontend));

/**
 * End step flow widget script
 */


/**
 * Start toggle widget script
 */

(function ($, elementor) {
    'use strict';
    var widgetToggle = function ($scope, $) {
        var $toggleContainer = $scope.find('.bdt-show-hide-container');
        var $toggle          = $toggleContainer.find('.bdt-show-hide');

        if ( !$toggleContainer.length ) {
            return;
        } 
        var $settings            = $toggle.data('settings');
        var toggleId             = $settings.id;
        var animTime             = $settings.scrollspy_time;
        var scrollspy_top_offset = $settings.scrollspy_top_offset;

        var by_widget_selector_status = $settings.by_widget_selector_status;
        var toggle_initially_open     = $settings.toggle_initially_open;
        var source_selector           = $settings.source_selector;
        var widget_visibility         = $settings.widget_visibility;
        var widget_visibility_tablet  = $settings.widget_visibility_tablet;
        var widget_visibility_mobile  = $settings.widget_visibility_mobile;
        var viewport_lg               = $settings.viewport_lg;
        var viewport_md               = $settings.viewport_md;

        var widget_visibility_filtered = widget_visibility;

        if ( $settings.widget_visibility == 'undefined' || $settings.widget_visibility == null ) {
            widget_visibility_filtered = widget_visibility = 0;
        }

        if ( $settings.widget_visibility_tablet == 'undefined' || $settings.widget_visibility_tablet == null ) {
            widget_visibility_tablet = widget_visibility;
        }

        if ( $settings.widget_visibility_mobile == 'undefined' || $settings.widget_visibility_mobile == null ) {
            widget_visibility_mobile = widget_visibility;
        }

        function widgetVsibleFiltered() {
            if ( (window.outerWidth) > (viewport_lg) ) {
                widget_visibility_filtered = widget_visibility;
            } else if ( (window.outerWidth) > (viewport_md) ) {
                widget_visibility_filtered = widget_visibility_tablet;
            } else {
                widget_visibility_filtered = widget_visibility_mobile;
            }
        }

        $(window).resize(function () {
            widgetVsibleFiltered();
        });


        function scrollspyHandler($toggle, toggleId, toggleBtn, animTime, scrollspy_top_offset) {
            if ( $settings.status_scrollspy === 'yes' && by_widget_selector_status !== 'yes' ) {
                if ( $($toggle).find('.bdt-show-hide-item') ) {
                    if ( $settings.hash_location === 'yes' ) {
                        window.location.hash = ($.trim(toggleId));
                    }
                    var scrollspyWrapper = $('#bdt-show-hide-' + toggleId).find('.bdt-show-hide-item');
                    $('html, body').animate({
                        easing   : 'slow',
                        scrollTop: $(scrollspyWrapper).offset().top - scrollspy_top_offset
                    }, animTime, function () {
                        //#code
                    }).promise().then(function () {
                        $(toggleBtn).siblings('.bdt-show-hide-content').slideToggle('slow', function () {
                            $(toggleBtn).parent().toggleClass('bdt-open');
                        });
                    });
                }
            } else {
                if ( by_widget_selector_status === 'yes' ) {
                    $(toggleBtn).parent().toggleClass('bdt-open');
                    $(toggleBtn).siblings('.bdt-show-hide-content').slideToggle('slow', function () {
                    });
                }else{
                    $(toggleBtn).siblings('.bdt-show-hide-content').slideToggle('slow', function () {
                        $(toggleBtn).parent().toggleClass('bdt-open');
                    });
                }
                
            }
        }

        $($toggle).find('.bdt-show-hide-title').off('click').on('click', function (event) {
            var toggleBtn = $(this);
            scrollspyHandler($toggle, toggleId, toggleBtn, animTime, scrollspy_top_offset);
        });

        function hashHandler() {
            toggleId             = window.location.hash.substring(1);
            var toggleBtn        = $('#bdt-show-hide-' + toggleId).find('.bdt-show-hide-title');
            var scrollspyWrapper = $('#bdt-show-hide-' + toggleId).find('.bdt-show-hide-item');
            $('html, body').animate({
                easing   : 'slow',
                scrollTop: $(scrollspyWrapper).offset().top - scrollspy_top_offset
            }, animTime, function () {
                //#code
            }).promise().then(function () {
                $(toggleBtn).siblings('.bdt-show-hide-content').slideToggle('slow', function () {
                    $(toggleBtn).parent().toggleClass('bdt-open');
                });
            });
        }

        $(window).on('load', function () {
            if ( $($toggleContainer).find('#bdt-show-hide-' + window.location.hash.substring(1)).length != 0 ) {
                if ( $settings.hash_location === 'yes' ) {
                    hashHandler();
                }
            }
        });

        /* Function to animate height: auto */
        function autoHeightAnimate(element, time){
    var curHeight = element.height(), // Get Default Height
        autoHeight = element.css('height', 'auto').height(); // Get Auto Height
          element.height(curHeight); // Reset to Default Height
          element.stop().animate({ height: autoHeight }, time); // Animate to Auto Height
      }
      function byWidgetHandler() {
        if ( $settings.status_scrollspy === 'yes' ) {
            $('html, body').animate({
                easing   : 'slow',
                scrollTop: $(source_selector).offset().top - scrollspy_top_offset
            }, animTime, function () {
                    //#code
                }).promise().then(function () {
                    if ( $(source_selector).hasClass('bdt-fold-close') ) {
                        // $(source_selector).css({
                        //     'max-height': '100%'
                        // }).removeClass('bdt-fold-close toggle_initially_open').addClass('bdt-fold-open');
                        $(source_selector).removeClass('bdt-fold-close toggle_initially_open').addClass('bdt-fold-open');
                        autoHeightAnimate($(source_selector), 500);
                    } else {
                        $(source_selector).css({
                            'height': widget_visibility_filtered + 'px'
                        }).addClass('bdt-fold-close').removeClass('bdt-fold-open');
                    }
                });
            } else {
                if ( $(source_selector).hasClass('bdt-fold-close') ) {
                    // $(source_selector).css({
                    //     'max-height': '100%'
                    // }).removeClass('bdt-fold-close toggle_initially_open').addClass('bdt-fold-open');
                    $(source_selector).removeClass('bdt-fold-close toggle_initially_open').addClass('bdt-fold-open');
                    autoHeightAnimate($(source_selector), 500);

                } else {
                    $(source_selector).css({
                        'height': widget_visibility_filtered + 'px',
                        'transition' : 'all 1s ease-in-out 0s'
                    }).addClass('bdt-fold-close').removeClass('bdt-fold-open');    
                } 
            }

        }


        if ( by_widget_selector_status === 'yes' ) {
            $($toggle).find('.bdt-show-hide-title').on('click', function () {
                byWidgetHandler();
            });

            if ( toggle_initially_open === 'yes' ) {
                $(source_selector).addClass('bdt-fold-toggle bdt-fold-open toggle_initially_open');
            } else {
                $(source_selector).addClass('bdt-fold-toggle bdt-fold-close toggle_initially_open');
            }

            $(window).resize(function () {
                visibilityCalled();
            });
            visibilityCalled();
        }

        function visibilityCalled() {
            if ( $(source_selector).hasClass('bdt-fold-close') ) {
                $(source_selector).css({
                    'height': widget_visibility_filtered + 'px'
                });
            } else {
                // $(source_selector).css({
                //     'max-height': '100%'
                // });
                autoHeightAnimate($(source_selector), 500);
            }
        }


    };
    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-toggle.default', widgetToggle);
    });
}(jQuery, window.elementorFrontend));

/**
 * End toggle widget script
 */


/**
 * Start tutor lms grid widget script
 */

(function ($, elementor) {

	'use strict';

	var widgetTutorLMSGrid = function ($scope, $) {

		var $tutorLMS = $scope.find('.bdt-tutor-lms-course-grid'),
			$settings = $tutorLMS.data('settings');

		if (!$tutorLMS.length) {
			return;
		}

		if ($settings.tiltShow == true) {
			var elements = document.querySelectorAll($settings.id + " .bdt-tutor-course-item");
			VanillaTilt.init(elements);
		}

	};

	jQuery(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/bdt-tutor-lms-course-grid.default', widgetTutorLMSGrid);
	});

}(jQuery, window.elementorFrontend));

/**
 * End tutor lms grid widget script
 */

/**
 * Start tutor lms widget script
 */

(function ($, elementor) {

	'use strict';

	var widgetTutorCarousel = function ($scope, $) {

		var $tutorCarousel = $scope.find('.bdt-tutor-lms-course-carousel');

		if (!$tutorCarousel.length) {
			return;
		}

		var $tutorCarouselContainer = $tutorCarousel.find('.swiper-carousel'),
			$settings = $tutorCarousel.data('settings');

		// Access swiper class
		const Swiper = elementorFrontend.utils.swiper;
		initSwiper();

		async function initSwiper() {

			var swiper = await new Swiper($tutorCarouselContainer, $settings);

			if ($settings.pauseOnHover) {
				$($tutorCarouselContainer).hover(function () {
					(this).swiper.autoplay.stop();
				}, function () {
					(this).swiper.autoplay.start();
				});
			}
		};
	};


	jQuery(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/bdt-tutor-lms-course-carousel.default', widgetTutorCarousel);
	});

}(jQuery, window.elementorFrontend));

/**
 * End tutor lms widget script
 */
/**
 * Start user register widget script
 */

(function ($, elementor) {

    'use strict';

    var widgetUserRegistrationForm = {

        registraitonFormSubmit: function (_this, $scope) {

            bdtUIkit.notification({
                message: '<div bdt-spinner></div>' + $(_this).find('.bdt_spinner_message').val(),
                timeout: false
            });
            $(_this).find('button.bdt-button').attr("disabled", true);
            var redirect_url = $(_this).find('.redirect_after_register').val();
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: element_pack_ajax_login_config.ajaxurl,
                data: {
                    'action': 'element_pack_ajax_register', //calls wp_ajax_nopriv_element_pack_ajax_register
                    'first_name': $(_this).find('.first_name').val(),
                    'last_name': $(_this).find('.last_name').val(),
                    'email': $(_this).find('.user_email').val(),
                    'password': $(_this).find('.user_password').val(),
                    'is_password_required': $(_this).find('.is_password_required').val(),
                    'g-recaptcha-response': $(_this).find('#g-recaptcha-response').val(),
                    'widget_id': $scope.data('id'),
                    'page_id': $(_this).find('.page_id').val(),
                    'security': $(_this).find('#bdt-user-register-sc').val(),
                    'lang': element_pack_ajax_login_config.language
                },
                success: function (data) {

                    var recaptcha_field = _this.find('.element-pack-google-recaptcha');
                    if (recaptcha_field.length > 0) {
                        var recaptcha_id = recaptcha_field.attr('data-widgetid');
                        grecaptcha.reset(recaptcha_id);
                        grecaptcha.execute(recaptcha_id);
                    }

                    if (data.registered === true) {
                        bdtUIkit.notification.closeAll();
                        bdtUIkit.notification({
                            message: '<div class="bdt-flex"><span bdt-icon=\'icon: info\'></span><span>' + data.message + '</span></div>',
                            status: 'primary'
                        });
                        if (redirect_url) {
                            document.location.href = redirect_url;
                        }
                    } else {
                        bdtUIkit.notification.closeAll();
                        bdtUIkit.notification({
                            message: '<div class="bdt-flex"><span bdt-icon=\'icon: warning\'></span><span>' + data.message + '</span></div>',
                            status: 'warning'
                        });
                    }
                    $(_this).find('button.bdt-button').attr("disabled", false);

                },
            });
        },
        load_recaptcha: function () {
            var reCaptchaFields = $('.element-pack-google-recaptcha'),
                widgetID;

            if (reCaptchaFields.length > 0) {
                reCaptchaFields.each(function () {
                    var self = $(this),
                        attrWidget = self.attr('data-widgetid');
                    // alert(self.data('sitekey'))
                    // Avoid re-rendering as it's throwing API error
                    if ((typeof attrWidget !== typeof undefined && attrWidget !== false)) {
                        return;
                    } else {
                        widgetID = grecaptcha.render($(this).attr('id'), {
                            sitekey: self.data('sitekey'),
                            callback: function (response) {
                                if (response !== '') {
                                    self.append(jQuery('<input>', {
                                        type: 'hidden',
                                        value: response,
                                        class: 'g-recaptcha-response'
                                    }));
                                }
                            }
                        });
                        self.attr('data-widgetid', widgetID);
                    }
                });
            }
        }

    }


    window.onLoadElementPackRegisterCaptcha = widgetUserRegistrationForm.load_recaptcha;

    var widgetUserRegisterForm = function ($scope, $) {
        var register_form = $scope.find('.bdt-user-register-widget'),
            recaptcha_field = $scope.find('.element-pack-google-recaptcha'),
            $userRegister = $scope.find('.bdt-user-register');

        // Perform AJAX register on form submit
        register_form.on('submit', function (e) {
            e.preventDefault();
            widgetUserRegistrationForm.registraitonFormSubmit(register_form, $scope)
        });

        if (elementorFrontend.isEditMode() && undefined === recaptcha_field.attr('data-widgetid')) {
            onLoadElementPackRegisterCaptcha();
        }

        if (recaptcha_field.length > 0) {
            grecaptcha.ready(function () {
                var recaptcha_id = recaptcha_field.attr('data-widgetid');
                grecaptcha.execute(recaptcha_id);
            });
        }

        var $settings = $userRegister.data('settings');

        if (!$settings || typeof $settings.passStrength === "undefined") {
            return;
        }

        var percentage = 0,
            $selector = $('#' + $settings.id),
            $progressBar = $('#' + $settings.id).find('.bdt-progress-bar');

        var passStrength = {
            progress: function ($value = 0) {
                if ($value <= 100) {
                    $($progressBar).css({
                        'width': $value + '%'
                    });
                }
            },
            formula: function (input, length) {

                if (length < 6) {
                    percentage = 0;
                    $($progressBar).css('background', '#ff4d4d'); //red
                } else if (length < 8) {
                    percentage = 10;
                    $($progressBar).css('background', '#ffff1a'); //yellow
                } else if (input.match(/0|1|2|3|4|5|6|7|8|9/) == null && input.match(/[A-Z]/) == null) {
                    percentage = 40;
                    $($progressBar).css('background', '#ffc14d'); //orange
                }else{
                    if (length < 12){
                        percentage = 50;
                        $($progressBar).css('background', '#1aff1a'); //green
                    }else{
                        percentage = 60;
                        $($progressBar).css('background', '#1aff1a'); //green
                    }
                }


                //Lowercase Words only
                if ((input.match(/[a-z]/) != null)) {
                    percentage += 10;
                }

                //Uppercase Words only
                if ((input.match(/[A-Z]/) != null)) {
                    percentage += 10;
                }

                //Digits only
                if ((input.match(/0|1|2|3|4|5|6|7|8|9/) != null)) {
                    percentage += 10;
                }

                //Special characters
                if ((input.match(/\W/) != null) && (input.match(/\D/) != null)) {
                    percentage += 10;
                }
                return percentage;
            },
            forceStrongPass: function (result) {
                if (result >= 70) {
                    $($selector).find('.elementor-field-type-submit .bdt-button').prop('disabled', false);
                } else {
                    $($selector).find('.elementor-field-type-submit .bdt-button').prop('disabled', true);
                }
            },
            init: function () {
                $scope.find('.user_password').keyup(function () {
                    var input = $(this).val(),
                        length = input.length;
                    let result = passStrength.formula(input, length);
                    passStrength.progress(result);

                    if (typeof $settings.forceStrongPass !== 'undefined') {
                        passStrength.forceStrongPass(result);
                    }
                });
                if (typeof $settings.forceStrongPass !== 'undefined') {
                    $($selector).find('.elementor-field-type-submit .bdt-button').prop('disabled', true);
                }

                $scope.find('.confirm_password').keyup(function () {
                    let input = $(this).val(),
                        length = input.length;
                    let result = passStrength.formula(input, length);
                    passStrength.progress(result);

                    let pass = $scope.find('.user_password').val();
                    
                    if(input !== pass){
                        $scope.find('.bdt-user-register-pass-res').removeClass('bdt-hidden');
                        $($selector).find('.elementor-field-type-submit .bdt-button').prop('disabled', true);
                    }else{
                        $scope.find('.bdt-user-register-pass-res').addClass('bdt-hidden');
                        if (typeof $settings.forceStrongPass !== 'undefined') {
                            passStrength.forceStrongPass(result);
                        }
                    }

                });
            }
        }

        passStrength.init();

    };


    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-user-register.default', widgetUserRegisterForm);
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-user-register.bdt-dropdown', widgetUserRegisterForm);
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-user-register.bdt-modal', widgetUserRegisterForm);
    });

}(jQuery, window.elementorFrontend));

/**
 * End user register widget script
 */
jQuery(document).ready(function () {
    jQuery('body').on('click', '.bdt-element-link', function () {
        var $el      = jQuery(this),
            settings = $el.data('ep-wrapper-link'),
            data     = settings,
            id   = 'bdt-element-link-' + $el.data('id');
            
        if (jQuery('#' + id).length === 0) {
            jQuery('body').append(
                jQuery(document.createElement('a')).prop({
                    target: data.is_external ? '_blank' : '_self',
                    href  : data.url,
                    class : 'bdt-hidden',
                    id    : id,
                    rel   : data.nofollow ? 'nofollow noreferer' : ''
                })
            );
        }

        jQuery('#' + id)[0].click();

    });
});

; (function ($, elementor) {
$(window).on('elementor/frontend/init', function () {
    var ModuleHandler = elementorModules.frontend.handlers.Base,
        ThreedText;

    ThreedText = ModuleHandler.extend({

        bindEvents: function () {
            this.run();
        },

        getDefaultSettings: function () {
            return {
                depth: '30px',
                layers: 8,
            };
        },

        onElementChange: debounce(function (prop) {
            if (prop.indexOf('ep_threed_text_') !== -1) {
                this.run();
            }
        }, 400),

        settings: function (key) {
            return this.getElementSettings('ep_threed_text_' + key);
        },

        run: function () {
            var options = this.getDefaultSettings(),
                $element = this.findElement('.elementor-heading-title, .bdt-main-heading-inner'),
                $widgetId = 'ep-' + this.getID(),
                $widgetIdSelect = '#' + $widgetId;

            jQuery($element).attr('id', $widgetId);

            if (this.settings('depth.size')) {
                options.depth = this.settings('depth.size') + this.settings('depth.unit') || '30px';
            }
            if (this.settings('layers')) {
                options.layers = this.settings('layers') || 8;
            }
            if (this.settings('perspective.size')) {
                options.perspective = this.settings('perspective.size') + 'px' || '500px';
            }
            if (this.settings('fade')) {
                options.fade = !!this.settings('fade');
            }
            // if (this.settings('direction')) {
            //     options.direction = this.settings('direction') || 'forwards';
            // }
            if (this.settings('event')) {
                options.event = this.settings('event') || 'pointer';
            }
            if (this.settings('event_rotation') && this.settings('event') != 'none') {
                options.eventRotation = this.settings('event_rotation.size') + 'deg' || '35deg';
            }
            if (this.settings('event_direction') && this.settings('event') != 'none') {
                options.eventDirection = this.settings('event_direction') || 'default';
            }

            if (this.settings('active') == 'yes') {

                var $text = $($widgetIdSelect).html();
                $($widgetIdSelect).parent().append('<div class="ep-z-text-duplicate" style="display:none;">' + $text + '</div>');

                $text = $($widgetIdSelect).parent().find('.ep-z-text-duplicate:first').html();

                $($widgetIdSelect).find('.z-text').remove();

                var ztxt = new Ztextify($widgetIdSelect, options, $text);
            }

            if (this.settings('depth_color')) {
                var depthColor = this.settings('depth_color') || '#fafafa';
                $($widgetIdSelect).find('.z-layers .z-layer:not(:first-child)').css('color', depthColor);
            }

            // if (this.settings('bg_color')) {
            //     var bgColor = this.settings('bg_color') || 'rgba(96, 125, 139, .5)';
            //     $($widgetIdSelect).find('.z-text > .z-layers').css('background', bgColor);
            // }

        }
    });

    elementorFrontend.hooks.addAction('frontend/element_ready/widget', function ($scope) {
        elementorFrontend.elementsHandler.addHandler(ThreedText, {
            $element: $scope
        });
    });

});
}) (jQuery, window.elementorFrontend);
/**
 * Start twitter carousel widget script
 */

( function( $, elementor ) {

	'use strict';

	var widgetProductCarousel = function( $scope, $ ) {

		var $ProductCarousel = $scope.find( '.bdt-ep-product-carousel' );
				
        if ( ! $ProductCarousel.length ) {
            return;
        }

		var $ProductCarouselContainer = $ProductCarousel.find('.swiper-carousel'),
			$settings 		 = $ProductCarousel.data('settings');

		// Access swiper class
        const Swiper = elementorFrontend.utils.swiper;
        initSwiper();
        
        async function initSwiper() {

			var swiper = await new Swiper($ProductCarouselContainer, $settings);

			if ($settings.pauseOnHover) {
				 $($ProductCarouselContainer).hover(function() {
					(this).swiper.autoplay.stop();
				}, function() {
					(this).swiper.autoplay.start();
				});
			}
		};

	};


	jQuery(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bdt-product-carousel.default', widgetProductCarousel );
	});

}( jQuery, window.elementorFrontend ) );

/**
 * End twitter carousel widget script
 */


/**
 * Start age-gate script
 */

(function ($, elementor) {

    'use strict';

    var widgetAgeGate = function ($scope, $) {

        var $modal = $scope.find('.bdt-age-gate');

        if (!$modal.length) {
            return;
        }

        $.each($modal, function (index, val) {

            var $this = $(this),
                $settings = $this.data('settings'),
                modalID = $settings.id,
                displayTimes = $settings.displayTimes,
                closeBtnDelayShow = $settings.closeBtnDelayShow,
                delayTime = $settings.delayTime,
                widgetId = $settings.widgetId,
                requiredAge = $settings.requiredAge,
                redirect_link = $settings.redirect_link;
            var editMode = Boolean(elementorFrontend.isEditMode());

            if (editMode) {
                redirect_link = false;
            }

            var modal = {
                setLocalize: function () {
                    if (editMode) {
                        this.clearLocalize();
                        return;
                    }
                    this.clearLocalize();
                    var widgetID = widgetId,
                        localVal = 0,
                        // hours = 4;
                        hours = $settings.displayTimesExpire;

                    var expires = (hours * 60 * 60);
                    var now = Date.now();
                    var schedule = now + expires * 1000;

                    if (localStorage.getItem(widgetID) === null) {
                        localStorage.setItem(widgetID, localVal);
                        localStorage.setItem(widgetID + '_expiresIn', schedule);
                    }
                    if (localStorage.getItem(widgetID) !== null) {
                        var count = parseInt(localStorage.getItem(widgetID));
                        count++;
                        localStorage.setItem(widgetID, count);
                        // this.clearLocalize();
                    }
                },
                clearLocalize: function () {
                    var localizeExpiry = parseInt(localStorage.getItem(widgetId + '_expiresIn'));
                    var now = Date.now(); //millisecs since epoch time, lets deal only with integer
                    var schedule = now;
                    if (schedule >= localizeExpiry) {
                        localStorage.removeItem(widgetId + '_expiresIn');
                        localStorage.removeItem(widgetId);
                    }
                },
                modalFire: function () {
                    var displayTimes = 1;
                    var firedNotify = parseInt(localStorage.getItem(widgetId)) || 0;

                    if ((displayTimes !== false) && (firedNotify >= displayTimes)) {
                        return;
                    }
                    bdtUIkit.modal($this, {
                        bgclose: false,
                        keyboard: false
                    }).show();
                },
                ageVerify: function () {
                    var init = this;
                    var firedNotify = parseInt(localStorage.getItem(widgetId)) || 0;
                    $('#' + widgetId).find('.bdt-button').on('click', function () {
                        var input_age = parseInt($('#' + widgetId).find('.bdt-age-input').val());
                        if (input_age >= requiredAge) {
                            init.setLocalize();
                            firedNotify += 1;
                            bdtUIkit.modal($this).hide();
                        } else {
                            if (redirect_link == false) {
                                $('.modal-msg-text').removeClass('bdt-hidden');
                                return;
                            } else {
                                $('.modal-msg-text').removeClass('bdt-hidden');
                            }
                            window.location.replace(redirect_link);
                        }
                    });

                    bdtUIkit.util.on($this, 'hidden', function () {

                        if(editMode){
                            return;
                        }

                        if (redirect_link == false && firedNotify <= 0) {

                            setTimeout( function(){
                                init.modalFire();
                            }, 1500);

                            return;
                        }

                        if (redirect_link !== false && firedNotify <= 0) {
                            window.location.replace(redirect_link);
                        }
                    });
                },
                closeBtnDelayShow: function () {
                    var $modal = $('#' + modalID);
                    $modal.find('#bdt-modal-close-button').hide(0);
                    $modal.on("shown", function () {
                            $('#bdt-modal-close-button').hide(0).fadeIn(delayTime);
                        })
                        .on("hide", function () {
                            $modal.find('#bdt-modal-close-button').hide(0);
                        });
                },

                default: function () {
                    this.modalFire();
                },
                init: function () {
                    var init = this;
                    init.default();
                    init.ageVerify();

                    if (closeBtnDelayShow) {
                        init.closeBtnDelayShow();
                    }
                }
            };

            // kick the modal
            modal.init();

        });
    };

    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-age-gate.default', widgetAgeGate);
    });

}(jQuery, window.elementorFrontend));

/**
 * End age-gate script
 */
(function ($, elementor) {

    'use strict';

    $(window).on('elementor/frontend/init', function () {
        var ModuleHandler = elementorModules.frontend.handlers.Base,
            widgetDarkMode;

        widgetDarkMode = ModuleHandler.extend({

            bindEvents: function () {
                this.run();
            },

            getDefaultSettings: function () {
                return {
                    left: 'unset',
                    time: '.5s',
                    mixColor: '#fff',
                    backgroundColor: '#fff',
                    saveInCookies: false,
                    label: '🌓',
                    autoMatchOsTheme: false
                };
            },


            onElementChange: debounce(function (prop) {
                // if (prop.indexOf('time.size') !== -1) {
                this.run();
                // }
            }, 400),

            settings: function (key) {
                return this.getElementSettings(key);
            },

            setCookie: function (name, value, days) {
                var expires = "";
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + (value || "") + expires + "; path=/";
            },
            getCookie: function (name) {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                }
                return null;
            },

            eraseCookie: function (name) {
                document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            },


            run: function () {
                var options = this.getDefaultSettings();

                var autoMatchOsTheme = (this.settings('autoMatchOsTheme') === 'yes'
                    && this.settings('autoMatchOsTheme') !== 'undefined');

                var saveInCookies = (this.settings('saveInCookies') === 'yes'
                    && this.settings('saveInCookies') !== 'undefined');

                options.left = 'unset';
                options.time = this.settings('time.size') / 1000 + 's';
                options.mixColor = this.settings('mix_color');
                options.backgroundColor = this.settings('default_background');
                options.saveInCookies = saveInCookies;
                options.label = '🌓';
                options.autoMatchOsTheme = autoMatchOsTheme;

                $('body').removeClass(function (index, css) {
                    return (css.match(/\bbdt-dark-mode-\S+/g) || []).join(' '); // removes anything that starts with "page-"
                });
                $('body').addClass('bdt-dark-mode-position-' + this.settings('toggle_position'));

                $(this.settings('ignore_element')).addClass('darkmode-ignore');

                if (options.mixColor) {

                    $('.darkmode-toggle, .darkmode-layer, .darkmode-background').remove();

                    var darkmode = new Darkmode(options);
                    darkmode.showWidget();

                    if (this.settings('default_mode') === 'dark') {
                        darkmode.toggle();
                        $('body').addClass('darkmode--activated');
                        $('.darkmode-layer').addClass('darkmode-layer--simple darkmode-layer--expanded');
                    } else {
                        $('body').removeClass('darkmode--activated');
                        $('.darkmode-layer').removeClass('darkmode-layer--simple darkmode-layer--expanded');
                    }

                    var global_this = this,
                        editMode = $('body').hasClass('elementor-editor-active');

                    if (editMode === false && saveInCookies === true) {
                        $('.darkmode-toggle').on('click', function () {
                            if (darkmode.isActivated() === true) {
                                global_this.eraseCookie('bdtDarkModeUserAction');
                                global_this.setCookie('bdtDarkModeUserAction', 'dark', 10);
                            } else if (darkmode.isActivated() === false) {
                                global_this.eraseCookie('bdtDarkModeUserAction');
                                global_this.setCookie('bdtDarkModeUserAction', 'light', 10);
                            } else {

                            }
                        });

                        var userCookie = this.getCookie('bdtDarkModeUserAction')

                        if (userCookie !== null && userCookie !== 'undefined') {
                            if (userCookie === 'dark') {
                                darkmode.toggle();
                                $('body').addClass('darkmode--activated');
                                $('.darkmode-layer').addClass('darkmode-layer--simple darkmode-layer--expanded');
                            } else {
                                $('body').removeClass('darkmode--activated');
                                $('.darkmode-layer').removeClass('darkmode-layer--simple darkmode-layer--expanded');
                            }

                        }
                    }

                }


            }
        });

        elementorFrontend.hooks.addAction('frontend/element_ready/bdt-dark-mode.default', function ($scope) {
            elementorFrontend.elementsHandler.addHandler(widgetDarkMode, { $element: $scope });

        });
    });


}(jQuery, window.elementorFrontend));

/**
 * End Dark Mode widget script
 */

(function ($, elementor) {
  $(window).on("elementor/frontend/init", function () {
    var ModuleHandler = elementorModules.frontend.handlers.Base,
      AnimatedGradientBackground;

    AnimatedGradientBackground = ModuleHandler.extend({
      bindEvents: function () {
        this.run();
      },

      getDefaultSettings: function () {
        return {
          allowHTML: true,
        };
      },

      onElementChange: debounce(function (prop) {
        if (prop.indexOf('element_pack_agbg_') !== -1) {
          this.run();
        }
      }, 400),

      settings: function (key) {
        return this.getElementSettings("element_pack_agbg_" + key);
      },

      // Helper function to parse and standardize colors to desired formats
      parseColor: function (color) {
        // Convert RGBA to 6-digit HEX if alpha is 1
        if (/^rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d.]*)\)$/.test(color)) {
          const [_, r, g, b, a = 1] = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d.]*)\)$/);
          const alpha = parseFloat(a);
          if (alpha === 1) {
            // If alpha is 1, convert to 6-digit HEX format
            return `#${((1 << 24) + (parseInt(r) << 16) + (parseInt(g) << 8) + parseInt(b)).toString(16).slice(1)}`;
          }
          return `rgba(${r}, ${g}, ${b}, .${a.toString().split('.')[1] || 0})`; // Format as .decimal if alpha < 1
        }

        // Convert 8-digit HEXA (#RRGGBBAA) to 6-digit HEX if alpha is 1
        if (/^#([A-Fa-f0-9]{8})$/.test(color)) {
          const rgba = color.match(/[A-Fa-f0-9]{2}/g).map((hex) => parseInt(hex, 16));
          const alpha = parseFloat((rgba[3] / 255).toFixed(2));
          if (alpha === 1) {
            return `#${color.slice(1, 7)}`; // Remove alpha part if 100% opaque
          }
          return `rgba(${rgba[0]}, ${rgba[1]}, ${rgba[2]}, .${alpha.toString().split('.')[1] || 0})`;
        }

        // Convert 6-digit HEX to standard 6-digit HEX (no changes needed)
        if (/^#([A-Fa-f0-9]{6})$/.test(color)) {
          return color.toLowerCase();
        }

        // Handle HSLA, standardizing alpha to .decimal format
        if (/^hsla?\((\d+),\s*([\d.]+)%,\s*([\d.]+)%,?\s*([\d.]*)\)$/.test(color)) {
          const [_, h, s, l, a = 1] = color.match(/^hsla?\((\d+),\s*([\d.]+)%,\s*([\d.]+)%,?\s*([\d.]*)\)$/);
          const alpha = parseFloat(a);
          if (alpha === 1) {
            return `hsl(${h}, ${s}%, ${l}%)`; // No alpha if fully opaque
          }
          return `hsla(${h}, ${s}%, ${l}%, .${a.toString().split('.')[1] || 0})`; // .decimal format for alpha < 1
        }

        return color; // Return color as-is for named colors or other formats
      },

      run: function () {
        if (this.settings('show') !== 'yes') {
          return;
        }
        const sectionID = this.$element.data("id");
        const widgetContainer = document.querySelector(".elementor-element-" + sectionID);
        const checkClass = $(widgetContainer).find(".bdt-animated-gradient-background");

        if ($(checkClass).length < 1) {
          $(widgetContainer).prepend('<canvas id="canvas-basic-' + sectionID + '" class="bdt-animated-gradient-background"></canvas>');
        }

        const gradientID = $(widgetContainer).find(".bdt-animated-gradient-background").attr("id");

        let color_list = this.settings("color_list");
        let colors = color_list.map((color) => [
          this.parseColor(color.start_color),
          this.parseColor(color.end_color)
        ]);

        var direction = (this.settings("direction") !== undefined) ? this.settings('direction') : 'diagonal';
        var transitionSpeed = (this.settings("transitionSpeed") !== undefined) ? this.settings('transitionSpeed.size') : '5500';
        
        var granimInstance = new Granim({
          element: "#" + gradientID,
          direction: direction,
          isPausedWhenNotInView: true,
          states: {
            "default-state": {
              gradients: colors,
              transitionSpeed: transitionSpeed,
            },
          },
        });
      },
    });

    elementorFrontend.hooks.addAction(
      "frontend/element_ready/section",
      function ($scope) {
        elementorFrontend.elementsHandler.addHandler(AnimatedGradientBackground, {
          $element: $scope,
        });
      }
    );

    elementorFrontend.hooks.addAction(
      "frontend/element_ready/container",
      function ($scope) {
        elementorFrontend.elementsHandler.addHandler(AnimatedGradientBackground, {
          $element: $scope,
        });
      }
    );

  });
})(jQuery, window.elementorFrontend);

; (function ($, elementor) {
    $(window).on('elementor/frontend/init', function () {
        var ModuleHandler = elementorModules.frontend.handlers.Base,
            Tooltip;

        Tooltip = ModuleHandler.extend({

            bindEvents: function () {
                this.run();
            },

            getDefaultSettings: function () {
                return {
                    allowHTML: true,
                };
            },

            onElementChange: debounce(function (prop) {
                if (prop.indexOf('element_pack_widget_') !== -1) {
                    this.instance.destroy();
                    this.run();
                }
            }, 400),

            settings: function (key) {
                return this.getElementSettings('element_pack_widget_' + key);
            },

            run: function () {
                var options = this.getDefaultSettings();
                var widgetID = this.$element.data('id');
                var widgetContainer = document.querySelector('.elementor-element-' + widgetID + ' .elementor-widget-container');

                if (this.settings('tooltip_text')) {
                    options.content = EP_SAFE_HTML(this.settings('tooltip_text'));
                }

                options.arrow = !!this.settings('tooltip_arrow');
                options.followCursor = !!this.settings('tooltip_follow_cursor');

                if (this.settings('tooltip_placement')) {
                    options.placement = this.settings('tooltip_placement');
                }

                if (this.settings('tooltip_trigger')) {
                    if (this.settings('tooltip_custom_trigger')) {
                        options.triggerTarget = document.querySelector(this.settings('tooltip_custom_trigger'));
                    } else {
                        options.trigger = this.settings('tooltip_trigger');
                    }
                }
                // if (this.settings('tooltip_animation_duration')) {
                //     options.duration = this.settings('tooltip_animation_duration.sizes.from');
                // }
                if (this.settings('tooltip_animation')) {
                    if (this.settings('tooltip_animation') === 'fill') {
                        options.animateFill = true;
                    } else {
                        options.animation = this.settings('tooltip_animation');
                    }
                }
                if (this.settings('tooltip_x_offset.size') || this.settings('tooltip_y_offset.size')) {
                    options.offset = [this.settings('tooltip_x_offset.size') || 0, this.settings('tooltip_y_offset.size') || 0];
                }
                if (this.settings('tooltip')) {
                    options.theme = 'bdt-tippy-' + widgetID;
                    this.instance = tippy(widgetContainer, options);
                }
            }
        });

        elementorFrontend.hooks.addAction('frontend/element_ready/widget', function ($scope) {
            elementorFrontend.elementsHandler.addHandler(Tooltip, {
                $element: $scope
            });
        });
    });
})(jQuery, window.elementorFrontend);

// Common js for review card, review card carousel, review card grid, testimonial carousel, testimonial grid
(function ($, elementor) {
    "use strict";
    $(window).on("elementor/frontend/init", function () {
        /** Read more */
        const readMoreWidgetHandler = function readMoreWidgetHandler($scope) {
            if (jQuery($scope).find(".bdt-ep-read-more-text").length) {
                jQuery($scope)
                    .find(".bdt-ep-read-more-text")
                    .each(function () {
                        var words_limit_settings = $(this).data("read-more");
                        var max_words = words_limit_settings.words_length || 20; // Set the maximum number of words to show
                        var content = $(this).html(); // Get the full content
                        var cleanContent = content.replace(/<\/?[^>]+(>|$)/g, ""); // Removes all HTML tags
                        var words = cleanContent.split(/\s+/);

                        if (words.length > max_words) {
                            var short_content = words.slice(0, max_words).join(" "); // Get the first part of the content
                            var long_content = words.slice(max_words).join(" "); // Get the remaining part of the content

                            $(this).html(`
                          ${short_content}
                          <a href="#" class="bdt_read_more">...${ElementPackConfig.words_limit.read_more}</a>
                          <span class="bdt_more_text" style="display:none;">${long_content}</span>
                          <a href="#" class="bdt_read_less" style="display:none;">${ElementPackConfig.words_limit.read_less}</a>
                      `);

                            $(this)
                                .find("a.bdt_read_more")
                                .click(function (event) {
                                    event.preventDefault();
                                    $(this).hide(); // Hide the read more link
                                    $(this).siblings(".bdt_more_text").show(); // Show the more text
                                    $(this).siblings("a.bdt_read_less").show(); // Show the read less link
                                });

                            $(this)
                                .find("a.bdt_read_less")
                                .click(function (event) {
                                    event.preventDefault();
                                    $(this).hide(); // Hide the read less link
                                    $(this).siblings(".bdt_more_text").hide(); // Hide the more text
                                    $(this).siblings("a.bdt_read_more").show(); // Show the read more link
                                });
                        }
                    });
            }
        };

        const readMoreWidgetsHanlders = {
            "bdt-review-card.default": readMoreWidgetHandler,
            "bdt-review-card-carousel.default": readMoreWidgetHandler,
            "bdt-review-card-grid.default": readMoreWidgetHandler,
            "bdt-testimonial-carousel.default": readMoreWidgetHandler,
            "bdt-testimonial-carousel.bdt-twyla": readMoreWidgetHandler,
            "bdt-testimonial-carousel.bdt-vyxo": readMoreWidgetHandler,
            "bdt-testimonial-grid.default": readMoreWidgetHandler,
            "bdt-testimonial-slider.default": readMoreWidgetHandler,
            "bdt-testimonial-slider.bdt-single": readMoreWidgetHandler,
            "bdt-testimonial-slider.bdt-thumb": readMoreWidgetHandler,
        };

        $.each(readMoreWidgetsHanlders, function (widgetName, handlerFn) {
            elementorFrontend.hooks.addAction(
                "frontend/element_ready/" + widgetName,
                handlerFn
            );
        });
        /** /Read more */
    });
})(jQuery, window.elementorFrontend);

// end
