(function (Drupal, once, jQuery) {
  Drupal.behaviors.preventScroll = {
    attach: function (context) {
      once('preventScroll', 'input[name="links_add_more"]', context).forEach((input) => {
        jQuery(input).on('click', function () {

          // Wait for the AJAX update to complete - due to block_class module?
          setTimeout(() => {
            const modal = jQuery('#drupal-modal');
            const table = modal.find('table[id^="links-values"]');
            const lastRow = table.find('tr:last');

            if (modal.length && lastRow.length) {
              const lastRowOffsetTop = lastRow[0].offsetTop - modal[0].offsetTop;
              modal.scrollTop(lastRowOffsetTop);
            }
          }, 400);
        });
      });
    },
  };
})(Drupal, once, jQuery);
