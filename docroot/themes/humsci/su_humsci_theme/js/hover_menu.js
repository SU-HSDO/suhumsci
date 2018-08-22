(function($, Drupal) {
  'use strict';
  Drupal.behaviors.HoverMenu = {
    attach: function(context, settings) {
      var $header = $('#header');

      function setMenu() {
        var $menu = $header.find('ul.decanter-nav-primary');

        // Desktop, apply the jquery ui menu and change any mobile classes.
        if (window.innerWidth >= 1201) {
          $menu.menu();
          $menu.removeClass('expanded');
          $menu.find('.fa-minus').addClass('fa-plus').removeClass('fa-minus');
          $menu.find('.expanded').removeClass('expanded');
          // Remove attribute only on top level to pass AMP test.
          $menu.children('li').children('a').removeAttr('aria-expanded');
          $menu.removeAttr('role');
        } else {
          // Check if jquery ui has been applied yet.
          if ($menu.hasClass('ui-menu')) {
            $menu.menu('destroy');

            // Removes any `display:block;` inline styles.
            $menu.parent().find('ul').attr('style', function(i, style) {
              return style && style.replace(/display[^;]+;?/g, '');
            });
          }
        }
      }

      // Check main menu items to ensure dropdown submenus don't get orphaned or cut off.
      function menuEdgeCheck() {
        var $viewportWidth = $('body').innerWidth();
        $('ul.decanter-nav-primary > li', context).each(function() {
          var $itemFromLeft = $(this).offset().left;
          var $itemFromRight = $viewportWidth - $itemFromLeft;
          var $subMenuWidth = $('> ul', this).outerWidth();
          if ($subMenuWidth > $itemFromRight) {
            $(this).addClass('edge');
          }
          else {
            $(this).removeClass('edge');
          }
        });
      }

      setMenu();
      menuEdgeCheck();

      // Open/close the menu from hamburger button.
      $header.find('button.fa-bars').once().click(function() {
        menuExpander(this);
      });

      // Open/close submenus from the plus button.
      $header.find('button.fa-plus').once().click(function() {
        menuExpander(this);
        $(this).toggleClass('fa-plus').toggleClass('fa-minus');
      });

      function menuExpander(theMenu) {
        $(theMenu).siblings('ul').toggleClass('expanded').find('.fa-minus').each(function() {
          $(theMenu).toggleClass('fa-plus').toggleClass('fa-minus');
          $(theMenu).siblings('ul').toggleClass('expanded');
        });
      }

      // run on window resize
      window.addEventListener('resize',
        Drupal.debounce(function() {
          setMenu();
          menuEdgeCheck();
        }, 125)
      );

    }
  };
})(jQuery, Drupal);
