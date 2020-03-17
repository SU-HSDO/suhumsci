import changeNav from './change-nav';

const togglers = document.querySelectorAll('.hb-nested-toggler');
const mobileNavBreakpoint = 992;
let windowWidth = window.innerWidth;

if (togglers) {
  for (let i = 0; i < togglers.length; i += 1) {
    const toggler = togglers[i];
    const togglerID = toggler.getAttribute('id');
    const togglerContent = document.querySelector(`[aria-labelledby="${togglerID}"]`);
    const togglerParent = toggler.parentNode;
    const subnavIsExpanded = togglerParent.classList.contains('hb-main-nav__item--active-trail') ? true : false;
    let isHidden;

    // Togglers should always have content but in the event that they don't we
    // don't want the rest of the togglers on the page to break.
    if (togglerContent) {
      // On page load:
      // - All menus in the active section should be open
      // - All other menus should be hidden
      if (subnavIsExpanded && windowWidth < mobileNavBreakpoint) {
        changeNav(toggler, togglerContent, true);
        isHidden = false;
      } else {
        changeNav(toggler, togglerContent, false);
        isHidden = true;
      }

      toggler.addEventListener('click', (e) => {
        e.preventDefault();

        // Toggle the aria-hidden and aria-expanded values on click
        if (isHidden) {
          changeNav(toggler, togglerContent, true);
          isHidden = false;
        } else {
          changeNav(toggler, togglerContent, false);
          isHidden = true;
        }
      });

      // Some togglers will be anchor tags instead of buttons and they should behave
      // like a button when the spacebar is pressed
      toggler.addEventListener('keydown', (e) => {
        // 32 is the keycode for the spacebar
        if (e.which !== 32) {
          return;
        }

        e.preventDefault();
        if (isHidden) {
          changeNav(toggler, togglerContent, true);
          isHidden = false;
        } else {
          changeNav(toggler, togglerContent, false);
          isHidden = true;
        }
      });

      // At larger screen sizes:
      // =========================================================================
      // All menus collapse when resizing larger than the lg breakpoint
      window.addEventListener('resize', () => {
        windowWidth = window.innerWidth;

        // When resizing from mobile to desktop, show the navigation
        if (windowWidth >= mobileNavBreakpoint) {
          changeNav(toggler, togglerContent, false);
          isHidden = true;
        }
      });

      // We want to close open dropdowns on desktop when the following events happen
      // on the body, outside of the toggler component:
      // 1. (focusin) When tabbing through the navigation the previously opened dropdown closes
      // 2. (click) When clicking outside of the dropdown area it will close
      ["focusin", "click"].forEach(function(event) {
        document.body.addEventListener(event, (e) => {
          if (windowWidth >= mobileNavBreakpoint && !togglerParent.contains(e.target)) {
            changeNav(toggler, togglerContent, false);
            isHidden = true;
          }
        }, false);
      });
    }
  }
}
