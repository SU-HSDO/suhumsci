(function ($, Drupal, once) {
  Drupal.behaviors.hsGroupBlocksAdmin = {
    attach: function (context, settings) {
      const previewChanges = $('[name=toggle_content_preview]');
      if (!previewChanges.is(':checked')) {
        $('[data-layout-content-preview-placeholder-label] > [data-layout-content-preview-placeholder-label]').show();
      }

      previewChanges.once('group-blocks').on('change', function () {
        if (!previewChanges.is(':checked')) {
          $('[data-layout-content-preview-placeholder-label] > [data-layout-content-preview-placeholder-label]').show();
        }
      })

    }
  };
})(jQuery, Drupal, once);
