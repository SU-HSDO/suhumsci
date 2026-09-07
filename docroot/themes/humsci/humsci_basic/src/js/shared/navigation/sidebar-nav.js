/**
 * @file
 * Direction-aware sticky sidebar.
 *
 * Behavior:
 * - Scrolling DOWN: the sidebar's bottom edge sticks to the bottom of the
 *   viewport until the bottom of the shared container (sidebar + main
 *   content) is reached, then it scrolls off like the main content column.
 * - Scrolling UP: mirror behavior, sticking to the top of the viewport
 *   until the top of the container, then scrolling off.
 * - Works for sidebars shorter OR taller than the viewport: a sidebar
 *   taller than the screen is revealed gradually in normal flow, then
 *   "catches" (pins) once its top or bottom edge would otherwise run off
 *   screen, so all of its content stays reachable.
 * - Disabled entirely below desktopBreakpoint (mobile/tablet get normal,
 *   static stacking).
 *
 * Implementation notes:
 * - Uses a JS-inserted wrapper around the sidebar's children (per the
 *   ticket's tech notes) instead of making `.hb-three-column__sidebar-1`
 *   itself sticky/fixed. This avoids fighting whatever the ancestor
 *   Layout Builder regions are doing (overflow, transforms, etc.), and
 *   lets us reserve the column's height via `min-height` on the outer
 *   element so nothing collapses or overlaps while the wrapper is
 *   `position: fixed`.
 * - The core positioning math is a single formula (see `loop`) that
 *   naturally reproduces "stick to top while scrolling up / stick to
 *   bottom while scrolling down" WITHOUT needing to track scroll
 *   direction explicitly — it falls out of clamping the natural
 *   (unpositioned) scroll position between a top pin and a bottom pin,
 *   both bounded by the container's own top/bottom edges. Whichever pin
 *   is "in the way" wins, for whichever direction you happen to be
 *   scrolling.
 * - Each sidebar found on the page gets its own private state via a
 *   closure (no shared `this`), so multiple sidebars on one page won't
 *   step on each other.
 */

(function (Drupal, once) {
  const desktopBreakpoint = '(min-width: 992px)';

  const sidebarSelector = '.hb-three-column__sidebar-1';
  const containerSelector = '.hb-three-column';
  const wrapperClass = 'hb-sticky-sidebar__inner';
  const activeClass = 'hb-sticky-sidebar--active';
  const pinnedClass = 'hb-sticky-sidebar--pinned';

  // Optional breathing room between the pinned sidebar and the viewport
  // edge (e.g. so it doesn't sit flush against the very top/bottom of the
  // screen). Set to 0 to disable.
  const topSpacing = 0;
  const bottomSpacing = 0;

  function initStickySidebar(sidebar) {
    const container = sidebar.closest(containerSelector) || sidebar.parentElement;
    const mq = window.matchMedia(desktopBreakpoint);

    let wrapper = null;
    let metrics = null;
    let rafId = null;
    let resizeTimer = null;
    let contentResizeObserver = null;

    function measure() {
      // Reset first so measurements reflect natural, unpositioned layout.
      wrapper.style.cssText = '';

      const sidebarRect = sidebar.getBoundingClientRect();
      const containerRect = container.getBoundingClientRect();
      const scrollY = window.pageYOffset;

      metrics = {
        containerTop: containerRect.top + scrollY,
        containerBottom: containerRect.bottom + scrollY,
        sidebarHeight: wrapper.offsetHeight,
        left: sidebarRect.left,
        width: sidebarRect.width,
      };

      // Reserve the column's height so the grid doesn't collapse once the
      // wrapper is taken out of flow (position: fixed).
      sidebar.style.minHeight = `${metrics.sidebarHeight}px`;
    }

    function loop() {
      rafId = null;
      if (!metrics) {
        return;
      }

      const { containerTop, containerBottom, sidebarHeight, left, width } = metrics;
      const scrollY = window.pageYOffset;
      const viewportHeight = window.innerHeight;

      // Where the sidebar's top would sit (viewport-relative) if it were
      // just sitting untouched at the top of the container.
      const naturalTop = containerTop - scrollY;

      if (naturalTop >= 0) {
        // Container hasn't reached the top of the screen yet: normal flow.
        sidebar.classList.remove(pinnedClass);
        wrapper.style.cssText = '';
        return;
      }

      const pinTop = topSpacing;
      const pinBottom = viewportHeight - sidebarHeight - bottomSpacing;

      let top = naturalTop;
      top = Math.min(top, pinTop);
      top = Math.max(top, pinBottom);

      // Don't let the sidebar's bottom edge pass the bottom of the shared
      // container — this is what makes it release and scroll off screen
      // at the end, same as the main content column.
      const maxTopFromContainer = containerBottom - scrollY - sidebarHeight;
      top = Math.min(top, maxTopFromContainer);

      sidebar.classList.add(pinnedClass);
      wrapper.style.position = 'fixed';
      wrapper.style.top = `${top}px`;
      wrapper.style.left = `${left}px`;
      wrapper.style.width = `${width}px`;
    }

    function onScroll() {
      if (rafId) {
        return;
      }
      rafId = requestAnimationFrame(loop);
    }

    // Shared by window resize and sidebar-content resize: re-measure after
    // a short debounce (a toggled submenu or a resize drag can trigger
    // several rapid layout changes in a row).
    function remeasureDebounced() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        measure();
        onScroll();
      }, 150);
    }

    function onResize() {
      if (!mq.matches) {
        return;
      }
      remeasureDebounced();
    }

    function enable() {
      if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.className = wrapperClass;
        while (sidebar.firstChild) {
          wrapper.appendChild(sidebar.firstChild);
        }
        sidebar.appendChild(wrapper);
      }

      sidebar.classList.add(activeClass);
      measure();
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();

      // Content inside the sidebar can change height without a window
      // resize firing (submenu toggles like `.hb-secondary-toggler`,
      // images loading in a block, etc.). Watch for that and re-measure
      // so the pinned position doesn't get stale or clip content.
      if (!contentResizeObserver && 'ResizeObserver' in window) {
        contentResizeObserver = new ResizeObserver(remeasureDebounced);
        contentResizeObserver.observe(wrapper);
      }
    }

    function disable() {
      window.removeEventListener('scroll', onScroll);
      if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = null;
      }
      if (contentResizeObserver) {
        contentResizeObserver.disconnect();
        contentResizeObserver = null;
      }
      sidebar.classList.remove(activeClass, pinnedClass);
      sidebar.style.minHeight = '';
      if (wrapper) {
        wrapper.style.cssText = '';
      }
      metrics = null;
    }

    function setup() {
      if (mq.matches) {
        enable();
      } else {
        disable();
      }
    }

    setup();

    if (mq.addEventListener) {
      mq.addEventListener('change', setup);
    }
    window.addEventListener('resize', onResize, { passive: true });
  }

  Drupal.behaviors.hbStickySidebar = {
    attach(context) {
      const sidebars = once('hb-sticky-sidebar', sidebarSelector, context);

      // Guard against the nested-duplicate-class case: only ever
      // initialize the outermost element matching sidebarSelector.
      sidebars
        .filter((el) => !el.parentElement || !el.parentElement.closest(sidebarSelector))
        .forEach((sidebar) => {
          initStickySidebar(sidebar);
        });
    },
  };
}(Drupal, once));
