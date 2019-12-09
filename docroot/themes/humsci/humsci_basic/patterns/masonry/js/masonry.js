(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.suHumsciMasonry = {
    attach: function (context, settings) {
      $('.masonry', context).each(function () {
        var $container = $(this);

        // For infinite scroll views, the masonry wrapper is duplicated. So
        // in order to get them all in the same container, we have to detach
        // and appaend them to the first container.
        if ($container.closest('.views-infinite-scroll-content-wrapper').length) {
          var first = null;
          $container.closest('.views-infinite-scroll-content-wrapper').find('.masonry').each(function () {
            // Its the first grouping of view items.
            if (!first) {
              first = this;
              return;
            }

            // Add all following items to the first group.
            var newItems = $(this).children().detach();
            $(this).detach();
            $(first).append(newItems).masonry('appended', newItems);
          });
        }

        // Set the masonry when images are loaded.
        $container.imagesLoaded(function () {
          $container.masonry({
            columnWidth: '.masonry-sizer--item',
            gutter: '.masonry-sizer--gap',
            itemSelector: '.masonry-item',
            percentPosition: true
          });

          // Listen for lazy loaded images and reset the layout of the masonry.
          $container.find('img').on('lazy-image-loaded', function () {
            $container.masonry('layout');
          });
        });
      });
    }
  };
})(jQuery, Drupal);
