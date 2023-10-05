const attachStanfordColorbox = (context) => {
  const colorboxLinks = 'a.colorbox';
  const galleryDialogSpan = document.createElement('span');
  galleryDialogSpan.className = 'sr-only';
  galleryDialogSpan.textContent = 'Opens gallery dialog';
  document.querySelector(colorboxLinks).appendChild(galleryDialogSpan);

  const colorboxElement = context.getElementById('colorbox');
  if (colorboxElement) {
    colorboxElement.setAttribute('aria-label', 'Image gallery');
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
