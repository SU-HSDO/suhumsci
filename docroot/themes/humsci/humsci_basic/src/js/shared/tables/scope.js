/**
 * Add scope attribute to column/row headers on every table
 * This improves table accessibility
 */

(function (Drupal) {
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
      const columnEls = context.querySelectorAll('thead th');
      setScopeOnElements(columnEls, 'col');

      // set scope attribute on row headers
      const rowEls = context.querySelectorAll('tbody th');
      setScopeOnElements(rowEls, 'row');
    },
  };
}(Drupal));
