// (function (Drupal, once) {
//   Drupal.behaviors.tagifyAutocomplete = {
//     attach(context) {
//       console.log('HERE', context);
//       // once('tagify-autocomplete', '.tagify input', context)
//       //   .forEach((input) => {

//       //     const tagify = new Tagify(input, {
//       //       whitelist: [],
//       //       enforceWhitelist: false,
//       //       dropdown: {
//       //         enabled: 1,
//       //         closeOnSelect: false,
//       //       },
//       //       userInput: false
//       //     });

//       //     const autocompleteUrl = input.dataset.autocompletePath;

//       //     tagify.on('input', async (e) => {
//       //       const value = e.detail.value;
//       //       if (!value || value.length < 2) return;

//       //       const response = await fetch(`${autocompleteUrl}?q=${value}`);
//       //       const data = await response.json();

//       //       tagify.settings.whitelist = data.map(item => ({
//       //         value: item.label,
//       //         id: item.value,
//       //       }));

//       //       tagify.dropdown.show(value);
//       //     });

//       //   });
//     }
//   };
// })(Drupal, once);

(function ($, Drupal, once) {
  Drupal.behaviors.tagifySelect = {
    attach: function (context) {
      console.log('HERE', context);
    }
  };
})(jQuery, Drupal, once);

