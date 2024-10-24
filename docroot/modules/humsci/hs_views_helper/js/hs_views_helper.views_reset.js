(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.hsFieldHelpersViewsReset = {
    attach: function attach(context, settings) {
      if (typeof (Drupal.views) === 'undefined') {
        return;
      }
      $.each(Drupal.views.instances, function (key, ajaxView) {
        $(once('views-reset', ajaxView['$exposed_form'].find('input[data-drupal-selector=edit-reset]'))).click(function (e) {
          e.preventDefault();

          // Reset the form and trigger chosen select fields.
          $(':input', ajaxView['$exposed_form'][0])
            .not(':button, :submit, :reset, :hidden')
            .val('')
            .prop('checked', false)
            .prop('selected', false);

          // Trigger('RefreshView') causes the view to reload twice. So we use
          // a click action on the submit button after clearing all the field
          // values. Keeping the RefreshView in case we need it later.
          // Drupal.views.instances[key]['$view'].trigger('RefreshView');
          $(this).siblings('input[data-drupal-selector^="edit-submit-"]').click();
        });
      });
    }
  };
})(jQuery, Drupal, once);
