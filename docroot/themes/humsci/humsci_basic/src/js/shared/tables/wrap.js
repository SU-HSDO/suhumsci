(function (Drupal, once) {
  Drupal.behaviors.wrapTableElements = {
    attach(context) {
      /**
      * Wrap every table in a class that will allow us to create more responsive styling
      */

      /**
      * Wrap each element in a new parent
      * @param elements
      * @param wrapper
      */
      function wrapElement(element) {
        // Create a new div with a special class name
        const wrapper = context.createElement('div');
        wrapper.className = 'hb-table-wrap';

        element.parentNode.insertBefore(wrapper, element);
        wrapper.appendChild(element);
      }

      // Select every table element
      const elements = once('wrap-table', 'table', context);
      const uiPatternTable = once('wrap-ui-pattern-table', '.hb-table-pattern', context);

      // Wrap every table element
      for (let i = 0; i < elements.length; i++) {
        wrapElement(elements[i]);
      }

      // Wrap every table UI pattern
      for (let i = 0; i < uiPatternTable.length; i++) {
        wrapElement(uiPatternTable[i]);
      }
    },
  };
// eslint-disable-next-line no-undef
}(Drupal, once));
