(function($, Drupal) {
  'use strict';
  Drupal.behaviors.HoverMenu = {
    attach: function(context, settings) {
      var $header = $('#header');

      function setMenu() {
        var $menu = $header.find('ul.decanter-nav-primary');

        // Desktop, apply the jquery ui menu and change any mobile classes.
        if ($(window).width() >= 1201) {
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
        var viewportWidth = document.body.clientWidth;
        jQuery('ul.decanter-nav-primary > li').each(function() {
          var itemFromLeft = jQuery(this).offset().left;
          var itemFromRight = viewportWidth - itemFromLeft;
          var subMenuWidth = jQuery('> ul', this).outerWidth();
          if (subMenuWidth > itemFromRight) {
            jQuery(this).addClass( 'edge' );
          }
          else {
            jQuery(this).removeClass( 'edge' );
          }
        });
      }

      setMenu();
      menuEdgeCheck();

      var onResizeActivity = debounce(function() {
        setMenu();
        menuEdgeCheck();
      }, 125);

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

      // debounce so that we don't run our resize functions constantly
      function debounce(func, wait, immediate) {
        var timeout;
        return function() {
          var context = this, args = arguments;
          var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
          };
          var callNow = immediate && !timeout;
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
          if (callNow) func.apply(context, args);
        };
      } // end debounce function

      // run on window resize
      window.addEventListener('resize', onResizeActivity);

    }
  };
})(jQuery, Drupal);
