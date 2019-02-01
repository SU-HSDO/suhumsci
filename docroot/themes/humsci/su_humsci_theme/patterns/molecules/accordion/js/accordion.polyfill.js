(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.accordionPattern = {
    attach: function (context, settings) {
      // $('details').once('details-toggle').on('toggle', function () {
      //   if ($(this).attr('open')) {
      //     $(this).find('[role="button"]').attr('aria-expanded', 'true');
      //   }
      //   else {
      //     $(this).find('[role="button"]').attr('aria-expanded', 'false');
      //   }
      // });
    }
  };
})(jQuery, Drupal);
