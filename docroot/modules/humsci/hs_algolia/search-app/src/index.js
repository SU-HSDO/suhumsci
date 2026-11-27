/* global drupalSettings */
import { liteClient } from 'algoliasearch/lite';
import instantsearch from 'instantsearch.js/es';
import {
  connectHits,
  connectPagination,
  connectSearchBox,
} from 'instantsearch.js/es/connectors';
import { configure } from 'instantsearch.js/es/widgets';
import { history } from 'instantsearch.js/es/lib/routers';
import renderCard from './card';
import escapeHtml from './escape';

/**
 * Wrap the Algolia client so an empty query sends no request.
 *
 * The Drupal search view requires keywords before it shows anything, so an
 * empty search page shows only the form here too, and costs no Algolia
 * operation.
 */
const conditionalClient = (client) => ({
  ...client,
  search(requests) {
    if (requests.every(({ params }) => !params.query)) {
      return Promise.resolve({
        results: requests.map(() => ({
          hits: [],
          nbHits: 0,
          nbPages: 0,
          page: 0,
          hitsPerPage: 0,
          processingTimeMS: 0,
          exhaustiveNbHits: true,
          query: '',
          params: '',
        })),
      });
    }
    return client.search(requests);
  },
});

/**
 * Keep the Drupal search URL format: ?search=KEYWORDS&page=N.
 *
 * The page number is zero based, like Drupal's pager, so a URL copied from
 * either search lands on the same page.
 */
const routing = (indexName, queryParam) => ({
  router: history({ cleanUrlOnDispose: false }),
  stateMapping: {
    stateToRoute(uiState) {
      const state = uiState[indexName] || {};
      return {
        [queryParam]: state.query || undefined,
        page: state.page > 1 ? state.page - 1 : undefined,
      };
    },
    routeToState(routeState) {
      const page = parseInt(routeState.page, 10);
      return {
        [indexName]: {
          query: routeState[queryParam] || '',
          page: Number.isNaN(page) ? 1 : page + 1,
        },
      };
    },
  },
});

/**
 * Search form with the markup of the Drupal search view's exposed form.
 */
const searchBox = (container, settings) => connectSearchBox(({ query, refine }, isFirstRender) => {
  if (isFirstRender) {
    const id = `hs-algolia-search-input-${Math.random().toString(36).slice(2)}`;
    container.innerHTML = `
        <form class="views-exposed-form hs-algolia-search__form" action="" method="get" role="search">
          <div class="form--inline clearfix">
            <div class="js-form-item form-item js-form-type-search-api-fulltext form-item-search js-form-item-search">
              <label for="${id}">${escapeHtml(settings.strings.label)}</label>
              <input type="text" id="${id}" name="${escapeHtml(settings.queryParam)}" size="30" maxlength="128" class="form-text" autocomplete="off">
            </div>
            <div class="form-actions js-form-wrapper form-wrapper">
              <input type="submit" value="${escapeHtml(settings.strings.submit)}" class="button js-form-submit form-submit">
            </div>
          </div>
        </form>`;
    container.querySelector('form').addEventListener('submit', (event) => {
      event.preventDefault();
      refine(container.querySelector('input[type="text"]').value.trim());
    });
  }

  const input = container.querySelector('input[type="text"]');
  if (document.activeElement !== input) {
    input.value = query;
  }
});

/**
 * Result list built from horizontal cards, like the Drupal search rows.
 */
const hits = (container, status, settings) => connectHits(({ items, results }) => {
  if (!results || !results.query) {
    container.innerHTML = '';
    status.textContent = '';
    return;
  }

  if (!items.length) {
    container.innerHTML = `<div class="view-empty"><p>${escapeHtml(settings.strings.empty)}</p></div>`;
    status.textContent = settings.strings.empty;
    return;
  }

  container.innerHTML = `<div class="view-content">${items
    .map((hit) => `<div class="views-row">${renderCard(hit, results.query, settings.queryParam)}</div>`)
    .join('')}</div>`;
  status.textContent = Drupal.formatPlural(
    results.nbHits,
    '1 result',
    '@count results',
  );
});

/**
 * Pager with the markup of Drupal's full pager, so the theme styles it.
 */
const pagination = (container, settings, onPageChange) => connectPagination(
  ({
    pages, currentRefinement, nbPages, isFirstPage, isLastPage, refine, createURL,
  }) => {
    if (nbPages <= 1) {
      container.innerHTML = '';
      return;
    }

    const { strings } = settings;
    const link = (page, text, label, rel = '') => `
        <a href="${escapeHtml(createURL(page))}" data-page="${page}" title="${escapeHtml(label)}"${rel ? ` rel="${rel}"` : ''}>
          <span class="visually-hidden">${escapeHtml(label)}</span>
          <span aria-hidden="true">${text}</span>
        </a>`;

    const items = [];
    if (!isFirstPage) {
      items.push(`<li class="pager__item pager__item--first">${link(0, '« First', strings.first)}</li>`);
      items.push(`<li class="pager__item pager__item--previous">${link(currentRefinement - 1, '‹ Previous', strings.previous, 'prev')}</li>`);
    }
    pages.forEach((page) => {
      if (page === currentRefinement) {
        items.push(`<li class="pager__item is-active">
            <a href="${escapeHtml(createURL(page))}" data-page="${page}" title="${escapeHtml(strings.currentPage)}" aria-current="page">
              <span class="visually-hidden">${escapeHtml(strings.currentPage)}</span>${page + 1}
            </a>
          </li>`);
      } else {
        items.push(`<li class="pager__item">
            <a href="${escapeHtml(createURL(page))}" data-page="${page}" title="${escapeHtml(`${strings.page} ${page + 1}`)}">
              <span class="visually-hidden">${escapeHtml(strings.page)}</span>${page + 1}
            </a>
          </li>`);
      }
    });
    if (!isLastPage) {
      items.push(`<li class="pager__item pager__item--next">${link(currentRefinement + 1, 'Next ›', strings.next, 'next')}</li>`);
      items.push(`<li class="pager__item pager__item--last">${link(nbPages - 1, 'Last »', strings.last)}</li>`);
    }

    container.innerHTML = `
        <nav class="pager" aria-label="${escapeHtml(strings.pagerLabel)}">
          <h4 class="visually-hidden">${escapeHtml(strings.pagerLabel)}</h4>
          <ul class="pager__items js-pager__items">${items.join('')}</ul>
        </nav>`;

    container.querySelectorAll('a[data-page]').forEach((anchor) => {
      anchor.addEventListener('click', (event) => {
        event.preventDefault();
        refine(parseInt(anchor.dataset.page, 10));
        onPageChange();
      });
    });
  },
);

((Drupal, drupalSettings, once) => {
  Drupal.behaviors.hsAlgoliaSearch = {
    attach(context) {
      once('hs-algolia-search', '.hs-algolia-search__app', context).forEach((element) => {
        const settings = drupalSettings.hsAlgoliaSearch;
        if (!settings) {
          return;
        }

        element.innerHTML = `
          <div class="hs-algolia-search__searchbox"></div>
          <div class="visually-hidden" role="status" aria-live="polite"></div>
          <div class="hs-algolia-search__results" tabindex="-1"></div>
          <div class="hs-algolia-search__pager"></div>`;
        const results = element.querySelector('.hs-algolia-search__results');
        const status = element.querySelector('[role="status"]');

        // Move focus to the new results so keyboard and screen reader users
        // are not left on a pager link that no longer exists.
        const onPageChange = () => {
          results.focus({ preventScroll: true });
          element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        const search = instantsearch({
          indexName: settings.indexName,
          searchClient: conditionalClient(liteClient(settings.appId, settings.searchKey)),
          routing: routing(settings.indexName, settings.queryParam),
        });

        search.on('error', () => {
          results.innerHTML = `<div class="view-empty"><p>${escapeHtml(settings.strings.error)}</p></div>`;
        });

        search.addWidgets([
          configure({
            hitsPerPage: settings.hitsPerPage,
            attributesToSnippet: ['html:40'],
            snippetEllipsisText: '…',
            attributesToHighlight: [],
            attributesToRetrieve: [
              'title',
              'url',
              'type',
              'summary',
              'event_start',
              'event_location',
              'event_map_link',
              'course_days',
              'course_start_time',
              'person_title',
              'person_email',
              'person_office',
            ],
          }),
          searchBox(element.querySelector('.hs-algolia-search__searchbox'), settings)({}),
          hits(results, status, settings)({}),
          pagination(element.querySelector('.hs-algolia-search__pager'), settings, onPageChange)({
            padding: 4,
          }),
        ]);

        search.start();
      });
    },
  };
})(Drupal, drupalSettings, once);
