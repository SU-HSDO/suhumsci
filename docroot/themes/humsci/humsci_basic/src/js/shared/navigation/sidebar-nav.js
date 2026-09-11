/**
 * @file
 * Direction-aware sticky sidebar for the three column layout.
 *
 * Scroll down and the sidebar's bottom edge sticks to the bottom of the
 * screen. Scroll up and its top edge sticks to the top instead. Either way,
 * once you hit the end of the sidebar it lets go and scrolls off with the
 * main content column like normal.
 *
 * Only runs at the 'lg' breakpoint and up, where the layout actually puts
 * the sidebar next to the main content. Below that everything stacks and
 * the sidebar blocks just sit in normal flow.
 *
 * A few things worth knowing before touching this:
 * - We wrap the sidebar's children in a JS-inserted div and move that,
 *   instead of moving the sidebar itself. Whichever edge is pinned gets
 *   real `position: fixed`, not a transform driven off the scroll event.
 *   Fixed positioning is handled entirely by the browser's compositor, so
 *   once it's pinned there's no more JS work and no per-frame lag. A
 *   transform-driven version has to recompute and repaint on every scroll
 *   pixel, so it visibly trails the rest of the (compositor-driven,
 *   zero-lag) page during a fast scroll. We only use a transform while the
 *   sidebar is "parked" between the two pins (or outside the sticky range
 *   entirely), where it doesn't need to track scroll at all and a static
 *   transform costs nothing.
 * - Pulling the wrapper out of flow with `position: fixed` would collapse
 *   the sidebar column, so we reserve its height up front with an explicit
 *   `min-height`.
 * - Where the sidebar sits depends on which way you scrolled to get there,
 *   not just where you are on the page. That's the hysteresis behind
 *   "sticks to the bottom going down, the top going up" -- and it's what
 *   keeps a sidebar taller than the screen fully readable in both
 *   directions. We get it by clamping the current position between a
 *   top-pin and a bottom-pin offset, rather than branching on scroll
 *   direction: whichever pin the position has drifted past wins. That also
 *   makes the very first frame after a reflow come out right (page already
 *   scrolled on load, a mobile-to-desktop resize, a submenu changing the
 *   sidebar's height) -- a direction-based version has nothing to compare
 *   against yet and would render one frame in the wrong spot before
 *   visibly snapping into place on the next scroll.
 * - Each sidebar keeps its own state in a closure, so having more than one
 *   on a page won't cause them to interfere with each other.
 */

(function (Drupal, once) {
  // Matches $su-screen-lg, the width at which the layout switches from
  // stacked columns to sidebar-beside-content.
  const desktopQuery = '(min-width: 992px)';

  const sidebarSelector = '.hb-three-column__sidebar-1';
  const containerSelector = '.hb-three-column';
  const mainSelector = '.hb-three-column__main';

  // Both sit fixed at the top of the viewport, stacked on top of each
  // other, so together they decide how far down the sidebar's top pin
  // needs to sit. We watch them directly instead of trusting
  // Drupal.displace() alone, since the tray can open and close at any time.
  const toolbarBarSelector = '#toolbar-bar';
  const toolbarTraySelector = '.toolbar-tray';

  const wrapperClass = 'hb-sticky-sidebar__inner';
  const activeClass = 'hb-sticky-sidebar--active';
  const pinnedClass = 'hb-sticky-sidebar--pinned';

  // Breathing room between the pinned sidebar and the viewport edges. Top
  // spacing tracks the admin toolbar: it's `position: fixed` at the top of
  // the screen, so pinning at 0 would tuck the sidebar behind it. Toolbar
  // height isn't constant (none logged out, one row, two rows with the
  // tray open), so we read it from `Drupal.displace()` -- the same API
  // core's toolbar module uses to report its own height -- instead of
  // guessing at a fixed number.
  let topSpacing = 0;
  const bottomSpacing = 0;

  function currentToolbarOffset() {
    return typeof Drupal.displace === 'function' ? Drupal.displace().top : 0;
  }

  // Window resizes, a submenu opening, an image loading in -- these can
  // all fire several times in a row, so we wait for things to settle
  // before re-measuring.
  const remeasureDelay = 100;

  function initStickySidebar(sidebar) {
    const container = sidebar.closest(containerSelector);

    if (!container) {
      return;
    }

    // We release the sidebar at the bottom of the main content region, so
    // it scrolls off with the body content -- not at the bottom of the
    // section, which can carry extra padding below it.
    const main = container.querySelector(mainSelector) || container;
    const desktop = window.matchMedia(desktopQuery);

    let wrapper = null;
    let frame = null;
    let remeasureTimer = null;
    let contentObserver = null;
    let toolbarObserver = null;

    // Every position below is in document space, in pixels.
    let naturalTop = 0; // Where the wrapper sits in normal flow.
    let releaseAt = 0; // Document Y the wrapper's bottom may not pass.
    let wrapperHeight = 0;
    let left = 0; // The sidebar column's own offset, for fixed positioning.
    let width = 0;
    let offset = 0; // How far the wrapper currently sits from naturalTop.

    // What's currently applied to the DOM: 'static' (in-flow, positioned
    // with a transform if `offset` is non-zero), 'fixed-top' or
    // 'fixed-bottom'. Starts out `null` so the first update() always
    // applies something, even if it computes 'static' with offset 0.
    let mode = null;

    function measure() {
      // Reset first so we're reading the natural layout, not whatever is
      // currently pinned. That forces a reflow, but only here -- on init,
      // on resize, and when the sidebar or main content changes height --
      // never on scroll.
      wrapper.style.cssText = '';
      sidebar.style.minHeight = '';

      topSpacing = currentToolbarOffset();

      const scrollY = window.pageYOffset;
      const sidebarRect = sidebar.getBoundingClientRect();
      const wrapperRect = wrapper.getBoundingClientRect();
      const mainBottom = main.getBoundingClientRect().bottom + scrollY;

      naturalTop = wrapperRect.top + scrollY;
      wrapperHeight = wrapperRect.height;
      left = sidebarRect.left;
      width = sidebarRect.width;

      // Math.max keeps a sidebar taller than the main content at a travel
      // distance of zero, instead of a negative one.
      releaseAt = Math.max(mainBottom, naturalTop + wrapperHeight);

      // Reserve the column's height so nothing collapses or overlaps once
      // the wrapper gets taken out of flow below.
      sidebar.style.minHeight = `${wrapperHeight}px`;

      // Force the next update() to reapply styles even if it computes the
      // same mode as before -- left/width from a resize, or which edge is
      // glued, might still need to move.
      mode = null;
    }

    function applyStatic(docTop) {
      wrapper.style.position = '';
      wrapper.style.top = '';
      wrapper.style.bottom = '';
      wrapper.style.left = '';
      wrapper.style.width = '';

      const t = Math.round(docTop - naturalTop);
      wrapper.style.transform = t !== 0 ? `translate3d(0, ${t}px, 0)` : '';

      // Still offset from its natural spot -- parked mid-scroll, or
      // released and sitting at the bottom of the main content -- needs
      // the same z-index bump a pinned edge gets, for the same reason.
      sidebar.classList.toggle(pinnedClass, t !== 0);
    }

    function applyFixed(edge) {
      wrapper.style.transform = '';
      wrapper.style.position = 'fixed';
      wrapper.style.top = edge === 'top' ? `${topSpacing}px` : '';
      wrapper.style.bottom = edge === 'bottom' ? `${bottomSpacing}px` : '';
      wrapper.style.left = `${left}px`;
      wrapper.style.width = `${width}px`;
      sidebar.classList.add(pinnedClass);
    }

    function update() {
      frame = null;

      const scrollY = window.pageYOffset;
      const viewportHeight = window.innerHeight;

      // The two offsets that put an edge of the sidebar against an edge of
      // the viewport, plus the furthest it's allowed to travel before it
      // gets released.
      const pinTop = (scrollY + topSpacing) - naturalTop;
      const pinBottom = (scrollY + viewportHeight) - bottomSpacing - wrapperHeight - naturalTop;
      const maxOffset = releaseAt - naturalTop - wrapperHeight;

      let next;
      let nextMode;

      if (wrapperHeight + topSpacing + bottomSpacing <= viewportHeight) {
        // The sidebar fits on screen, so both directions want the same
        // thing: hold the top edge at the top of the viewport and keep the
        // whole thing visible all the way down. Pinning the bottom edge
        // instead would shove a short sidebar to the foot of the screen and
        // leave a gap above it, for no benefit -- it's already fully shown.
        next = pinTop;
        nextMode = 'fixed-top';
      } else if (offset < pinBottom) {
        // The edge we're scrolling toward (down) would otherwise run off
        // screen -- catch it there.
        next = pinBottom;
        nextMode = 'fixed-bottom';
      } else if (offset > pinTop) {
        // Same idea, scrolling up.
        next = pinTop;
        nextMode = 'fixed-top';
      } else {
        // Comfortably between the two pins: leave it parked wherever it
        // already is, scrolling along with the page, until one of the pins
        // above catches up to it. Reversing direction here is exactly what
        // releases a pin and lets you read through a long sidebar either
        // way.
        next = offset;
        nextMode = 'static';
      }

      // Never let it travel above its natural position or past the bottom
      // of the main content, so at both ends of the range it just scrolls
      // away like the main column would. Hitting either bound means it's
      // no longer tracking scroll, so it goes static.
      const clamped = Math.min(Math.max(next, 0), Math.max(maxOffset, 0));

      if (clamped !== next) {
        nextMode = 'static';
      }

      offset = clamped;

      if (nextMode === mode) {
        // Already showing the right thing. While an edge is pinned this is
        // the common case on every scroll event -- the browser's already
        // rendering it correctly via `position: fixed`, so there's nothing
        // left for JS to do.
        return;
      }

      mode = nextMode;

      if (mode === 'fixed-top') {
        applyFixed('top');
      } else if (mode === 'fixed-bottom') {
        applyFixed('bottom');
      } else {
        applyStatic(naturalTop + offset);
      }
    }

    function onScroll() {
      if (frame === null) {
        frame = requestAnimationFrame(update);
      }
    }

    function remeasure() {
      clearTimeout(remeasureTimer);
      remeasureTimer = setTimeout(() => {
        // measure() resets the wrapper to its natural, unpinned position to
        // get a clean reading -- same as enable() does before calling
        // update() directly below. Re-pinning has to happen synchronously,
        // in this same task: if we left it to onScroll()'s next animation
        // frame instead of calling update() here, the browser would paint
        // that reset unpinned state for one real frame -- a visible jump --
        // before snapping back into place on the next one.
        measure();
        update();
      }, remeasureDelay);
    }

    function onResize() {
      if (desktop.matches) {
        remeasure();
      }
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
      update();

      window.addEventListener('scroll', onScroll, { passive: true });

      // Either column can change height without the window ever resizing --
      // a submenu opening in the sidebar, an image loading in, an AJAX view
      // or pager redrawing the main content -- and either one shifts where
      // the sidebar needs to pin.
      if (!contentObserver && 'ResizeObserver' in window) {
        contentObserver = new ResizeObserver(remeasure);
        contentObserver.observe(wrapper);
        contentObserver.observe(main);
      }

      // Same idea, but for the toolbar: the `drupalViewportOffsetChange`
      // listener further down works, but only once core's toolbar JS gets
      // around to firing it. Watching the bar and tray directly means we
      // catch the resize the moment it happens -- tray sliding open,
      // toolbar collapsing to icons -- instead of waiting on that event.
      if (!toolbarObserver && 'ResizeObserver' in window) {
        const toolbarBar = document.querySelector(toolbarBarSelector);
        const toolbarTrays = document.querySelectorAll(toolbarTraySelector);

        if (toolbarBar || toolbarTrays.length) {
          toolbarObserver = new ResizeObserver(remeasure);

          if (toolbarBar) {
            toolbarObserver.observe(toolbarBar);
          }

          toolbarTrays.forEach((tray) => toolbarObserver.observe(tray));
        }
      }
    }

    function disable() {
      window.removeEventListener('scroll', onScroll);
      clearTimeout(remeasureTimer);

      if (frame !== null) {
        cancelAnimationFrame(frame);
        frame = null;
      }

      if (contentObserver) {
        contentObserver.disconnect();
        contentObserver = null;
      }

      if (toolbarObserver) {
        toolbarObserver.disconnect();
        toolbarObserver = null;
      }

      // We leave the wrapper in place: the breakpoint can be crossed either
      // way, and unwrapping it would tear down DOM that other behaviors
      // might already hold references to.
      sidebar.classList.remove(activeClass, pinnedClass);
      sidebar.style.minHeight = '';

      if (wrapper) {
        wrapper.style.cssText = '';
      }

      offset = 0;
      mode = null;
    }

    function setup() {
      if (desktop.matches) {
        enable();
      } else {
        disable();
      }
    }

    setup();

    desktop.addEventListener('change', setup);
    window.addEventListener('resize', onResize, { passive: true });

    // Fires whenever the admin toolbar's height changes -- its tray opening
    // or closing, or the toolbar itself showing up or going away after a
    // login/logout in the same tab.
    window.addEventListener('drupalViewportOffsetChange', onResize, { passive: true });
  }

  Drupal.behaviors.hbStickySidebar = {
    attach(context) {
      once('hb-sticky-sidebar', sidebarSelector, context)
        .filter((sidebar) => {
          // Some Layout Builder markup repeats the sidebar class on a
          // wrapper that also holds the main content column -- sticking
          // that would drag the whole page along with it.
          if (sidebar.querySelector(mainSelector)) {
            return false;
          }

          // Otherwise, if the class is nested, only stick the outermost
          // one that holds just sidebar content.
          const outer = sidebar.parentElement?.closest(sidebarSelector);

          return !outer || !!outer.querySelector(mainSelector);
        })
        .forEach(initStickySidebar);
    },
  };
}(Drupal, once));
