(function (Drupal, once) {
  Drupal.behaviors.attachStanfordColorbox = {
    attach(context) {
      const colorboxElement = once('colorbox', '#colorbox', context);
      if (colorboxElement) {
        const previousButton = once('cboxPrevious', '#cboxPrevious', context);
        const nextButton = once('cboxNext', '#cboxNext', context);
        const slideshowButton = context.getElementById('cboxSlideshow');
        if (previousButton) previousButton.textContent = '« Prev';
        if (nextButton) nextButton.textContent = 'Next »';
        if (slideshowButton) slideshowButton.textContent = 'Slideshow';
      }
    },
  };
// eslint-disable-next-line no-undef
}(Drupal, once));
