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

  Drupal.behaviors.legislationSearch = {
    attach: function (context, settings) {
      var inputs = $(
        "#views-exposed-form-index-search-legislation-bk-legislation-search input"
      );

      // Add 'for' attribute to label with corrosponding id.
      inputs.each(function (index) {
        //alert(index + ": " + $(this).attr("id"));

        var id = $(this).attr("id");
        $(this).next("label").attr("for", id);
      });
    },
  };

  Drupal.behaviors.judgmentBrowser = {
    attach: function (context, settings) {
      var inputs = $("#views-exposed-form-judgment-bk-judgment-browser input");

      // Add 'for' attribute to label with corrosponding id.
      inputs.each(function (index) {
        //alert(index + ": " + $(this).attr("id"));

        var id = $(this).attr("id");
        $(this).next("label").attr("for", id);
      });
    },
  };

  Drupal.behaviors.legislationBrowser = {
    attach: function (context, settings) {
      var inputs = $(
        "#views-exposed-form-legislation-bk-legislation-browser input"
      );

      // Add 'for' attribute to label with corrosponding id.
      inputs.each(function (index) {
        //alert(index + ": " + $(this).attr("id"));

        var id = $(this).attr("id");
        $(this).next("label").attr("for", id);
      });
    },
  };
})(jQuery, Drupal);
