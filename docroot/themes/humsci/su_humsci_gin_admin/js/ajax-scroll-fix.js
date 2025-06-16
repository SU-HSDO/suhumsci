((Drupal) => {
  Drupal.behaviors.duplicateProfilesAjaxViewScrollOffset = {
    attach() {
      if (!Drupal.AjaxCommands.prototype.scrollTop) return;

      // Calculate offset top
      function calculateElementOffset(element) {
        const rect = element.getBoundingClientRect();
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        return rect.top + scrollTop;
      }

      // Calculate total sticky offset height
      function calculateStickyHeaderHeight(element) {
        let headerHeight = 0;

        // Define an array of elements that contribute to sticky height
        const stickyElements = [
          document.getElementById("toolbar-bar"),
          document.querySelector(".toolbar-tray.is-active"),
          document.querySelector("header.region-sticky"),
          element?.parentElement?.parentElement?.querySelector("h2"),
        ];

        // Loop through elements and add height only if they exist
        stickyElements.forEach((el) => {
          if (el && el.offsetHeight) {
            headerHeight += el.offsetHeight;
          }
        });

        // Get padding top from the block element safely
        const blockElement = element?.parentElement?.parentElement;
        const computedStyle = blockElement
          ? getComputedStyle(blockElement)
          : null;
        const paddingTop = computedStyle
          ? parseFloat(computedStyle.paddingTop) || 0
          : 0;

        headerHeight += paddingTop;

        return headerHeight;
      }

      // Override the default Drupal Views AJAX scrolling behavior.
      Drupal.AjaxCommands.prototype.scrollTop = function (ajax, response) {
        // Prevent automatic scrolling by overriding the function.
        const selector = response.selector;

        // Gets the selector of the updated view that Drupal wants to scroll to.
        const targetElement = document.querySelector(selector);
        if (!targetElement) return;

        let scrollTarget = targetElement;

        // Traverse up the DOM until we find a scrollable parent
        while (scrollTarget.scrollTop === 0 && scrollTarget.parentElement) {
          scrollTarget = scrollTarget.parentElement;
        }

        // Calculate offset top
        const offsetTop = calculateElementOffset(targetElement);

        // Calculate total sticky offset height
        const headerHeight = calculateStickyHeaderHeight(targetElement);

        // Subtracts a fixed height for the sticky header so the element isn't hidden behind it when scrolling.
        const scrollPosition = offsetTop - headerHeight;

        // Scroll only if the element is above the current scroll position
        if (scrollPosition < scrollTarget.scrollTop) {
          scrollTarget.scrollTo({
            top: scrollPosition,
            behavior: "smooth",
          });
        }
      };
    },
  };
})(Drupal);
