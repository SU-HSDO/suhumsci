(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.hsPerson = {
    attach: function (context, settings) {
      $('.node-hs-person-field-hs-person-email', context).once('wrapped').each(function () {
        if ($(this).next('.node-hs-person-field-hs-person-office').length) {
          $(this).next('.node-hs-person-field-hs-person-office').addBack().wrapAll('<div class="email-office-wrapper" />')
          $(this).after('<div class="divider">|</div>');
        }
      });
    }
  };
})(jQuery, Drupal);
