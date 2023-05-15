const animationEnhancements = document.querySelector('.hb-has-animation-enhancements');
const experimentalFeaturesClass = document.querySelector('.hb-experimental');
const experimentalClassesToAnimate = [document.querySelectorAll('.hs-font-lead')];

// The classes of items we want to add animations to
const classesToAnimate = [
  document.querySelectorAll('.hb-gradient-hero'),
  document.querySelectorAll('.hb-gradient-hero__text'),
  document.querySelectorAll('.hb-gradient-hero__image-wrapper'),
  document.querySelectorAll('.field-hs-gradient-hero-image'),
  document.querySelectorAll('.hb-hero-overlay'),
  document.querySelectorAll('.hb-hero-overlay__text'),
  document.querySelectorAll('.hb-hero-overlay__image-wrapper'),
  document.querySelectorAll('.field-hs-hero-image'),
  document.querySelectorAll('.hs-font-splash'),
];

if (experimentalFeaturesClass) {
  classesToAnimate.push(experimentalClassesToAnimate);
}

// check if top of element is in viewport
const isElementVisible = new IntersectionObserver((items) => {
  items.forEach((item) => {
    if (item.intersectionRatio > 0) {
      item.target.classList.add('animate');
    }
  });
});

if (animationEnhancements) {
  classesToAnimate.forEach((items) => {
    if (items) {
      items.forEach((item) => {
        isElementVisible.observe(item);
      });
    }
  });
}
