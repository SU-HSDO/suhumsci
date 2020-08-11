import './tables/wrap';
import './tables/scope';
import './tables/table-pattern';
import './navigation/index';
import './equal-height-grid/index';
import './media/video-with-caption';
import Masonry from 'masonry-layout';

var elem = document.querySelectorAll('.hb-masonry');
if (elem) {
  for (let i = 0; i < elem.length; i += 1) {
    new Masonry( elem[i], {
      columnWidth: '.hb-card',
      gutter: 5,
      itemSelector: '.hb-card',
      percentPosition: true
    });
  }
}

// elem.imagesLoaded( function() {
// });