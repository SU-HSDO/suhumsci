Drupal.behaviors.defaultShortcuts = {
  attach: function (context, settings) {
    function alterDefaultToolbarItem() {
      const shortcutsItem = context.querySelector('#toolbar-item-shortcuts');
      if (shortcutsItem) {
        shortcutsItem.click();
      }
    }
    window.addEventListener('load', function() {
      alterDefaultToolbarItem();
    })
  }
}