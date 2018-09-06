(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.hsFieldHelpersViewsReset = {
    attach: function attach(context, settings) {

      $.each(Drupal.views.instances, function (key, ajaxView) {
        ajaxView['$exposed_form'].find('input[data-drupal-selector=edit-reset]').once('views-reset').click(function (e) {
          e.preventDefault();

          // Reset the form and trigger chosen select fields.
          ajaxView['$exposed_form'][0].reset();
          $(ajaxView['$exposed_form'][0]).find('select').trigger("chosen:updated");

          // Trigger('RefreshView') causes the view to reload twice. So we use
          // a click action on the submit button after clearing all the field
          // values. Keeping the RefreshView in case we need it later.
          // Drupal.views.instances[key]['$view'].trigger('RefreshView');
          $(this).siblings('input[data-drupal-selector^="edit-submit-"]').click();
        });
      });
    }
  };
})(jQuery, Drupal);
