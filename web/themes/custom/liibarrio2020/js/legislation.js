(function($) {
  function renameElement(el, name) {
    var newEl = document.createElement(name);

    for (var i = 0; i < el.attributes.length; i++) {
      newEl.setAttribute(
        el.attributes.item(i).nodeName,
        el.attributes.item(i).nodeValue,
      );
    }

    newEl.innerHTML = el.innerHTML;
    el.parentNode.replaceChild(newEl, el);

    return newEl;
  }

  $(function() {
    // tag term definition containers
    $('.akoma-ntoso .akn-def[data-refersto]').each(function(i, def) {
      var term = def.getAttribute("data-refersto").replace('#', '');
  
      $(def)
        .closest('.akn-p, .akn-subsection, .akn-section, .akn-blockList')
        .attr('data-defines', def.getAttribute('data-refersto'))
        .attr('id', 'defn-' + term);
    });
  
    // link term definitions
    $(".akoma-ntoso .akn-term[data-refersto]").each(function(i, term) {
      term = renameElement(term, 'a');
      term.setAttribute('href', '#defn-' + term.getAttribute("data-refersto").replace('#', ''));
    });
  
    // show definition popups
    $('.akn-term').popover({
      placement: 'top',
      trigger: 'hover',
      html: true,
      template: '<div class="popover term-definition" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body akoma-ntoso"></div></div>',
      delay: { show: 500 },
      title: function() {
        var term_id = $(this).data('refersto');
        var term = $('.akn-def[data-refersto="' + term_id + '"]').text();
        if (window.ga) ga('send', 'event', 'term-popup', term_id.replace('#', ''));
        return term;
      },
      content: function() {
        var term_id = $(this).data('refersto');
        return $('.akoma-ntoso [data-defines="' + term_id + '"]')[0].outerHTML;
      }
    });
  });
})(jQuery);