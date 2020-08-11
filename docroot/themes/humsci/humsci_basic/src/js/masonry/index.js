import Masonry from 'masonry-layout';
import ImagesLoaded from 'imagesloaded';

// TODO resizing is ugly

// init Masonry
const masonry_grid = document.querySelectorAll('.hb-masonry');

// Sizer element
const masonry_sizer = document.createElement('div');
masonry_sizer.classList.add('hb-masonry__sizer');

// Gutter element
const masonry_gutter = document.createElement('div');
masonry_gutter.classList.add('hb-masonry__gutter');

if (masonry_grid && masonry_grid.length > 0) {
  console.log('Test that this doesn\'t run on pages without masonry');

  let msnry;

  ImagesLoaded(masonry_grid, function() {
    for (let i = 0; i < masonry_grid.length; i += 1) {
      // Add a gutter element wrapped inside each masonry grid for the settings
      masonry_grid[i].prepend(masonry_gutter);
      masonry_grid[i].prepend(masonry_sizer);

      msnry = new Masonry(masonry_grid[i], {
          columnWidth: '.hb-masonry__sizer',
          gutter: '.hb-masonry__gutter',
          itemSelector: '.hb-card',
          percentPosition: true
        }
      );

      // Lay out Masonry after each image loads
      msnry.layout();
    }
  });
}