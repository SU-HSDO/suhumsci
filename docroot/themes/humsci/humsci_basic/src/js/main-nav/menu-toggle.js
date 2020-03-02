const menuToggle = document.querySelector('.su-main-nav__toggle');
const mainMenu = document.querySelector('.su-main-nav__menu-lv1');
const mobileNavBreakpoint = 992;
let windowWidth = window.innerWidth;
let isHidden;
let wasDesktopSize;

const collapseNav = () => {
  menuToggle.setAttribute('aria-expanded', false);
  mainMenu.setAttribute('aria-hidden', true);
  menuToggle.innerHTML = "Menu";
  isHidden = true;
}

const showNav = () => {
  menuToggle.setAttribute('aria-expanded', true);
  mainMenu.setAttribute('aria-hidden', false);
  menuToggle.innerHTML = "Close";
  isHidden = false;
}

if (menuToggle) {
  // If below a certain breakpoint, hide the nav on page load
  if (window.innerWidth < mobileNavBreakpoint) {
    collapseNav();
  }

  // Toggle the nav when the the button is clicked
  menuToggle.addEventListener('click', () => {
    if (isHidden) {
      showNav();
    } else {
      collapseNav();
    }
  });

  // Handle the showing/hiding of the nav when resizing the browser
  window.addEventListener('resize', () => {
    windowWidth = window.innerWidth;

    // When resizing from mobile to desktop, show the navigation
    if (windowWidth >= mobileNavBreakpoint) {
      showNav();
      wasDesktopSize = true;
    }

    // When resizing from desktop to mobile, hide the navigation
    if (windowWidth < mobileNavBreakpoint && wasDesktopSize) {
      collapseNav();

      // This keeps the navigation from collapsing every time the screen is resized
      // below remains the mobileNavBreakpoint
      wasDesktopSize = false;
    }
  });
}
