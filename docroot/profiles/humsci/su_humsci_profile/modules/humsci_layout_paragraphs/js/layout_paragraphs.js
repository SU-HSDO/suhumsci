(($, Drupal ) => {
  Drupal.behaviors.humsciLayoutParagraphs = {
    attach: function attach(context, settings) {
      if (typeof CKEDITOR !== 'undefined') {
        Object.keys(CKEDITOR.instances).forEach(instanceId => {
          CKEDITOR.instances[instanceId].on('unlockSnapshot', snapshot => {
            snapshot.editor.fire('change');
          })
        })
      }
    },
  };
})(jQuery, Drupal);
