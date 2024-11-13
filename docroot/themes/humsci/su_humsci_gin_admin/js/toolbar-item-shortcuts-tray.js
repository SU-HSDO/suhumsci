((Drupal) => {
  Drupal.behaviors.toolbarItemShortcutsTray = {
    attach(context) {
      const toolbarItemShortcutsTrayItems = context.querySelectorAll(
        "#toolbar-item-shortcuts-tray li > ul li"
      );
      if (toolbarItemShortcutsTrayItems) {
        toolbarItemShortcutsTrayItems.forEach(function (li) {
          if (li.querySelector("ul")) {
            li.classList.add("has-submenu");
          }
        });
      }
    },
  };
})(Drupal);
