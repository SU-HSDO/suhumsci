((Drupal, once) => {
  Drupal.behaviors.dashboardNav = {
    attach(context) {

      const dashboard = context.querySelector('.dashboard--main-dashboard');
      if (!dashboard) return;

      const stickyRegion = once(
        'dashboard-nav',
        context.querySelector('.region-sticky__items__inner')
      )[0];

      if (!stickyRegion) return;

      const headings = dashboard.querySelectorAll('.block h2');
      if (!headings.length) return;

      const nav = document.createElement('nav');
      nav.className = 'dashboard-menu-panel';
      nav.setAttribute('aria-label', 'Dashboard menu');

      const toggle = document.createElement('button');
      toggle.className = 'dashboard-menu-toggle';
      toggle.setAttribute('aria-expanded', 'false');

      toggle.innerHTML = `
        Dashboard Menu
        <span class="dashboard-menu-panel__toggle-icon"></span>
      `;

      const list = document.createElement('ul');
      list.className = 'dashboard-menu-panel__menu';
      list.setAttribute('aria-hidden', 'true');

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

      nav.appendChild(toggle);
      nav.appendChild(list);

      // Replace the page title
      const pageTitle = stickyRegion.querySelector('.page-title');
      if (pageTitle) {
        pageTitle.replaceWith(nav);
      } else {
        stickyRegion.prepend(nav);
      }

      // Toggle behavior
      toggle.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';

        toggle.setAttribute('aria-expanded', !expanded);
        list.setAttribute('aria-hidden', expanded);
      });

      // Close the menu after clicking a link
      list.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
          toggle.setAttribute('aria-expanded', 'false');
          list.setAttribute('aria-hidden', 'true');
        });
      });
    }
  };
})(Drupal, once);
