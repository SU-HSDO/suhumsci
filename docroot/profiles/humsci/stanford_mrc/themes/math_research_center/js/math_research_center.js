(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.mathResearchCenter = {
    attach: function (context, settings) {
      $('#block-searchform input[type=search]').focus(function () {
        $(this).closest('form').addClass('expanded');
      }).blur(function () {
        var $this = $(this);
        setTimeout(function () {
          if (!$this.closest('form').find('input[type=submit]').is(':focus')) {
            $this.closest('form').removeClass('expanded');
          }
        }, 50);
      });

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
