(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.revealOverview = {
    attach: function (context, settings) {
      $('.diff-link', context).each(function () {
        var langcode = $(this).data('langcode');
        $('<a>', {
          text: $(this).data('text'),
          href: '#',
          click: function (event) {Drupal.behaviors.revealOverview.changeUrl(event, langcode);},
          class: 'use-ajax',
          'data-reveal-langcode': langcode,
          'data-dialog-type': 'modal',
          'data-dialog-options': JSON.stringify({'width': 700})
        }).appendTo(this);
      });
    },
    changeUrl: function(event, langcode) {
      var left = $('input[name=radios_left_' + langcode + ']:checked').val();
      var right = $('input[name=radios_right_' + langcode + ']:checked').val();
      if (left && right) {
        var location = window.location.href.replace(/[#?].*$/, '');
        Drupal.ajax.instances.forEach(function (element) {
          if ($(element.element).data('reveal-langcode') === langcode) {
            element.options.url = location + '/diff/' + langcode + '/' + left + '/' + right;
          }
        });
      }
      else {
        alert(Drupal.t('You need to select two ' + langcode + ' revisions first'));
        event.stopImmediatePropagation();
      }
    }
  }
})(jQuery, Drupal, drupalSettings);
