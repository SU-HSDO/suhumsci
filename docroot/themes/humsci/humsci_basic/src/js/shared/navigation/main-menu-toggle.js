import changeNav from './change-nav';

(function (Drupal, window, once) {
  Drupal.behaviors.toggleNavigation = {
    attach(context) {
      const menuToggle = once('toggle-navigation', '.hb-main-nav__toggle', context)[0];
      const mainMenu = once('main-menu', '.hb-main-nav__menu-lv1', context)[0];
      const mobileNavBreakpoint = 992;
      let windowWidth;
      let wasDesktopSize;

      if (menuToggle) {
        // Toggle the nav when the the button is clicked
        menuToggle.addEventListener('click', () => {
          const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';

          changeNav(menuToggle, mainMenu, !isExpanded);
        });

        // Handle the showing/hiding of the nav when resizing the browser
        window.addEventListener('resize', () => {
          windowWidth = window.innerWidth;

          // When resizing from mobile to desktop, ensure navigation is displayed, not hidden
          // If wasDesktopSize is false, it means we haven't gotten there yet
          // and will to run this check
          // Otherwise, if wasDesktopSize is true,
          // we are above the mobileNavBreakpoint and don't need to keep showingNav
          if (windowWidth >= mobileNavBreakpoint && !wasDesktopSize) {
            changeNav(menuToggle, mainMenu, true);
            wasDesktopSize = true;
          }

          // When resizing from desktop to mobile, hide the navigation
          if (windowWidth < mobileNavBreakpoint && wasDesktopSize) {
            changeNav(menuToggle, mainMenu, false);

            // This keeps the navigation from collapsing every time the screen is resized
            // below remains the mobileNavBreakpoint
            // After the first time we resize to below the mobileNavBreakpoint,
            // reset wasDesktopSize var
            wasDesktopSize = false;
          }
        });
      }
    },
  };
// eslint-disable-next-line no-undef
}(Drupal, window, once));
