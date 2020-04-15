(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.polyfillSticky = {
    attach: function (context, settings) {
      var stickyClass = $(".sticky");
      Stickyfill.add(stickyClass);
    },
  };
})(jQuery, Drupal);
