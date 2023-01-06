const menu = document.querySelector('.js-megamenu');

// Because all JS is smashed together instead of using libraries,
// we need to 'if' all the parent variables we create.
if (menu) {
  const menuBtnMobile = document.querySelector('.js-megamenu__mobile-btn');
  const menuList = menu.querySelector('.js-megamenu__list--main');
  const menuBtns = menu.querySelectorAll('.js-megamenu__toggle');
  const mobileNavBreakpoint = 992;
  let windowWidth = window.innerWidth;
  let isMobile = windowWidth < mobileNavBreakpoint;

  window.addEventListener('resize', () => {
    windowWidth = window.innerWidth;

    isMobile = windowWidth <= mobileNavBreakpoint;
  });

  // Toggle util function for expanded menus
  const toggleMenu = (btn) => {
    const list = btn.nextElementSibling;

    list.classList.remove('is-expanded');
    btn.classList.remove('is-expanded');
    btn.setAttribute('aria-expanded', 'false');
  };

  // Closes open submenus if another menu btn is clicked.
  const closeAllSubmenus = (currentBtn) => {
    menuBtns.forEach((btn) => {
      if (btn !== currentBtn) {
        toggleMenu(btn);
      }
    });
  };

  // Closes an open menu if user clicks outside
  document.body.addEventListener('mousedown', (e) => {
    const isClickInsideMenu = menu.contains(e.target);

    if (!isClickInsideMenu) {
      menuBtns.forEach((btn) => {
        toggleMenu(btn);
      });
    }
  });

  // Toggle aria-[anything] attribute from true / false.
  const toggleAria = (el, aria) => {
    let x = el.getAttribute(`aria-${aria}`);
    if (x === 'true') {
      x = 'false';
    } else {
      x = 'true';
    }
    el.setAttribute(`aria-${aria}`, x);
  };

  // Displays nested child menus on mobile
  const expandMobileSubmenus = () => {
    const activeParent = document.querySelector('.js-megamenu__active-trail');
    const childMenu = activeParent.nextElementSibling;

    toggleAria(activeParent, 'expanded');
    activeParent.classList.add('is-expanded');
    childMenu.classList.add('is-expanded');
  };

  if (menuBtnMobile) {
    // Toggle nav immediately for JS visitors
    toggleAria(menuBtnMobile, 'expanded');

    if (isMobile) {
      expandMobileSubmenus();
    }

    // Toggle the nav when the the button is clicked
    menuBtnMobile.addEventListener('click', () => {
      toggleAria(menuBtnMobile, 'expanded');

      if (menuList) {
        menuList.classList.toggle('is-active');
      }
    });
  }

  menuBtns.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      const currentBtn = e.currentTarget;
      const menuItem = e.currentTarget.parentElement;
      const subMenu = menuItem.querySelector('.js-megamenu__expanded-container');
      // We want to only close expanded menus on desktop to mimic current
      // menu functionality.
      if (!isMobile) {
        closeAllSubmenus(currentBtn);
      }
      toggleAria(currentBtn, 'expanded');

      btn.classList.toggle('is-expanded');
      subMenu.classList.toggle('is-expanded');
    });
  });
}
