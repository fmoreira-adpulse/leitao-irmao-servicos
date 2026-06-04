; (function ($) {
	'use strict';
	jQuery(function () {

		// Fn to allow an event to fire after all images are loaded
		$.fn.wpgspimagesLoaded = function () {

			// get all the images (excluding those with no src attribute)
			var $imgs = this.find('img[src!=""]');
			// if there's no images, just return an already resolved promise
			if (!$imgs.length) { return $.Deferred().resolve().promise(); }

			// for each image, add a deferred object to the array which resolves when the image is loaded (or if loading fails)
			var dfds = [];
			$imgs.each(function () {

				var dfd = $.Deferred();
				dfds.push(dfd);
				var img = new Image();
				img.onload = function () { dfd.resolve(); }
				img.onerror = function () { dfd.resolve(); }
				img.src = this.src;

			});

			// return a master promise object which will resolve when all the deferred objects have resolved
			// IE - when all the images are loaded
			return $.when.apply($, dfds);

		}
		// set all settings
		var settings = wcgs_object.wcgs_settings,
		wcgs_product_wrapper = wcgs_object.wcgs_product_wrapper,
		wcgs_body_font_size = parseInt( wcgs_object.wcgs_body_font_size ),
		gallery_w = 0,
		summary_w = 0;

		function wcgs_slider_func(width) {
			var width_unit = width > 100 ? 'px': '%';
			if ($(window).width() < 992) {
				if (settings.gallery_responsive_width.width > 0) {
					width_unit = settings.gallery_responsive_width.unit;
				}
			}
			if ($(window).width() < 768) {
				width_unit = settings.gallery_responsive_width.unit;
			}

			setTimeout(function(){
				// For % unit
				if('%' === width_unit){
					summary_w = ( 100 - width );
					summary_w = summary_w > 20 ?  'calc( ' + summary_w + '% - 30px )' : '';
				} else { 
					// For px or em unit
					var parent_wrapper = $('#wpgs-gallery').parent('*');
					var parent_wrapper_width = parent_wrapper.width() > ( $('#wpgs-gallery').width() + 100 ) ? parent_wrapper.width() : 0;
					summary_w = parent_wrapper_width > 200 ? ( parent_wrapper_width - width ) : 0;
					summary_w = summary_w > 150 ?  ( summary_w - 35 )+width_unit : '';
					// For em unit
					if('em' === width_unit){
						parent_wrapper_width = parent_wrapper_width / wcgs_body_font_size;
						summary_w = parent_wrapper_width > width ? ( parent_wrapper_width - width ) : 0;
						summary_w = summary_w > 12 ? ( summary_w - 3 ) + width_unit : '';
					}
				}
				$('#wpgs-gallery ~ .summary').css('maxWidth', summary_w );
			}, 100);

			$("#wpgs-gallery").css('minWidth', 'auto').css('maxWidth', width + width_unit);
			var wcgs_img_count = $("#wpgs-gallery").find('.gallery-navigation-carousel .wcgs-thumb').length;
			var thumbnails_item_to_show = parseInt(settings.thumbnails_item_to_show);
			var adaptive_height = (settings.adaptive_height == '1') ? true : false;
			var accessibility = (settings.accessibility == '1') ? true : false;
			var pagination = (settings.pagination == '1') ? true : false;
			var navigation = (settings.navigation == '1') ? true : false;
			var slider_dir = (settings.slider_dir == '1' || $('body').hasClass('rtl')) ? true : false;
			var infinite = (settings.infinite_loop == '1') ? true : false;
			var thumbnail_nav = (settings.thumbnailnavigation == 1) ? true : false;
			var slide_orientation = (settings.slide_orientation == 'vertical') ? true : false;
			// hide nav carousel if item is one!
			if (wcgs_img_count == 1) {
				$("#wpgs-gallery").find('.gallery-navigation-carousel').hide();
			} else {
				$("#wpgs-gallery").find('.gallery-navigation-carousel').show();
			}
			// $('.wcgs-carousel').show();
			var carousel_items = $('.wcgs-carousel:not(.slick-initialized) .wcgs-slider-image').length;
			if (carousel_items > 0) {
				if (typeof $.fn.slick == 'function') {
					$('.wcgs-carousel:not(.slick-initialized)').slick({
						slidesToShow: 1,
						infinite: infinite,
						accessibility: accessibility,
						autoplay: false,
						slidesToScroll: 1,
						speed: 300,
						rtl: (settings.slide_orientation != 'vertical') ? slider_dir : false,
						rows: 0,
						vertical: slide_orientation,
						adaptiveHeight: adaptive_height,
						arrows: navigation,
						fade: false,
						dots: pagination,
						focusOnSelect: true,
						asNavFor: '.gallery-navigation-carousel',
						prevArrow: '<a class="sp_wgs-icon-left-open" data-role="none" aria-label="Prev slide"></a>',
						nextArrow: '<a class="sp_wgs-icon-right-open" data-role="none" aria-label="Next slide"></a>'
					});
				} else {
					console.log('slick is not loaded');
				}
			}
			if (typeof $.fn.slick == 'function') {
				$('.gallery-navigation-carousel:not(.slick-initialized)').slick({
					infinite: infinite,
					slidesToShow: thumbnails_item_to_show,
					slidesToScroll: 1,
					asNavFor: '.wcgs-carousel',
					vertical: false,
					verticalSwiping: false,
					centerMode: false,
					centerPadding: '0',
					rtl: slider_dir,
					rows: 0,
					arrows: thumbnail_nav,
					focusOnSelect: true,
					prevArrow: '<a class="sp_wgs_thumb_nav sp_wgs-icon-left-open" data-role="none" aria-label="Prev slide"></a>',
					nextArrow: '<a class="sp_wgs_thumb_nav sp_wgs-icon-right-open" data-role="none" aria-label="Next slide"></a>'
				});
			} else {
				console.log('slick is not loaded');
			}

			// trigger current item after clicking on lightbox icon
			$(document).on('click', '.wcgs-carousel .wcgs-lightbox', function (e) {
				$(document).find('.wcgs-carousel .slick-current.slick-active').trigger('click');
			});
			// Take the same height for each image wrapper(vertical orientation).
			if (slide_orientation) {
				var maxHeight = 0;
				$('#wpgs-gallery .wcgs-carousel .wcgs-slider-image').each(function () {
					if ($(this).height() > maxHeight) {
						maxHeight = $(this).innerHeight();
					}
				});
				$('#wpgs-gallery .wcgs-carousel .wcgs-slider-image').height(maxHeight);
				$('#wpgs-gallery .wcgs-carousel .wcgs-slider-image').css({
					"display": "flex",
					"justify-content": "center",
					"align-items": "center",
				});
			}

			// Theme savoy.
			if ($('body').hasClass('theme-savoy')) {
				var slickArrow = ['.sp_wgs-icon-left-open', '.sp_wgs-icon-right-open', '.sp_wgs-icon-left-open', '.sp_wgs-icon-right-open'];
				$.each(slickArrow, function (i, item) {
					$('#wpgs-gallery ' + item).addClass('slick-arrow');
				})
			}

			if (settings.lightbox == '1') {
				if (!$('#wpgs-gallery .wcgs-carousel .wcgs-lightbox').length) {
					$('#wpgs-gallery .wcgs-carousel').append('<div class="wcgs-lightbox top_right"><a href="javascript:;"><span class="sp_wgs-icon-search"></span></a></div>');
				}
			}

			var pagination_visibility = (settings.pagination_visibility == 'hover') ? true : false;
			if (pagination_visibility) {
				$("#wpgs-gallery .slick-dots").hide()
				$("#wpgs-gallery .wcgs-carousel").on({
					mouseenter: function () {
						$("#wpgs-gallery .slick-dots").show();
					},
					mouseleave: function () {
						$("#wpgs-gallery .slick-dots").hide();
					}
				});
			}
			// Carousel navigation visibility.
			var navigation_visibility = (settings.navigation_visibility == 'hover') ? true : false;
			if (navigation_visibility) {
				$("#wpgs-gallery .slick-arrow:not(.sp_wgs_thumb_nav)").hide()
				$("#wpgs-gallery .wcgs-carousel").on({
					mouseenter: function () {
						$("#wpgs-gallery .slick-arrow:not(.sp_wgs_thumb_nav)").show();
					},
					mouseleave: function () {
						$("#wpgs-gallery .slick-arrow:not(.sp_wgs_thumb_nav)").hide();
					}
				});
			}
			// Thumb navigation visibility.
			var thumb_navigation_visibility = (settings.thumb_nav_visibility == 'hover') ? true : false;
			if (thumb_navigation_visibility) {
				$("#wpgs-gallery .sp_wgs_thumb_nav").hide()
				$("#wpgs-gallery .gallery-navigation-carousel").on({
					mouseenter: function () {
						$("#wpgs-gallery .sp_wgs_thumb_nav").show();
					},
					mouseleave: function () {
						$("#wpgs-gallery .sp_wgs_thumb_nav").hide();
					}
				});
			}

			var isPreloader = (settings.preloader == 1) ? true : false;
			if (isPreloader) {
				if (!$('.wcgs-gallery-preloader').length) {
					$('#wpgs-gallery').append('<div class="wcgs-gallery-preloader"></div>');
				}
			}

		}
		$('#wpgs-gallery').wpgspimagesLoaded().then(function () {
			$(".wcgs-gallery-preloader").css("opacity", 0);
			$(".wcgs-gallery-preloader").css("z-index", -99);
		});
		// Add video icon on thumbnail.
		function videoIcon() {
			$('.wcgs-slider-image, .wcgs-thumb').each(function () {
				var icon = $(this).find('img').data('type');
				if (icon) {
					$(this).append('<div class="video-icon"></div>');
				}
			})
		}

		// Add data-scale and data-image attributes when hover on wrapper.
		function dataZoom() {
			$('.wcgs-slider-image').on('mouseenter mouseleave', function () {
				$(this).attr('data-scale', '1.5');
				var img = $(this).find('img').attr('src');
				$(this).attr('data-image', img);
			});
		}

		// Zoom function defines.
		function zoomFunction() {
			$('.wcgs-slider-image')
				.on('mouseover', function () {
					$(this).children('.photo').css({
						'transform': 'scale(' + $(this).attr('data-scale') + ')',
						'transition': 'all .5s'
					});
				})
				.on('mouseout', function () {
					$(this).children('.photo').css({ 'transform': 'scale(1)', 'transition': 'all .5s' });
				})
				.on('mousemove', function (e) {
					$(this).children('.photo').css({
						'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 + '%', 'transition': 'transform 1s ease-in'
					});
				})
				.each(function () {
					var photoLength = $(this).find('.photo').length;
					if (photoLength === 0) {
						$(this).append('<div class="photo"></div>');
					}
					$(this).children('.photo').css({ 'background-image': 'url(' + $(this).find('img').attr('src') + ')' });
				});
		}

		// Determine when zoomFunction apply.
		function zoomEffect() {
			if ($(window).width() < 480 && settings.mobile_zoom == 1) {
				return '';
			}
			$(document).on('click', '.wcgs-slider-image', function () {
				zoomFunction();
			});
			$(".wcgs-slider-image").on({
				mouseenter: function () {
					zoomFunction();
				},
				mouseleave: function () {
					zoomFunction();
				}
			});
		}

		// Add lightbox with gallery.
		function wcgsLightbox() {
			var lightbox = (settings.lightbox == 1) ? true : false;
			if (lightbox) {
				var gl_btns = [
					"zoom"
				];
				if (settings.gallery_fs_btn == 1) {
					gl_btns.push("fullScreen");
				}
				if (settings.gallery_share == 1) {
					gl_btns.push("share");
				}
				gl_btns.push("close");
				$.fancybox.defaults.buttons = gl_btns;
				var counter = (settings.l_img_counter == 1) ? true : false;
				$('.wcgs-carousel').fancybox({
					selector: '.wcgs-carousel .wcgs-slider-image:not(.slick-cloned)',
					backFocus: false,
					baseClass: "wcgs-fancybox-custom-wrapper",
					caption: function () {
						var caption = '';
						if (settings.lightbox_caption == 1) {
							caption = $(this).find('img').data('cap') || '';
						}
						return caption;
					},
					afterShow: function (instance, current) {
						$(".wcgs-fancybox-custom-wrapper~.elementor-lightbox").remove();
					},
					infobar: counter,
					buttons: gl_btns,
					loop: true
				});
			} else {
				$('.wcgs-carousel .wcgs-slider-image').removeAttr("data-fancybox href");
			}
		}
		function wcgs_initialize() {
			var gallery_width = settings.gallery_width;

			gallery_w = gallery_width;
			summary_w = (100 - gallery_width);

			if ($(window).width() >= 992) {
				summary_w = summary_w > 20 ? summary_w : '100%';
				$('#wpgs-gallery ~ .summary').css('maxWidth', 'calc(' + summary_w + '% - 30px)');
			}

			if ($('body').hasClass('et_divi_builder') || $('body').hasClass('theme-Divi')) {
				var gallery_divi_width = $('.wcgs-gallery-slider.et-db #et-boc .et-l .et_pb_gutters3 .et_pb_column_1_2').width();
				if (typeof gallery_divi_width === "number") {
					gallery_w = gallery_divi_width;
				}
			}

			if ($('body').hasClass('theme-flatsome')) {
				var gallery_flatsome_width = $('.single-product .product .row.content-row .product-gallery').width();
				if (typeof gallery_flatsome_width === "number") {
					gallery_w = gallery_flatsome_width;
				}
			}

			if ($('.woocommerce-product-gallery').parents('.hestia-product-image-wrap').length) {
				var gallery_hestia_width = $('.woocommerce-product-gallery').parents('.hestia-product-image-wrap').width();
				if (typeof gallery_hestia_width === "number") {
					gallery_w = gallery_hestia_width;
				}
			}

			if ($(window).width() < 992) {
				if (settings.gallery_responsive_width.width > 0) {
					gallery_w = settings.gallery_responsive_width.width;
				}
			}
			if ($(window).width() < 768) {
				gallery_w = settings.gallery_responsive_width.height;
			}
			if ($(window).width() < 480) {
				gallery_w = settings.gallery_responsive_width.height2;
			}

			wcgs_slider_func(gallery_w);
		}

		wcgs_initialize();

		$(window).on( "resize", function(){
			wcgs_initialize();
		});
		
		$(window).on("load", function () {
			$(".wcgs-gallery-preloader").css("opacity", 0);
			$(".wcgs-gallery-preloader").css("z-index", -99);
		});
		videoIcon();
		if (wcgs_object.wcgs_settings.zoom == "1") {
			dataZoom();
			zoomEffect();
		}
		wcgsLightbox();

		$(document).on('change', '.variations select', function () {
			var variationsArray = {};
			$('.variations tr').each(function (i) {
				var attributeName = $(this).find('select').data('attribute_name');
				var attributeValue = $(this).find('select').val();
				if (attributeValue) {
					variationsArray[attributeName] = attributeValue;
				}
			});
			if (wcgs_object.wcgs_data.length > 0) {
				var data = wcgs_object.wcgs_data;
				$.each(data, function (i, v) {
					var v0 = JSON.stringify(v[0]) == '[]' ? '{}' : JSON.stringify(v[0]);
					if (v0 === JSON.stringify(variationsArray)) {
						var response = v[1];
						if (response.length > 0) {
							$('.wcgs-gallery-preloader').css('z-index', 99);
							$('.wcgs-gallery-preloader').css('opacity', 0.5);
							if (typeof $.fn.slick == 'function') {
								$('.wcgs-carousel').slick('unslick');
								$('.gallery-navigation-carousel').slick('unslick');
							} else {
								console.log('slick is not loaded');
							}
							$('#wpgs-gallery .wcgs-carousel > *, #wpgs-gallery .gallery-navigation-carousel > *').remove();
							var gallery = response;
							gallery.forEach(function (item, index) {
								if (item != null) {
									var caption = (item.cap.length > 0) ? item.cap : '';
									var checkVideo = item.hasOwnProperty('video') ? true : false;
									if (checkVideo) {
										var regex = /(http|https):\/\/(\w+:{0,1}\w*)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%!\-\/]))?/;
										var video = item.video;
										if (regex.test(video)) {
											var youtubeCheck = (video.indexOf('youtub') > -1) ? true : false;
											var vimeoCheck = (video.indexOf('vimeo') > -1) ? true : false;
											if (youtubeCheck) {
												var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*/;
												var match = video.match(regExp);
												var id = (match && match[7].length == 11) ? match[7] : false;

												$('#wpgs-gallery .wcgs-carousel').append('<a class="wcgs-slider-image" href="' + video + '" data-fancybox="view"><div class="wcgs-iframe-wraper"><img style="visibility: hidden;" alt="' + caption + '" data-videosrc="' + video + '" src="' + item.url + '" data-type="youtube" data-videoid="' + id + '" /><iframe frameborder="0"  src="https://www.youtube.com/embed/' + id + '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="true"></iframe></div></a>');
												$('#wpgs-gallery .gallery-navigation-carousel').append('<div class="wcgs-thumb"><img alt="' + caption + '" data-videosrc="' + video + '" src="' + item.thumb_url + '" data-type="youtube" data-videoid="' + id + '" /></div>');
											}
											if (vimeoCheck) {
												var regExp = /^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/;
												var match = video.match(regExp);
												var id = match[5];
												$('#wpgs-gallery .wcgs-carousel').append('<a class="wcgs-slider-image" href="' + video + '" data-fancybox="view"><div class="wcgs-iframe-wraper"><img style="visibility: hidden;" alt="' + caption + '" data-videosrc="' + video + '" src="' + item.url + '" data-type="vimeo" data-videoid="' + id + '" /><iframe src="https://player.vimeo.com/video/' + id + '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe></div></a>');
												$('#wpgs-gallery .gallery-navigation-carousel').append('<div class="wcgs-thumb"><img alt="' + caption + '" data-videosrc="' + video + '" src="' + item.thumb_url + '" data-type="vimeo" data-videoid="' + id + '"   /></div>');
											}
											if (!youtubeCheck && !vimeoCheck) {
												$('#wpgs-gallery .wcgs-carousel').append('<a class="wcgs-slider-image" href="' + video + '" data-fancybox="view"><div class="wcgs-iframe-wraper"><video controls><source src="' + video + '" type="video/mp4"></video><img  style="visibility: hidden;" alt="' + caption + '" data-videosrc="' + video + '" src="' + item.url + '" data-type="html5video" data-videomp4="' + video + '" /></div></a>');
												$('#wpgs-gallery .gallery-navigation-carousel').append('<div class="wcgs-thumb"><img alt="' + caption + '" data-videosrc="' + video + '" src="' + item.thumb_url + '" data-type="html5video" data-videomp4="' + video + '" /></div>');
											}
										} else {
											$('#wpgs-gallery .wcgs-carousel').append('<a class="wcgs-slider-image" href="' + item.url + '" data-fancybox="view"><img alt="' + caption + '" src="' + item.url + '" data-image="' + item.url + '" /></a>');
											$('#wpgs-gallery .gallery-navigation-carousel').append('<div class="wcgs-thumb"><img alt="' + caption + '" src="' + item.thumb_url + '" data-image="' + item.url + '" /></div>');
										}
									} else {
										$('#wpgs-gallery .wcgs-carousel').append('<a class="wcgs-slider-image" href="' + item.full_url + '" data-fancybox="view"><img alt="' + caption + '" src="' + item.url + '" data-image="' + item.full_url + '" /></a>');
										$('#wpgs-gallery .gallery-navigation-carousel').append('<div class="wcgs-thumb"><img alt="' + caption + '" src="' + item.thumb_url + '" data-image="' + item.url + '" /></div>');
									}
								}
							});

							$('#wpgs-gallery').wpgspimagesLoaded().then(function () {
								// do stuff after images are loaded here
								wcgs_slider_func(gallery_w);
								var slickHeight = $('.wcgs-carousel .slick-list').height();
								if( slickHeight < 50 ){
									slickHeight = $('.wcgs-carousel .slick-current').height();
									$('.wcgs-carousel .slick-list').css('height', slickHeight );
								}
								setTimeout(function () {
									wcgsGalleryClickEvents();
									if (wcgs_object.wcgs_settings.zoom == "1") {
										dataZoom();
										zoomEffect();
									}
									videoIcon();
									wcgsLightbox();
									$('.wcgs-gallery-preloader').css('z-index', -99);
									$('.wcgs-gallery-preloader').css('opacity', 0);
								}, 200);
							});
						}
					}
				})
			} else {
				console.log('gallery-attr length:' + $(document).find('.wpgs-gallery-attr').length);
			}
		});

		function wcgsGalleryClickEvents() {
			var lightbox = (settings.lightbox == 1) ? true : false;
			var wcgs_img_count = $("#wpgs-gallery").find('.gallery-navigation-carousel .wcgs-slider-image:not(.slick-cloned)').length;
			if (lightbox) {
				if (wcgs_img_count > 1) {
					$('.wcgs-carousel').on('afterChange', function (event, slick, currentSlide, nextSlide) {
						$('.wcgs-lightbox a').attr('data-fancybox-index', currentSlide + 1);
					});
				}
			}
		}
		// Lightbox init.
		$(function () {
			wcgsGalleryClickEvents();
		});
		
		// Fix the conflict of Variation Swatches plugin.
		$(document).on('click', '.wcgs-gallery-slider .variations .select-option.swatch-wrapper', function (e) {
			var $this = $(this);
			var $option_wrapper = $this.closest('div.select').eq(0);
			var $wc_select_box = $option_wrapper.find('select').first();
			$wc_select_box.trigger('change');
		});

	});
})(jQuery);
