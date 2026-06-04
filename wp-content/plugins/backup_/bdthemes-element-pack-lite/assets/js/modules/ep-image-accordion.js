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
