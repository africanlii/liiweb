/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.liibarrio2020_general = {
    attach: function (context, settings) {
      $('#page_loader').addClass("hide");
      var position = $(window).scrollTop();
      $("body").removeClass("add_fixed scrolled scrollup scrolldown");
      if($(window).width()<992){
        var widthFlag = true;
      }
      else{
        var widthFlag = false;
      }
      $(window).scroll(function () {

        if($(this).scrollTop() > 600){
          $("body").addClass("scrolled");
        }
        else if ($(this).scrollTop() > 300) {
          $("body").addClass("add_fixed");
        } 
        else {
          $("body").removeClass("add_fixed scrolled");
        }
        var scroll = $(window).scrollTop();
        if (scroll > position) {
          $("body").addClass("scrolldown");
          $("body").removeClass("scrollup");
          if(widthFlag){
            $(".navbar-collapse").collapse('hide');
          }
          
        } else {
          $("body").addClass("scrollup");
          $("body").removeClass("scrolldown");
          if(widthFlag){
            $(".navbar-collapse").collapse('hide');
          }
        }
        position = scroll;
      });

      /*Interactions using bootstrap jquery stuff*/
      $(function () {
        $('[data-toggle="popover"]').popover();
        $('.popover-dismiss').popover({
          trigger: 'focus'
        })
        
        $('.collapse').collapse({
          toggle: false
        });
        
        $('.collapse').on({
          "show.bs.collapse": function (e) {
            $(this).parents("tr").addClass("expanded");
          },
          "hide.bs.collapse": function (e) {
            $(this).parents("tr").removeClass("expanded");
          }
        }
        )
      })

    },
  };

  Drupal.behaviors.liibarrioSummarySearchBrowserInput = {
    attach: function (context, settings) {
      var viewSummary = ".view--liibarrio-subject :input";
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
