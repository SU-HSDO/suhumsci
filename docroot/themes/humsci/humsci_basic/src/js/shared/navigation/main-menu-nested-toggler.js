import changeNav from './change-nav';
import togglerHandler from './toggler-handler';

const togglers = document.querySelectorAll('.hb-nested-toggler');
const mobileNavBreakpoint = 992;

if (togglers) {
  for (let i = 0; i < togglers.length; i += 1) {
    let windowWidth = window.innerWidth;
    const toggler = togglers[i];
    const togglerID = toggler.getAttribute('id');
    const togglerContent = document.querySelector('[aria-labelledby="'.concat(togglerID, '"]'));
    const togglerParent = toggler.parentNode;

    // Togglers should always have content but in the event that they don't we
    // don't want the rest of the togglers on the page to break.
    if (!togglerContent) {
      continue;
    }

    toggler.addEventListener('click', (e) => togglerHandler(e, toggler, togglerContent));

    // Some togglers will be anchor tags instead of buttons and they should behave
    // like a button when the spacebar is pressed
    toggler.addEventListener('keydown', (e) => {
      // 32 is the keycode for the spacebar
      if (e.which !== 32) {
        return;
      }

      e.preventDefault();

      const isExpanded = e.target.getAttribute('aria-expanded') === 'true';
      changeNav(toggler, togglerContent, !isExpanded);
    });

    // At larger screen sizes:
    // =========================================================================
    // All menus collapse when resizing larger than the lg breakpoint
    window.addEventListener('resize', () => {
      windowWidth = window.innerWidth;

      // When resizing from mobile to desktop, show the navigation
      if (windowWidth >= mobileNavBreakpoint) {
        changeNav(toggler, togglerContent, false);
      }
    });

    // We want to close open dropdowns on desktop when the following events happen
    // on the body, outside of the toggler component:
    // 1. (focusin) When tabbing through the navigation the previously opened dropdown closes
    // 2. (click) When clicking outside of the dropdown area it will close
    ['focusin', 'click'].forEach((event) => {
      document.body.addEventListener(event, (e) => {
        if (windowWidth >= mobileNavBreakpoint && !togglerParent.contains(e.target)) {
          changeNav(toggler, togglerContent, false);
        }
      }, false);
    });
  }
}
