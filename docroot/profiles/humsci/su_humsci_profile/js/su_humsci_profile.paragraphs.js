
(function ($, Drupal, once) {
  Drupal.behaviors.hsProfileParagraphs = {
    attach: function (context) {
      const duplicatedItem = $(once('duplicate-scrolled', '.hs-duplicated', context));
      if (duplicatedItem.length > 0) {
        $([document.documentElement, document.body]).animate({
          scrollTop: duplicatedItem.offset().top,
        }, 500);
      }
    }
  };
})(jQuery, Drupal, once);
