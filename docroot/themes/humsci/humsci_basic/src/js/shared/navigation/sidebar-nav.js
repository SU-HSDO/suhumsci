/**
 * @file
 * Direction-aware sticky sidebar for the three column layout.
 *
 * Scrolling down, the sidebar's bottom edge holds at the bottom of the
 * screen; scrolling up, its top edge holds at the top of the screen. At
 * either end of the range it is released and scrolls away with the main
 * content column.
 *
 * The behavior only runs from 'lg' up, where the layout actually puts the
 * sidebar beside the main content. Below that the columns stack and every
 * block in the sidebar is left in normal flow.
 *
 * Implementation notes:
 * - The sidebar's children are wrapped in a JS-inserted element, and that
 *   wrapper is what moves. Whichever edge is actively being held against
 *   the viewport is done with real
 *   `position: fixed`, not a scroll-driven `transform`: fixed positioning
 *   is tracked by the browser's compositor, so once an edge is pinned it
 *   costs no further JS and has no per-frame lag behind the scroll. A
 *   `transform`-driven version has to recompute and repaint on every
 *   scroll pixel while gluing an edge, trailing the (compositor-driven,
 *   zero-lag) rest of the page by however long the scroll → rAF → style
 *   round trip takes -- visible as the sidebar swimming relative to the
 *   page during a fast scroll. `transform` is used only while "parked"
 *   (between the two pins, or before/after the sticky range), where the
 *   position does not need to track scroll at all and a constant
 *   `transform` costs nothing further either.
 * - Because `position: fixed` removes the wrapper from normal flow, the
 *   sidebar column reserves its height with an explicit `min-height` so
 *   nothing collapses or overlaps while an edge is pinned.
 * - The travel distance is deliberately path-dependent: where the sidebar
 *   sits depends on which way you came, not just on the scroll position.
 *   That hysteresis is what "sticks the bottom going down, the top going
 *   up" means, and it is what keeps a sidebar taller than the screen fully
 *   readable in both directions. It is implemented as a pure clamp of the
 *   current position into the band between the top-pin and bottom-pin
 *   offsets, rather than by branching on scroll direction: whichever pin
 *   the current position has fallen outside of "wins". That makes it
 *   correct on the very first frame after (re)computing where things are
 *   (page load already scrolled past the sidebar, a mobile-to-desktop
 *   resize, a submenu changing the sidebar's height, ...), where a
 *   direction-based version has nothing to compare against yet and would
 *   otherwise render one frame in the wrong place and visibly snap into
 *   position on the next scroll event.
 * - Each sidebar keeps its own state in a closure, so more than one on a
 *   page cannot interfere.
 */

(function (Drupal, once) {
  // Matches $su-screen-lg, the width at which the layout switches from
  // stacked columns to sidebar-beside-content.
  const desktopQuery = '(min-width: 992px)';

  const sidebarSelector = '.hb-three-column__sidebar-1';
  const containerSelector = '.hb-three-column';
  const mainSelector = '.hb-three-column__main';

  const wrapperClass = 'hb-sticky-sidebar__inner';
  const activeClass = 'hb-sticky-sidebar--active';
  const pinnedClass = 'hb-sticky-sidebar--pinned';

  // Breathing room between the pinned sidebar and the viewport edges.
  // Top spacing tracks the Drupal admin toolbar, if one is displayed: the
  // toolbar is `position: fixed` at the top of the viewport, so pinning the
  // sidebar's top edge at 0 would tuck it behind the toolbar. The toolbar's
  // own height varies (none when logged out, one row, or two rows while its
  // tray is open), so this is read from `Drupal.displace()` -- the same API
  // core's toolbar module uses to report its own current height -- rather
  // than a fixed guess.
  let topSpacing = 0;
  const bottomSpacing = 0;

  function currentToolbarOffset() {
    return typeof Drupal.displace === 'function' ? Drupal.displace().top : 0;
  }

  // A window resize drag, a submenu opening and an image loading can each
  // fire several times in a row, so re-measuring waits for them to settle.
  const remeasureDelay = 100;

  function initStickySidebar(sidebar) {
    const container = sidebar.closest(containerSelector);

    if (!container) {
      return;
    }

    // The sidebar is released at the bottom of the main content region, so
    // it scrolls off with the body content rather than at the bottom of the
    // section, which can carry padding below it.
    const main = container.querySelector(mainSelector) || container;
    const desktop = window.matchMedia(desktopQuery);

    let wrapper = null;
    let frame = null;
    let remeasureTimer = null;
    let contentObserver = null;

    // Every position below is in document space, in pixels.
    let naturalTop = 0; // Where the wrapper sits in normal flow.
    let releaseAt = 0; // Document Y the wrapper's bottom may not pass.
    let wrapperHeight = 0;
    let left = 0; // The sidebar column's own offset, for fixed positioning.
    let width = 0;
    let offset = 0; // How far the wrapper currently sits from naturalTop.

    // What is currently applied to the DOM: 'static' (in-flow, positioned
    // with a transform if `offset` is non-zero), 'fixed-top' or
    // 'fixed-bottom'. Starts `null` so the first update() always applies,
    // even if the computed mode happens to be 'static' with offset 0.
    let mode = null;

    function measure() {
      // Reset first so the read reflects natural layout rather than
      // whatever is currently pinned. This forces a reflow, but only here
      // -- on init, on resize, and when the sidebar or main content
      // changes height -- never on scroll.
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

      // Math.max keeps a sidebar that is taller than the main content at a
      // travel distance of zero rather than a negative one.
      releaseAt = Math.max(mainBottom, naturalTop + wrapperHeight);

      // Reserve the column's height so nothing collapses or overlaps while
      // the wrapper is taken out of flow below.
      sidebar.style.minHeight = `${wrapperHeight}px`;

      // Force the next update() to reapply styles even if the mode it
      // computes is unchanged -- left/width (from a resize) or which
      // pixel edge is glued may need to move regardless.
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

      // Still offset from its natural spot (parked mid-scroll, or released
      // and left sitting at the bottom of the main content) needs the same
      // z-index bump a pinned edge does, for the same reason.
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
      // the viewport, and the furthest it may travel before being released.
      const pinTop = (scrollY + topSpacing) - naturalTop;
      const pinBottom = (scrollY + viewportHeight) - bottomSpacing - wrapperHeight - naturalTop;
      const maxOffset = releaseAt - naturalTop - wrapperHeight;

      let next;
      let nextMode;

      if (wrapperHeight + topSpacing + bottomSpacing <= viewportHeight) {
        // The sidebar fits on screen, so both directions want the same
        // thing: hold its top edge at the top of the viewport, which keeps
        // all of it visible the whole way down. Pinning the bottom edge
        // instead would shove a short sidebar to the foot of the screen and
        // open a gap above it, and there is nothing to read into by
        // scrolling since it is already fully shown.
        next = pinTop;
        nextMode = 'fixed-top';
      } else if (offset < pinBottom) {
        // The edge being scrolled toward (down) would otherwise leave the
        // screen: catch it there.
        next = pinBottom;
        nextMode = 'fixed-bottom';
      } else if (offset > pinTop) {
        // Mirror, scrolling up.
        next = pinTop;
        nextMode = 'fixed-top';
      } else {
        // Comfortably between the two pins: leave it parked wherever it
        // currently sits, scrolling with the page, until one of the above
        // catches up to it. Reversing direction here is what releases a
        // pin and lets the reader work through a long sidebar either way.
        next = offset;
        nextMode = 'static';
      }

      // Never travel above the natural position or past the bottom of the
      // main content, so at both ends of the range the sidebar simply
      // scrolls away like the main column. Hitting either of these bounds
      // means the sidebar isn't tracking scroll any more, so it's static.
      const clamped = Math.min(Math.max(next, 0), Math.max(maxOffset, 0));

      if (clamped !== next) {
        nextMode = 'static';
      }

      offset = clamped;

      if (nextMode === mode) {
        // Already showing the right thing. While an edge is pinned this is
        // the common case on every scroll event: the browser is already
        // rendering it correctly via `position: fixed`, with nothing
        // further for JS to do.
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
        // get a clean reading, same as enable() does before its own direct
        // update() call below. Re-pinning has to happen synchronously, in
        // this same task: deferring it to onScroll()'s next animation frame
        // (as opposed to calling update() here directly) would let the
        // browser paint that reset, unpinned state for one real frame,
        // visible as a small jump, before the next frame snapped it back.
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

      // Either column can change height without the window resizing, and
      // both move where the sidebar has to pin: a submenu toggling open or
      // an image loading in the sidebar, an AJAX view or a pager redrawing
      // the main content.
      if (!contentObserver && 'ResizeObserver' in window) {
        contentObserver = new ResizeObserver(remeasure);
        contentObserver.observe(wrapper);
        contentObserver.observe(main);
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

      // The wrapper is left in place: the breakpoint can be crossed either
      // way, and unwrapping would tear down DOM that other behaviors may
      // already hold references into.
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

    // Fires when the admin toolbar's height changes -- its tray opening or
    // closing, or it appearing/disappearing on login/logout in the same tab.
    window.addEventListener('drupalViewportOffsetChange', onResize, { passive: true });
  }

  Drupal.behaviors.hbStickySidebar = {
    attach(context) {
      once('hb-sticky-sidebar', sidebarSelector, context)
        .filter((sidebar) => {
          // Some Layout Builder markup repeats the sidebar class on a
          // wrapper that also holds the main content column. Sticking that
          // would take the whole page with it.
          if (sidebar.querySelector(mainSelector)) {
            return false;
          }

          // Otherwise, when the class is nested, only the outermost one that
          // holds just sidebar content is stuck.
          const outer = sidebar.parentElement?.closest(sidebarSelector);

          return !outer || !!outer.querySelector(mainSelector);
        })
        .forEach(initStickySidebar);
    },
  };
}(Drupal, once));
