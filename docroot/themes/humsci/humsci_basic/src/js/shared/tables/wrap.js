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
        const wrapper = document.createElement('div');
        wrapper.className = 'hb-table-wrap';

        element.parentNode.insertBefore(wrapper, element);
        wrapper.appendChild(element);
      }

      // Select every table and table pattern element
      const elements = once('table-wrap', 'table, .hb-table-pattern', context);

      // Wrap every element
      for (let i = 0; i < elements.length; i++) {
        wrapElement(elements[i]);
      }
    },
  };
}(Drupal, once));
