((Drupal, once) => {
  Drupal.behaviors.dashboardNav = {
    attach(context) {

      const dashboard = once(
        'dashboard',
        context.querySelector('.dashboard--main-dashboard')
      )[0];

      if (!dashboard) return;

      function getOffset() {
        const selectors = [
          '#toolbar-bar',
          '.gin-secondary-toolbar',
          '.region-sticky'
        ];

        return selectors.reduce((total, selector) => {
          const el = document.querySelector(selector);
          if (!el) return total;

          const style = window.getComputedStyle(el);

          // Only count elements that are fixed or sticky
          if (style.position === 'fixed' || style.position === 'sticky') {
            return total + el.offsetHeight;
          }

          return total;
        }, 5); // 5px visual buffer
      };

      const headings = dashboard.querySelectorAll('.block h2');
      if (!headings.length) return;

      const nav = context.querySelector('.dashboard-menu-panel');
      const toggle = nav.querySelector('.dashboard-menu-toggle');
      const list = nav.querySelector('.dashboard-menu-panel__menu');

      headings.forEach((heading, index) => {
        if (!heading.id) {
          heading.id = `dashboard-section-${index + 1}`;
        }

        const li = document.createElement('li');
        li.className = 'dashboard-menu-panel__item';

        const link = document.createElement('a');
        link.className = 'dashboard-menu-panel__link';
        link.href = `#${heading.id}`;
        link.textContent = heading.textContent;

        li.appendChild(link);
        list.appendChild(li);

      });

      // Toggle behavior
      toggle.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';

        toggle.setAttribute('aria-expanded', !expanded);
        list.setAttribute('aria-hidden', expanded);
      });

      // Close the menu after clicking a link and scroll to the right position.
      list.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();

          const id = link.getAttribute('href').replace('#', '');
          const target = document.getElementById(id);
          if (!target) return;

          const offset = getOffset();

          const y = target.getBoundingClientRect().top + window.pageYOffset - offset;

          window.scrollTo({
            top: y,
            behavior: 'smooth'
          });

          toggle.setAttribute('aria-expanded', 'false');
          list.setAttribute('aria-hidden', 'true');
        });
      });
    }
  };
})(Drupal, once);
