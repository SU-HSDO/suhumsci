(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.hsFieldHelpersViewsReset = {
    attach: function attach(context, settings) {
      console.log(context);
      console.log(Drupal.views);

      $.each(Drupal.views.instances, function (key, view) {
        $(view['$exposed_form']).find('input[data-drupal-selector=edit-reset]').click(function (e) {
          e.preventDefault();

          $(view['$exposed_form'])[0].reset();
          $(view['$view']).trigger('RefreshView');
        });
      });
    }
  };
})(jQuery, Drupal);
