/**
 * @file
 * IE11 only
 *
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.ie11Only = {
    attach: function (context, settings) {
      var isIE11 = !!navigator.userAgent.match(/Trident.*rv\:11\./);

      if (isIE11) {
        $("#nav-twitter a").hide();
        $("#nav-twitter").append(
          '<div class="ie11-twitter-message"><p>Your browser is no longer supported by Twitter Feeds</p></div>'
        );

        $("#nav-facebook-tab").click();
      }
    },
  };
})(jQuery, Drupal);
