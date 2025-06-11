((Drupal) => {
  Drupal.behaviors.duplicateProfilesAjaxViewScrollOffset = {
    attach() {
      // Override the default Drupal Views AJAX scrolling behavior.
      if (Drupal.AjaxCommands.prototype.scrollTop) {
        Drupal.AjaxCommands.prototype.scrollTop = function (ajax, response) {
          // Prevent automatic scrolling by overriding the function.
          const selector = response.selector;

          // Gets the selector of the updated view that Drupal wants to scroll to.
          const targetElement = document.querySelector(selector);
          if (!targetElement) return;

          let scrollTarget = targetElement;

          // Traverse up the DOM until we find a scrollable parent
          while (
            scrollTarget.scrollTop === 0 &&
            scrollTarget.parentElement
          ) {
            scrollTarget = scrollTarget.parentElement;
          }

          const rect = targetElement.getBoundingClientRect();
          const scrollTop = window.scrollY || document.documentElement.scrollTop;
          const offsetTop = rect.top + scrollTop;

          // Subtracts a fixed height for the sticky header so the element isn't hidden behind it when scrolling.
          const headerHeight = 230;
          const scrollPosition = offsetTop - headerHeight;

          // Scroll only if the element is above the current scroll position
          if (scrollPosition < scrollTarget.scrollTop) {
            scrollTarget.scrollTo({
              top: scrollPosition,
              behavior: 'smooth',
            });
          }
        };
      }
    },
  };
})(Drupal);
