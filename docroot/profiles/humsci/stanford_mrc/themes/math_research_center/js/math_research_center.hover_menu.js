(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.mrcHoverMenu = {
    attach: function (context, settings) {
      var $header = $('#header');

      function setMenu() {
        var $menu = $header.find('ul.decanter-nav-primary');

        // Desktop, apply the jquery ui menu and change any mobile classes.
        if ($(window).width() >= 600) {
          $menu.menu();
          $menu.removeClass('expanded');
          $menu.find('.fa-minus').addClass('fa-plus').removeClass('fa-minus');
          $menu.find('.expanded').removeClass('expanded');
        }
        else {
          // Check if jquery ui has been applied yet.
          if ($menu.hasClass('ui-menu')) {
            $menu.menu('destroy');

            // Removes any `display:block;` inline styles.
            $menu.parent().find('ul').attr('style', function (i, style) {
              return style && style.replace(/display[^;]+;?/g, '');
            });
          }
        }
      }

      setMenu();
      $(window).resize(setMenu);

      // Open/close the menu from hamburger button.
      $header.find('button.fa-bars').once().click(function () {
        $(this).siblings('ul').toggleClass('expanded').find('.fa-minus').each(function () {
          $(this).toggleClass('fa-plus').toggleClass('fa-minus');
          $(this).siblings('ul').toggleClass('expanded');
        });
      });

      // Open/close submenus from the plus button.
      $header.find('button.fa-plus').once().click(function () {
        $(this).siblings('ul').toggleClass('expanded').find('.fa-minus').each(function () {
          $(this).toggleClass('fa-plus').toggleClass('fa-minus');
          $(this).siblings('ul').toggleClass('expanded');
        });
        $(this).toggleClass('fa-plus').toggleClass('fa-minus');
      });
    }
  };
})(jQuery, Drupal);
