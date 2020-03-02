const togglers = document.querySelectorAll('.hb-nested-toggler');
const mobileNavBreakpoint = 992;
let windowWidth = window.innerWidth;

if (togglers) {
  for (let i = 0; i < togglers.length; i += 1) {
    const toggler = togglers[i];
    const togglerID = toggler.getAttribute('id');
    const togglerContent = document.querySelector(`[aria-labelledby="${togglerID}"]`);
    const togglerParent = toggler.closest('.su-main-nav__item--parent');
    const shouldBeOpen = togglerParent.classList.contains('su-main-nav__item--active-trail') ? true : false;
    let isHidden;

    const collapseMenu = () => {
      togglerContent.setAttribute('aria-hidden', true);
      toggler.setAttribute('aria-expanded', false);
      isHidden = true;
    }

    const showMenu = () => {
      togglerContent.setAttribute('aria-hidden', false);
      toggler.setAttribute('aria-expanded', true);
      isHidden = false;
    }

    // On page load:
    // - All menus in the active section should be open
    // - All other menus should be hidden
    if (shouldBeOpen && windowWidth < mobileNavBreakpoint) {
      showMenu();
    } else {
      collapseMenu();
    }

    toggler.addEventListener('click', (e) => {
      e.preventDefault();

      // Toggle the aria-hidden and aria-expanded values on click
      if (isHidden) {
        showMenu();
      } else {
        collapseMenu();
      }
    });

    // At larger screen sizes:
    // =========================================================================
    // All menus collapse when resizing larger than the lg breakpoint
    window.addEventListener('resize', () => {
      windowWidth = window.innerWidth;

      // When resizing from mobile to desktop, show the navigation
      if (windowWidth >= mobileNavBreakpoint) {
        collapseMenu();
      }
    });

    // When tabbing through the navigation the previously opened dropdown closes
    document.body.addEventListener('focusin', (e) => {
      if (windowWidth >= mobileNavBreakpoint && !togglerParent.contains(e.target)) {
        collapseMenu();
      }
    });

    // When clicking outside of the dropdown area it will close
    document.body.addEventListener('click', (e) => {
      if (windowWidth >= mobileNavBreakpoint && !togglerParent.contains(e.target)) {
        collapseMenu();
      }
    });
  }
}
