// Detect animation frame on scroll
let scroll = window.requestAnimationFrame || function(callback) {
  window.setTimeout(callback, 1000/60)
};

// The classes of items we want to add animations to
const classesToAnimate = [
  '.hb-hero-overlay',
  '.hb-hero-overlay__text',
  '.hb-hero-overlay__image-wrapper',
  '.field-hs-hero-image',
  '.hb-gradient-hero',
  '.hb-gradient-hero__text',
  '.hb-gradient-hero__image-wrapper',
  '.field-hs-gradient-hero-image',
  '.hs-font-splash'
];

let showAnimation = document.querySelectorAll(classesToAnimate);

// Check to see if the animation enhancement theme toggle has been
// activiated. If so, then add the `animate` class when an item
//  displays in the viewport.
let animationEnhancements = document.querySelectorAll('.hb-has-animation-enhancements');

if (animationEnhancements) {
  loop();
}

function loop() {
  for (var i = 0; i < showAnimation.length; i+=1) {
    if (isElementInViewport(showAnimation[i])) {
      showAnimation[i].classList.add('animate');
    }

    // Stop looping through animations once all items have been exposed in the viewport.
    if ((showAnimation.length == (i + 1)) && (showAnimation[i].classList.contains('animate'))) {
      scroll = window.cancelAnimationFrame;
    }
  }
  
  scroll(loop);
}

// Confirm when an item is in the viewport
function isElementInViewport(e) {
  let rect = e.getBoundingClientRect();

  // Set the point at which the hero animation begins
  // to be when the bottom of the browser window intersects
  // slightly above the bottom of the hero.
  let bottom = (rect.bottom - (rect.bottom*0.18));

  return (
    (rect.top >= 0 &&
      bottom <= ((window.innerHeight) || document.documentElement.clientHeight)) 
  );
}
