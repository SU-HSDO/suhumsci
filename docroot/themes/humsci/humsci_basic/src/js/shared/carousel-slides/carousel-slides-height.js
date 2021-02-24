const slides = document.querySelectorAll('.paragraph--type--hs-carousel');
let timeOutFunctionId; // a numeric ID which is used by clearTimeOut to reset the timer

if (slides.length > 0) {
  restrictHeight();

  // Watch for when the browser window resizes, then run the restrictHeight
  // function to reset the height of the text boxes
  window.addEventListener('resize', function() {
    clearTimeout(timeOutFunctionId);
    timeOutFunctionId = setTimeout(restrictHeight, 250);
  });
}

// Set the height of all text boxes within a Carousel to that
// of the tallest text box
function restrictHeight() {
  for (let i = 0; i < slides.length; i++) {
    let textBoxes;
    let boxHeightArray = [0]; // array must have a default entry of 0 for the banner components

    // Find all the textBoxes inside each carousel
    textBoxes = slides[i].getElementsByClassName('hb-hero-overlay__text');

    // Clear any inline styles that may have been set previously
    // This is necessary to determine the default height of text boxes
    for (let n = 0; n < textBoxes.length; n++) {
      textBoxes[n].removeAttribute('style');
    }

    // Loop through all the textBoxes and gather their heights into an array
    for (let j = 0; j < textBoxes.length; j++) {
      let boxHeight = textBoxes[j].offsetHeight;

      // Parse boxHeight to be a number that can be used to set the min-height value
      boxHeight = parseInt(boxHeight, 10);

      // Create an array containing all the heights of textBoxes
      boxHeightArray.push(boxHeight);
    }

    // Find largest number in array of textBoxes
    let maxBoxHeight = Math.max(...boxHeightArray);

    // Give all textBoxes the same height on medium and larger sized screens
    if (window.innerWidth > 768) {
      for (let k = 0; k < textBoxes.length; k++) {
        textBoxes[k].setAttribute('style', `min-height: ${maxBoxHeight}px`);
      }
    }
  }
}
