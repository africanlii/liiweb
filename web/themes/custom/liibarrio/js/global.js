/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.liibarrio = {
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


  /**
   * Open share links in a popup.
   */
  Drupal.behaviors.shareLinks = {
    attach: function attach(context) {
      var shareButtons = document.querySelectorAll("a.popup-share-link");
      for (var i = 0; i < shareButtons.length; i++) {
        var shareButton = shareButtons[i];
        shareButton.addEventListener("click", function (e) {
          var Config = {
            Width: 500,
            Height: 500
          };
          e.preventDefault();
          // popup position
          var px = Math.floor(((screen.availWidth || 1024) - Config.Width) / 2),
            py = Math.floor(((screen.availHeight || 700) - Config.Height) / 2);
          // open popup
          var popup = window.open($(this).attr("href"), "social", "width=" + Config.Width + ",height=" + Config.Height + ",left=" + px + ",top=" + py + ",location=0,menubar=0,toolbar=0,status=0,scrollbars=1,resizable=1");
          if (popup) {
            popup.focus();
            if (e.preventDefault) e.preventDefault();
            e.returnValue = false;
          }
          return !!popup;
        });
      }
    }
  };

})(jQuery, Drupal);
