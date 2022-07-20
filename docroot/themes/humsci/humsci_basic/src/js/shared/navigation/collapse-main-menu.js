import changeNav from './change-nav';

// The main menu is expanded by default,
// which allows users who have JavaScript disabled to navigate.
// This script collapses the pre-expanded menus so
// it's ready to use for those w/ JavaScript enabled.
const mainToggle = document.querySelector('.hb-main-nav__toggle');
const mainNavContent = document.querySelector('.hb-main-nav__menu-lv1');
const nestedTogglers = document.querySelectorAll('.hb-nested-toggler');
const isBelowMobileNavBreakpoint = (window.innerWidth < 992);

// Collapse the main hamburger nav on mobile.
if (isBelowMobileNavBreakpoint && mainToggle) {
  changeNav(mainToggle, mainNavContent, false);
}

// Collapse the subnavs at all screen sizes.
if (nestedTogglers) {
  for (let i = 0; i < nestedTogglers.length; i += 1) {
    const toggler = nestedTogglers[i];
    const togglerID = toggler.getAttribute('id');
    const togglerContent = document.querySelector('[aria-labelledby="'.concat(togglerID, '"]'));
    const subnavIsActive = !!toggler.parentNode.classList.contains('hb-main-nav__item--active-trail');

    if (!togglerContent) {
      continue;
    }

    // On page load, all menus in the active section should be expanded on mobile.
    // All other menus should be hidden.
    const isExpanded = !!((subnavIsActive && isBelowMobileNavBreakpoint));
    changeNav(toggler, togglerContent, isExpanded);
  }
}

// Now that we've manually collapsed the main nav and subnavs,
// we can remove the "still loading" class and disable the CSS-powered menu suppression.
if (mainToggle) {
  document.querySelector('.hb-main-nav--is-still-loading').classList.remove('hb-main-nav--is-still-loading');
}
