/**
 * Here we append all search result links with the original
 * search query. We do this so that if a user searches for
 * content containing accordions or timelines, we can expand
 * that content so it is not hidden from the user.
 * See:
 * accordion/accordion-toggle-all.js and
 * timeline/expand-collapse-timeline.js
 * for search expand functionality.
*/
Drupal.behaviors.searchParam = {
  attach(context) {
    const searchLinkContainer = context.querySelector('.views-element-container');

    if (searchLinkContainer) {
      const searchQuery = location.search.slice(1);
      const searchRows = searchLinkContainer.querySelectorAll('.views-row');

      searchRows.forEach((row) => {
        const linkContainer = row.querySelector('.hb-card__title');
        const link = linkContainer.getElementsByTagName('a')[0];
        const newHref = `${link.href}?${searchQuery}`;

        link.setAttribute('href', newHref);
      });
    }
  },
};
