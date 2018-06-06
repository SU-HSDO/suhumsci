(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.suHumSciTheme = {
    attach: function (context, settings) {
      $('.search-block-form input[type=search]').focus(function () {
        $(this).closest('form').addClass('expanded');
      }).blur(function () {
        var $this = $(this);
        setTimeout(function () {
          if (!$this.closest('form').find('input[type=submit]').is(':focus')) {
            $this.closest('form').removeClass('expanded');
          }
        }, 200);
      });

      function handleFirstTab(e) {
        if (e.keyCode === 9) { // the "I am a keyboard user" key
          document.body.classList.add('user-is-tabbing');
          window.removeEventListener('keydown', handleFirstTab);
        }
      }

      window.addEventListener('keydown', handleFirstTab);


      $('.decanter-grid .ptype-hs-hero-image', context).not(':first-child').each(function () {
        $(this).addClass('overflow-hero');
      });
      $(window).resize(heroImage);

      $('.overflow-hero').imagesLoaded(heroImage);
      function heroImage() {
        $('.overflow-hero', context).each(function () {
          $(this).css('margin-bottom', $(this).children().height());
        });
      }
    }
  };
})(jQuery, Drupal);
