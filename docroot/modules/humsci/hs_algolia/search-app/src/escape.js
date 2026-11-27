const entities = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
};

/**
 * Escape a value for use in HTML text or a quoted attribute.
 *
 * @param {*} value
 *   Any value. Null and undefined become an empty string.
 *
 * @return {string}
 *   The escaped string.
 */
export default (value) => String(value ?? '').replace(/[&<>"']/g, (character) => entities[character]);
