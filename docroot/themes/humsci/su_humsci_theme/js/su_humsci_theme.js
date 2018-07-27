(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.suHumSciTheme = {
    attach: function (context, settings) {
      $('#search-region .search-block-form input[type=search], #search-region .views-exposed-filter-block.hs-search-search-page input[type=text]', context).focus(function () {
        $(this).closest('form').addClass('expanded');
      }).blur(function () {
        var $this = $(this);
        setTimeout(function () {
          if (!$this.closest('form').find('input[type=submit]').is(':focus')) {
            $this.closest('form').removeClass('expanded');
          }
        }, 200);
      });

      /**
       * On the first tab, apply a class to the body.
       *
       * @param e
       *   The keydown event.
       */
      function handleFirstTab(e) {
        if (e.keyCode === 9) { // the "I am a keyboard user" key
          document.body.classList.add('user-is-tabbing');
          window.removeEventListener('keydown', handleFirstTab);
        }
      }

      window.addEventListener('keydown', handleFirstTab);
    }
  };
})(jQuery, Drupal);
