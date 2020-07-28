/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.liibarrio2020_general = {
    attach: function (context, settings) {
      var position = $(window).scrollTop();
      $(window).scroll(function () {
        if ($(this).scrollTop() > 50) {
          $("body").addClass("scrolled");
        } else {
          $("body").removeClass("scrolled");
        }
        var scroll = $(window).scrollTop();
        if (scroll > position) {
          $("body").addClass("scrolldown");
          $("body").removeClass("scrollup");
        } else {
          $("body").addClass("scrollup");
          $("body").removeClass("scrolldown");
        }
        position = scroll;
      });
    },
  };

  Drupal.behaviors.liibarrioSummarySearchBrowserInput = {
    attach: function (context, settings) {
      var viewSummary = ".view--liibarrio-summary :input";
      var viewSearch = ".view--liibarrio-search :input";
      var viewBrowser = ".view--liibarrio-browser :input";

      var viewClasses = [viewSummary, viewSearch, viewBrowser];

      for (var i = 0; i < viewClasses.length; i++) {
        var inputs = $(viewClasses[i]);

        // Add 'for' attribute to label with corrosponding id.
        inputs.each(function (index) {
          //alert(index + ": " + $(this).attr("id"));

          var id = $(this).attr("id");
          $(this).next("label").attr("for", id);
        });
      }
    },
  };
})(jQuery, Drupal);
