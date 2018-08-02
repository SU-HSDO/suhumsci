(function ($, window, Drupal) {
  'use strict';

  Drupal.behaviors.mrcEventsAdmin = {
    attach: function attach() {
      $('input.show-end-date[type="checkbox"]').each(function () {
        var parent = $(this).closest('.field--type-daterange');
        if ($(this).is(':checked')) {
          $(parent).find('.end-date').show();
        }
        else {
          $(parent).find('.end-date').hide();
        }
      }).change(function () {
        var parent = $(this).closest('.field--type-daterange');
        if ($(this).is(':checked')) {
          var $startDate = $(parent).find('.start-date');
          var $endDate = $(parent).find('.end-date');

          $startDate.find('select').each(function () {
            var name = $(this).attr('name').split('[').pop();
            var value = $(this).val();
            $endDate.find('select[name*="end_value][' + name + '"]').val(value).trigger("chosen:updated");
          });

          $endDate.show();
        }
        else {
          $(parent).find('.end-date').hide();
        }
      })
    }
  };
})(jQuery, window, Drupal);
