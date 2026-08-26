/**
 * Restores the "only scroll upward" guard removed from
 * Drupal.AjaxCommands.scrollTop() in #3478087.
 *
 * Not a Drupal.behaviors — this patches an AJAX command prototype once at
 * load, rather than processing DOM elements on each attach. Hence the flag
 * instead of once().
 *
 * @todo Remove when https://www.drupal.org/project/drupal/issues/3564586 lands.
 */
((Drupal) => {
  const original = Drupal.AjaxCommands.prototype.scrollTop;

  // Guard against double-wrapping if this file is evaluated more than once.
  if (typeof original !== 'function' || original.viewsScrollUpwardOnly) {
    return;
  }

  const patched = function (ajax, response, status) {
    const target = document.querySelector(response.selector);
    if (!target) {
      return;
    }
    const top = target.getBoundingClientRect().top + window.scrollY;
    if (top - 10 < window.scrollY) {
      original.call(this, ajax, response, status);
    }
  };
  patched.viewsScrollUpwardOnly = true;

  Drupal.AjaxCommands.prototype.scrollTop = patched;
})(Drupal);
