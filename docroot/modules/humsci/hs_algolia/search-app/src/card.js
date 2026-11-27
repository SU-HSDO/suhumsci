import escapeHtml from './escape';

const first = (value) => (Array.isArray(value) ? value[0] : value);

/**
 * Only allow web links, so a stored javascript: or internal: URI never
 * becomes a link.
 */
const webUrl = (value) => {
  // An empty value would otherwise resolve to the current page.
  if (!value) {
    return null;
  }
  try {
    const url = new URL(value, window.location.href);
    return ['http:', 'https:'].includes(url.protocol) ? url : null;
  } catch (e) {
    return null;
  }
};

/**
 * Format an indexed date the way the H&S event cards show it.
 *
 * Search API sends dates to Algolia as Unix timestamps.
 */
const formatDate = (timestamp) => {
  const seconds = Number(first(timestamp));
  if (!seconds) {
    return '';
  }
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'America/Los_Angeles',
  }).format(new Date(seconds * 1000));
};

/**
 * The record's description, or a snippet of its text when it has none.
 *
 * Basic pages have no summary. InstantSearch escapes snippets and marks the
 * matched words with <mark>, so the result is safe HTML. The Drupal search
 * excerpt uses <strong>, so match that.
 */
const description = (hit) => {
  const summary = first(hit.summary);
  if (summary) {
    return escapeHtml(summary);
  }
  // Algolia names its response metadata with a leading underscore.
  // eslint-disable-next-line no-underscore-dangle
  const snippet = hit._snippetResult?.html?.value;
  return snippet ? snippet.replace(/<(\/?)mark>/g, '<$1strong>') : '';
};

/**
 * Link to a result, carrying the keywords along.
 *
 * Accordions and timelines on the target page read ?search= to expand the
 * sections that match, as they do for Drupal search results.
 *
 * @see humsci_basic/src/js/shared/search/search-expand.js
 */
const resultUrl = (url, query, queryParam) => {
  const target = webUrl(url);
  if (!target) {
    return '';
  }
  target.searchParams.set(queryParam, query);
  return target.toString();
};

/**
 * Render one result as the horizontal card pattern.
 *
 * Mirrors hs_layouts/patterns/horizontal-card/horizontal-card.html.twig and
 * the field-to-slot mapping of the Drupal search view, so the theme styles
 * both the same way.
 *
 * Results have no image. The Drupal search view shows images for few content
 * types, and those are rarely set, so the image column mostly sat empty.
 *
 * @param {object} hit
 *   An Algolia record from the hs_algolia index.
 * @param {string} query
 *   The current keywords.
 * @param {string} queryParam
 *   The URL parameter that carries the keywords.
 *
 * @return {string}
 *   The card HTML.
 */
export default (hit, query, queryParam) => {
  const title = escapeHtml(first(hit.title));
  const href = escapeHtml(resultUrl(first(hit.url), query, queryParam));
  const subcontent = [
    formatDate(hit.event_start),
    first(hit.course_days),
    first(hit.person_email),
    first(hit.event_location),
    first(hit.course_start_time),
    first(hit.person_office),
  ].filter(Boolean);
  const mapLink = webUrl(first(hit.event_map_link));
  const speaker = first(hit.person_title);
  const body = description(hit);

  return `
    <div class="hb-card hb-card--horizontal hb-card--horizontal--left">
      <div class="hb-card__wrapper">
        <div class="hb-card__content">
          ${hit.type ? `<div class="hb-card__category"><div class="hb-single-category">${escapeHtml(first(hit.type))}</div></div>` : ''}
          <div class="hb-card__title"><h2>${href ? `<a href="${href}">${title}</a>` : title}</h2></div>
          ${subcontent.length ? `
          <div class="hb-card__subcontent">
            ${subcontent.map((item) => `<div class="hb-card__subcontent-item">${escapeHtml(item)}</div>`).join('')}
          </div>` : ''}
          ${speaker || mapLink ? `
          <div class="hb-card__highlighted">
            ${speaker ? escapeHtml(speaker) : ''}
            ${mapLink ? `<a href="${escapeHtml(mapLink)}">${escapeHtml(Drupal.t('Map'))}</a>` : ''}
          </div>` : ''}
          ${body ? `<div class="hb-card__description">${body}</div>` : ''}
        </div>
      </div>
    </div>`;
};
