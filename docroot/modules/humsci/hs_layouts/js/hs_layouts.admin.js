(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.hsLayoutsAdmin = {
    attach: function attach(context, settings) {
      $('.contextual-region', context).once('hs-layouts').each(function () {
        var $region = $(this);

        // Wait for contextual links to be added.
        setTimeout(function(){
          $region.find('button').click(function () {

            // Sometimes the field isn't tall enough to be able to click on the
            // contextual links, so lets make them at least as tall as the
            // contextual links.
            $region.css('min-height', '100px');
          });
        }, 1000);
      });
    }
  };
})(jQuery, Drupal);
