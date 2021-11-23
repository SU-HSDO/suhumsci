let allAnimated = false;

// Detect animation frame on scroll
const scroll = window.requestAnimationFrame || function (callback) {
  window.setTimeout(callback, 1000 / 60);
};

const windowHeight = () => (window.innerHeight || document.documentElement.clientHeight);
const experimentalFeaturesClass = [...document.querySelectorAll('.hb-experimental')];
const experimentalClassesToAnimate = ['.hs-font-lead'];

// check if top of element is in viewport
const isElementInViewport = (e) => {
  const rect = e.getBoundingClientRect();

  // Set the point at which the hero animation begins
  // to be when the bottom of the browser window intersects
  // slightly above the bottom of the hero.
  const bottom = (rect.bottom - (rect.bottom * 0.18));

  return (rect.top >= 0) && (bottom <= (windowHeight()));
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
  '.hs-font-splash',
];

if (experimentalFeaturesClass.length) {
  classesToAnimate.push(experimentalClassesToAnimate);
}

const showAnimation = document.querySelectorAll(classesToAnimate);

// Check to see if the animation enhancement theme toggle has been
// activiated. If so, then add the `animate` class when an item
// displays in the viewport.
const animationEnhancements = document.querySelectorAll('.hb-has-animation-enhancements');

const cancelLoop = () => window.cancelAnimationFrame;
const containsAnimateClass = (e) => e.classList.contains('animate');

const checkIfAllElementsAreAnimated = () => {
  for (let i = 0; i < showAnimation.length; i++) {
    if (containsAnimateClass(showAnimation[i])) {
      allAnimated = true;
    } else {
      allAnimated = false;
      break;
    }
  }
};

const loop = () => {
  showAnimation.forEach((el) => {
    if (isElementInViewport(el)) {
      el.classList.add('animate');
    }

    checkIfAllElementsAreAnimated();

    if (allAnimated) {
      cancelLoop();
    }
  });

  scroll(loop);
};

if (animationEnhancements.length) {
  // This ensures that elements animate if they are in the viewport on pageload
  loop();
}

