(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.niceScrollTableOfContents = {
    attach: function (context, settings) {
      $(".table-of-contents.table-of-contents--main").niceScroll({
        cursorcolor: "#d5d5d5",
        cursorwidth: "10px",
        autohidemode: false,
      });
    },
  };
})(jQuery, Drupal);
