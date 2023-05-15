
(function ($, Drupal, once) {
  Drupal.behaviors.hsGroupBlocksAdmin = {
    attach: function (context) {
      const previewChanges = $('[name=toggle_content_preview]', context);
      if (!previewChanges.is(':checked')) {
        $('[data-layout-content-preview-placeholder-label] > [data-layout-content-preview-placeholder-label]').show();
      }

      $(once('group-blocks', '[name=toggle_content_preview]', context)).on('change', function () {
        if (!previewChanges.is(':checked')) {
          $('[data-layout-content-preview-placeholder-label] > [data-layout-content-preview-placeholder-label]').show();
        }
      });
    }
  };
})(jQuery, Drupal, once);
