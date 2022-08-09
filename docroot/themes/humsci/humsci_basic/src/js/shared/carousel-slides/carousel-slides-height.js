// This work below applies uniform height to both the Hero Layered Slider (formerly Carousel),
// the Hero Gradient Slider paragraph component slides.
// and the Spotlight Slider.
const slides = document.querySelectorAll('.paragraph--type--hs-carousel, .paragraph--type--hs-gradient-hero-slider, .paragraph--type--hs-sptlght-slder');
const slidesTextboxClasses = '.hb-hero-overlay__text, .hb-gradient-hero__text, .hb-spotlight__text';
// const mediumScreenBreakpoint = 768;
let timeOutFunctionId; // a numeric ID which is used by clearTimeOut to reset the timer

// @boolean to determine if the textBox is a spotlight textBox
const isSpotlightTextBox = (textBox) => textBox.classList.contains('hb-spotlight__text');
const setMinHeight = (textBox, maxBoxHeight) => textBox.setAttribute('style', `min-height: ${maxBoxHeight}px`);

// Set the height of all text boxes within a slider to that
// of the tallest text box
const restrictHeight = () => {
  let boxHeightArray; let
    maxBoxHeight;

  slides.forEach((slide) => {
    // array must have a default entry of 0 for the banner components
    // and must be declare within the loop to set a baseline for each indiviual slider on a page
    boxHeightArray = [0];

    // Find all the textBoxes inside each slider
    const textBoxes = slide.querySelectorAll(slidesTextboxClasses);

    // Loop through all the textBoxes and gather their heights into an array
    textBoxes.forEach((textBox) => {
      // Clear any inline styles that may have been set previously
      // This is necessary to determine the default height of text boxes
      textBox.removeAttribute('style');

      let boxHeight = textBox.offsetHeight;

      // Parse boxHeight to be a number that can be used to set the min-height value
      boxHeight = parseInt(boxHeight, 10);

      // Create an array containing all the heights of textBoxes
      boxHeightArray.push(boxHeight);
    });

    // Find largest number in array of textBoxes
    maxBoxHeight = Math.max(...boxHeightArray);

    // Give all textBoxes the same height
    textBoxes.forEach((textBox) => setMinHeight(textBox, maxBoxHeight));

    // If the textBoxes are spotlight textBoxes, then give them the same height on all screen sizes
    textBoxes.forEach(
      (textBox) => isSpotlightTextBox(textBox) && setMinHeight(textBox, maxBoxHeight),
    );
  });
};

const clearTimeoutOnResize = () => {
  // Watch for when the browser window resizes, then run the restrictHeight
  // function to reset the height of the text boxes
  window.addEventListener('resize', () => {
    clearTimeout(timeOutFunctionId);
    timeOutFunctionId = setTimeout(restrictHeight, 250);
  });
};

if (slides.length > 0) {
  restrictHeight();
  clearTimeoutOnResize();
}
