const attachStanfordColorbox = (context) => {
  const colorboxElement = context.getElementById('colorbox');
  if (colorboxElement) {
    const previousButton = context.getElementById('cboxPrevious');
    const nextButton = context.getElementById('cboxNext');
    const slideshowButton = context.getElementById('cboxSlideshow');
    previousButton.textContent = '« Prev';
    nextButton.textContent = 'Next »';
    slideshowButton.textContent = 'Slideshow';
  }
};

document.addEventListener('DOMContentLoaded', () => {
  attachStanfordColorbox(document);
});
