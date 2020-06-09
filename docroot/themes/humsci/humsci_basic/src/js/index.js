import './tables/wrap';
import './tables/scope';
import './tables/table-pattern';
import './navigation/index';
import './equal-height-grid/index';


function resizeIFrameToFitContent( iFrame ) {
  console.log(iFrame);
    iFrame.width  = iFrame.contentWindow.document.body.scrollWidth;
    iFrame.height = iFrame.contentWindow.document.body.scrollHeight;
}

window.addEventListener('DOMContentLoaded', function(e) {

  var iFrame = document.querySelector( '.google-form' );
  resizeIFrameToFitContent( iFrame );

  // or, to resize all iframes:
  var iframes = document.querySelectorAll("iframe");
  for( var i = 0; i < iframes.length; i++) {
      resizeIFrameToFitContent( iframes[i] );
  }
} );
