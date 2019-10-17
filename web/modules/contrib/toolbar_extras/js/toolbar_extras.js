(function ($, Drupal, drupalSettings) {
  if (!window.matchMedia('only screen').matches) {
    return;
  }
  function updateToolbarHeight(){
    var toolbarTabOuterHeight = $('#toolbar-bar').find('.toolbar-tab').outerHeight() || 0;
    var toolbarTrayHorizontalOuterHeight = $('.is-active.toolbar-tray-horizontal').outerHeight() || 0;

    $('body').css({
      'padding-top': toolbarTabOuterHeight + toolbarTrayHorizontalOuterHeight
    });
  }
  setTimeout(updateToolbarHeight, 100);
})(jQuery, Drupal, drupalSettings);