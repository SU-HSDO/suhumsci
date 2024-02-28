Drupal.behaviors.defaultShortcuts = {
  attach: function (context, settings) {
    function alterDefaultToolbarItem() {
      const shortcutsItem = context.querySelector('#toolbar-item-shortcuts');
      if (shortcutsItem && window.localStorage.getItem('Drupal.toolbar.activeTabID') !== '"toolbar-item-shortcuts"') {
        shortcutsItem.click();
      }
    }
    window.addEventListener('load', function() {
      alterDefaultToolbarItem();
    })
  }
}