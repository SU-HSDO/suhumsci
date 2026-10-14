(function () {
  const GAP_CLASS = 'hb-tighten-hero-gap';

  /**
   * Checks if the first page component's own margin-top would collapse
   * through its wrapper and stack with the hero's margin-bottom, creating
   * an oversized gap. Mirrors the paragraph bundles targeted in
   * _layouts.general.scss.
   */
  function firstComponentCreatesGap(component) {
    if (component.classList.contains('ptype-hs-sptlght-slder')) {
      return true;
    }

    if (component.classList.contains('ptype-hs-accordion')) {
      // field_hs_accord_expcol ("Add Expand/Collapse All") adds this class
      // to the accordion when enabled (see hs_layouts_preprocess_pattern_accordion).
      // Without it, the accordion itself is the first visible element.
      return !component.querySelector('.hb-accordion_toggle-all');
    }

    if (component.classList.contains('ptype-hs-text-area')) {
      const textArea = component.querySelector('.field-hs-text-area');
      const firstElement = textArea ? textArea.firstElementChild : null;

      if (!firstElement) {
        return false;
      }

      const tag = firstElement.tagName.toLowerCase();

      if (/^h[1-6]$/.test(tag)) {
        return true;
      }

      return tag === 'p' && (
        firstElement.classList.contains('hs-font-splash')
        || firstElement.classList.contains('hs-font-lead')
      );
    }

    return false;
  }

  // Add body classes for full-width / main-region spacing gap
  // detection. Only applies to pages that use the full-width hero +
  // main region layout (see humsci_basic_preprocess_html()).
  document.addEventListener('DOMContentLoaded', () => {
    const hero = document.querySelector('.field-hs-page-hero');
    const components = document.querySelector('.field-hs-page-components');

    if (!hero && !components) {
      return;
    }

    const region = components.closest('.layout__region');
    const block = region?.querySelector('.block-layout-builder');
    const title = block?.querySelector('.node__title');

    // If the node's Title field block is shown above the page components,
    // it already breaks the margin adjacency between the hero and the
    // first component, so the gap doesn't need closing. Checking this
    // first (most pages render the title) avoids the more expensive
    // firstComponentCreatesGap() work below.
    if (title) {
      return;
    }

    // Removes the first Layout Builder block on the page if its node title
    // renders empty (e.g. the front page's suppressed "Home" title), and
    // reports whether it instead holds a visible title.
    if (block?.childElementCount === 0) {
      block.remove();
    }

    const firstComponent = components?.firstElementChild;

    if (!firstComponent || !firstComponentCreatesGap(firstComponent)) {
      return;
    }

    document.body.classList.add(GAP_CLASS);
  });
}());
