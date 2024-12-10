/**
 * Add scope attribute to column/row headers on every table
 * This improves table accessibility
 */

(function (Drupal, once) {
  Drupal.behaviors.addTableScopeAttributes = {
    attach(context) {
    /**
     * Set a specific scope attribute value on each element
     * @param elements
     * @param scope
     */
      function setScopeOnElements(elements, scope) {
        for (let i = 0; i < elements.length; i++) {
          elements[i].setAttribute('scope', scope);
        }
      }

      // set scope attribute on column headers
      const columnEls = once('add-table-scope-col', 'thead th', context);
      setScopeOnElements(columnEls, 'col');

      // set scope attribute on row headers
      const rowEls = once('add-table-scope-row', 'tbody th', context);
      setScopeOnElements(rowEls, 'row');
    },
  };
// eslint-disable-next-line no-undef
}(Drupal, once));
