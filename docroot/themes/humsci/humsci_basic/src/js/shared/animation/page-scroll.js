let allAnimated = false;

const windowHeight = () => (window.innerHeight || document.documentElement.clientHeight);

// check if top of element is in viewport
const isElementInViewport = (e) => {
  const rect = e.getBoundingClientRect();

  // Set the point at which the hero animation begins
  // to be when the bottom of the browser window intersects
  // slightly above the bottom of the hero.
  const bottom = (rect.bottom - (rect.bottom*0.18));

  return (rect.top >= 0) && (bottom <= (windowHeight()));
}

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
  '.hs-font-splash',
  '.hs-font-lead'
];

const showAnimation = document.querySelectorAll(classesToAnimate);

// Check to see if the animation enhancement theme toggle has been
// activiated. If so, then add the `animate` class when an item
//  displays in the viewport.
const animationEnhancements = document.querySelectorAll('.hb-has-animation-enhancements');

const cancelLoop = () => document.removeEventListener('scroll', loop);
const containsAnimateClass = (e) => e.classList.contains('animate');

const checkIfAllElementsAreAnimated = () => {
  for (let i = 0; i < showAnimation.length; i++) {
    if (containsAnimateClass(showAnimation[i])) {
      allAnimated = true
    } else {
      allAnimated = false;
      break;
    }
  }
}

const loop = () => {
  for (let i of showAnimation) {
    if (isElementInViewport(i)) {
      i.classList.add('animate');
    }

    checkIfAllElementsAreAnimated();

    if (allAnimated) {
      cancelLoop();
    }
  }

  scroll(loop);
}

if (animationEnhancements.length) {
  // This ensures that elements animate if they are in the viewport on pageload
  loop();

  // run the loop on page scroll
  document.addEventListener('scroll', loop);
}
