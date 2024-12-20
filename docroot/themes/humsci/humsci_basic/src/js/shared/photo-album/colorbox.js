(function (Drupal, once) {
  Drupal.behaviors.attachStanfordColorbox = {
    attach(context) {
      const [colorboxElement] = once('colorbox', '#colorbox', context);
      if (colorboxElement) {
        const previousButton = colorboxElement.querySelector('#cboxPrevious');
        const nextButton = colorboxElement.querySelector('#cboxNext');
        const slideshowButton = colorboxElement('#cboxSlideshow');
        if (previousButton) previousButton.textContent = '« Prev';
        if (nextButton) nextButton.textContent = 'Next »';
        if (slideshowButton) slideshowButton.textContent = 'Slideshow';
      }
    },
  };
}(Drupal, once));
