// This work below applies uniform height to both the Hero Layered Slider (formerly Carousel) and 
// the Hero Gradient Slider paragraph component slides.
const slides = document.querySelectorAll('.paragraph--type--hs-carousel, .paragraph--type--hs-gradient-hero-slider');
let mediumScreenBreakpoint = 768;
let timeOutFunctionId; // a numeric ID which is used by clearTimeOut to reset the timer

// Set the height of all text boxes within a slider to that
// of the tallest text box
function restrictHeight() {
  let boxHeightArray, maxBoxHeight;

  slides.forEach(slide => {
    let textBoxes;
    boxHeightArray = [0]; // array must have a default entry of 0 for the banner components and must be declare within the loop to set a baseline for each indiviual slider on a page

    // Find all the textBoxes inside each slider
    textBoxes = slide.querySelectorAll('.hb-hero-overlay__text, .hb-gradient-hero__text');

    // Loop through all the textBoxes and gather their heights into an array
    textBoxes.forEach(textBox => {
      // Clear any inline styles that may have been set previously
      // This is necessary to determine the default height of text boxes
      textBox.removeAttribute('style');

      let boxHeight = textBox.offsetHeight;

      // Parse boxHeight to be a number that can be used to set the min-height value
      boxHeight = parseInt(boxHeight);

      // Create an array containing all the heights of textBoxes
      boxHeightArray.push(boxHeight);
    });

    // Find largest number in array of textBoxes
    maxBoxHeight = Math.max(...boxHeightArray);

    // Give all textBoxes the same height on medium and larger sized screens
    if (window.innerWidth > mediumScreenBreakpoint) {
      textBoxes.forEach(textBox => {
        textBox.setAttribute('style', `min-height: ${maxBoxHeight}px`);
      });
    }
  });
}

if (slides.length > 0) {
  if (window.innerWidth > mediumScreenBreakpoint) {
    restrictHeight();
  }

  // Watch for when the browser window resizes, then run the restrictHeight
  // function to reset the height of the text boxes
  window.addEventListener('resize', function() {
    if (window.innerWidth > mediumScreenBreakpoint) {
      clearTimeout(timeOutFunctionId);
      timeOutFunctionId = setTimeout(restrictHeight, 250);
    }
  });
}
