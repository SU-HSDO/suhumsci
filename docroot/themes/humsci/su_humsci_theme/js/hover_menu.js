(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.HoverMenu = {
    attach: function (context, settings) {
      var $header = $('#header', context);

      this.setMenu(context);
      this.menuEdgeCheck(context);

      // Open/close the menu from hamburger button.
      $('button.fa-bars', $header).click(function () {
        menuExpander(this);
      });

      // Open/close submenus from the plus button.
      $('button.fa-plus', $header).click(function (e) {
        // Collapase all menu items outside of the one that was clicked.
        // This prevents overlapping submenus.
        $(this).parent().siblings().find('.fa-minus').click();
        $(this).siblings('ul').find('.fa-minus').click();
        menuExpander(this);
        $(this).toggleClass('fa-plus').toggleClass('fa-minus');
      });

      function menuExpander(theMenu) {
        $(theMenu).siblings('ul').toggleClass('expanded').find('.fa-minus').each(function () {
          $(theMenu).toggleClass('fa-plus').toggleClass('fa-minus');
          $(theMenu).siblings('ul').toggleClass('expanded');
        });
      }

      // run on window resize
      window.addEventListener('resize',
        Drupal.debounce(function () {
          Drupal.behaviors.HoverMenu.setMenu();
          Drupal.behaviors.HoverMenu.menuEdgeCheck(context);
        }, 125)
      );

    },

    /**
     * Set up the jQuery UI Menu functionality.
     *
     * @param context
     *   Context of request.
     */
    setMenu: function (context) {
      var $header = $('#header', context);
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
    },

    /**
     * Check main menu items to ensure dropdown submenus don't get orphaned or
     * cut off.
     *
     * @param context
     *   Context of request.
     */
    menuEdgeCheck: function (context) {
      var $viewportWidth = $('body').innerWidth();
      $('ul.decanter-nav-primary > li', context).each(function () {
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

  };
})(jQuery, Drupal);
