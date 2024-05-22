(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.hsEventsAdmin = {
    attach: function attach(context, settings) {
      $('input.show-end-date[type="checkbox"]', context).each(function () {
        var parent = $(this).closest('fieldset');
        if ($(this).is(':checked')) {
          $(parent).find('.end-date').show();
        }
        else {
          $(parent).find('.end-date').hide();
        }
      }).change(function () {
        var parent = $(this).closest('fieldset');
        if ($(this).is(':checked')) {
          var $startDate = $(parent).find('.start-date');
          var $endDate = $(parent).find('.end-date');

          $startDate.find('select[name^="field_*"], input[name^="field_*"]').each(function () {
            console.log($(this));
            var name = $(this).attr('name').split('[').pop();
            var value = $(this).val();
          });

          $endDate.show();
        }
        else {
          $(parent).find('.end-date').hide();
        }
      })
    }
  };
})(jQuery, Drupal);
