Drupal.behaviors.defaultShortcuts = {
  attach: function (context, settings) {
    if (!settings.user.uid) {
      // If the user is not logged in, clear the active toolbar tab ID.
      window.localStorage.removeItem('Drupal.toolbar.activeTabID');
      return;
    }

    // Get the toolbar shortcuts toolbar button. Only users with the
    // 'access shortcuts' permission will have this button.
    const shortcutsItem = context.querySelector('#toolbar-item-shortcuts');
    console.log('shortcutsItem', shortcutsItem);
    if (!window.localStorage.getItem('Drupal.toolbar.activeTabID') && shortcutsItem) {
      // If the tab ID is not set and the shortcuts toolbar button exists, click it.
      window.addEventListener('load', () => {
        shortcutsItem.click();
      });
    }
  },
};
