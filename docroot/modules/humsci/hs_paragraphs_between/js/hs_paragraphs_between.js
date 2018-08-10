(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.paragraphsBetween = {
    attach: function (context, settings) {
      $('.paragraphs-between-wrapper', context).once('between-buttons').each(function (i, buttonWrapper) {
        buttonWrapper = $(buttonWrapper);
        // buttonWrapper.hide();
        buttonWrapper.children('.paragraphs-between-buttons').hide();

        buttonWrapper.append($('<a>', {
          href: '#',
          class: 'add-below-expand',
          title: Drupal.t('Add Below'),
          html: $('<span>', {
            class: 'visually-hidden',
            html: Drupal.t('Add Below')
          })
        }).click(function (e) {
          e.preventDefault();
          $(this).siblings().show();
          $(this).hide();
        }));

        buttonWrapper.closest('tr').hover(function () {
          if ($(this).find('.paragraphs-subform').length) {
            return;
          }
          buttonWrapper.show();
        }, function () {
          buttonWrapper.hide();
          buttonWrapper.children('.paragraphs-between-buttons').hide();
          buttonWrapper.find('.add-below-expand').show();
        })
      });
    }
  };
})(jQuery, Drupal);
