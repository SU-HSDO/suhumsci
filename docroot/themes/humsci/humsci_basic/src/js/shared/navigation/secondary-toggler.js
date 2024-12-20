import changeNav from './change-nav';
import togglerHandler from './toggler-handler';

(function (Drupal, once) {
  Drupal.behaviors.secondaryToggleNavigation = {
    attach(context) {
      const togglers = once('secondary-toggler', '.hb-secondary-toggler', context);

      if (togglers) {
        for (let i = 0; i < togglers.length; i += 1) {
          const toggler = togglers[i];
          const togglerID = toggler.getAttribute('id');
          const togglerContent = document.querySelector('[aria-labelledby="'.concat(togglerID, '"]'));
          const togglerParent = toggler.parentNode;
          const activeTrail = togglerParent.classList.contains('hb-secondary-nav__item--active-trail');

          // Togglers should always have content but in the event that they don't we
          // don't want the rest of the togglers on the page to break.
          if (!togglerContent) {
            continue;
          }

          if (!activeTrail) {
            changeNav(toggler, togglerContent, false);
          }

          toggler.addEventListener('click', (e) => togglerHandler(e, toggler, togglerContent));
        }
      }
    },
  };
}(Drupal, once));
