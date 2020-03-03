import changeNav from './change-nav';

const menuToggle = document.querySelector('.su-main-nav__toggle');
const mainMenu = document.querySelector('.su-main-nav__menu-lv1');
const mobileNavBreakpoint = 992;
let windowWidth = window.innerWidth;
let isHidden;
let wasDesktopSize;

if (menuToggle) {
  // If below a certain breakpoint, hide the nav on page load
  if (window.innerWidth < mobileNavBreakpoint) {
    changeNav(menuToggle, mainMenu, false);
    isHidden = true;
  }

  // Toggle the nav when the the button is clicked
  menuToggle.addEventListener('click', () => {
    if (isHidden) {
      changeNav(menuToggle, mainMenu, true);
      isHidden = false;
    } else {
      changeNav(menuToggle, mainMenu, false);
      isHidden = true;
    }
  });

  // Handle the showing/hiding of the nav when resizing the browser
  window.addEventListener('resize', () => {
    windowWidth = window.innerWidth;

    // When resizing from mobile to desktop, ensure navigation is displayed, not hidden
    // If wasDesktopSize is false, it means we haven't gotten there yet and will to run this check
    // Otherwise, if wasDesktopSize is true, we are above the mobileNavBreakpoint and don't need to keep showingNav
    if (windowWidth >= mobileNavBreakpoint && !wasDesktopSize) {
      changeNav(menuToggle, mainMenu, true);
      isHidden = false;

      wasDesktopSize = true;
    }

    // When resizing from desktop to mobile, hide the navigation
    if (windowWidth < mobileNavBreakpoint && wasDesktopSize) {
      changeNav(menuToggle, mainMenu, false);
      isHidden = true;

      // This keeps the navigation from collapsing every time the screen is resized
      // below remains the mobileNavBreakpoint
      // After the first time we resize to below the mobileNavBreakpoint, reset wasDesktopSize var
      wasDesktopSize = false;
    }
  });
}
