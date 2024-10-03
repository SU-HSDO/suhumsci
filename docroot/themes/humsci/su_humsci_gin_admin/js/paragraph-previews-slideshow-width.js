((Drupal) => {
  const slideshowsSelector =
    '.ptype-hs-gradient-hero-slider, .ptype-hs-carousel, .ptype-stanford-gallery, .ptype-hs-sptlght-slder';

  const resizeObserver = new ResizeObserver((entries) => {
    const offset = {
      'edit-field-hs-page-hero-wrapper': 128,
      'edit-field-hs-page-components-wrapper': 90,
      'edit-field-hs-priv-page-components-wrapper': 90,
    };
    for (const entry of entries) {
      const containerWidth = entry.contentRect.width;
      const id = entry.target.id;
      const slideshows = entry.target.querySelectorAll(slideshowsSelector);

      for (const slideshow of slideshows) {
        slideshow.style.width = containerWidth - offset[id] + 'px';
      }
    }
  });

  Drupal.behaviors.paragraphPreviewsSlideshowWidth = {
    attach(context) {
      const fields = context.querySelectorAll(
        '#edit-field-hs-page-hero-wrapper:not([data-preview-slideshow-width]), #edit-field-hs-page-components-wrapper:not([data-preview-slideshow-width]), #edit-field-hs-priv-page-components-wrapper:not([data-preview-slideshow-width])',
      );
      if (!fields.length) {
        return;
      }

      for (const field of fields) {
        const slideshows = field.querySelectorAll(slideshowsSelector);
        if (slideshows.length) {
          field.setAttribute('data-preview-slideshow-width', '');
          resizeObserver.observe(field);
        }
      }
    },
  };
})(Drupal);
