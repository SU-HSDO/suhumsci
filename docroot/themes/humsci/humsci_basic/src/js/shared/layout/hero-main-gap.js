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

  /**
   * Removes the first Layout Builder block on the page if its node title
   * renders empty (e.g. the front page's suppressed "Home" title), and
   * reports whether it instead holds a visible title.
   *
   * CSS :empty can't reliably catch the empty case because Twig leaves
   * whitespace text nodes between the tags (which can still create a
   * phantom line box even with margin/height zeroed out), so remove the
   * block outright instead of just hiding it with CSS.
   *
   * Only the first block is checked: it's the only one that can hold the
   * node title and sit above the page components, so it's the only one
   * relevant to the hero gap. A block without a .node__title is left
   * untouched either way.
   *
   * @return {boolean} Whether the first block holds a visible node title.
   */
  function removeEmptyTitleBlock() {
    const block = document.querySelector('.block-layout-builder');

    if (!block) {
      return false;
    }

    const title = block.querySelector('.node__title');

    if (!title) {
      return false;
    }

    if (title.childElementCount > 0) {
      return true;
    }

    block.remove();

    return false;
  }

  // Add body classes for full-width / main-region spacing gap
  // detection. Only applies to pages that use the full-width hero +
  // main region layout (see humsci_basic_preprocess_html()).
  document.addEventListener('DOMContentLoaded', () => {
    const hero = document.querySelector('.field-hs-page-hero');

    // If the node's Title field block is shown above the page components,
    // it already breaks the margin adjacency between the hero and the
    // first component, so the gap doesn't need closing. Checking this
    // first (most pages render the title) avoids the more expensive
    // firstComponentCreatesGap() work below.
    const hasVisibleTitle = removeEmptyTitleBlock();

    if (!hero || hasVisibleTitle) {
      return;
    }

    const components = document.querySelector('.field-hs-page-components');
    const firstComponent = components?.firstElementChild;

    if (!firstComponent || !firstComponentCreatesGap(firstComponent)) {
      return;
    }

    document.body.classList.add(GAP_CLASS);
  });
}());
