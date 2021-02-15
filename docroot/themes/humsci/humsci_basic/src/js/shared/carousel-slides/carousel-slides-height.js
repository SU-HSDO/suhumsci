let slides = document.querySelectorAll('.paragraph--type--hs-carousel'); // UGH! needs to be more specific

if (slides.length > 0) {
  restrictHeight();
}

function restrictHeight() {
  console.log('yo slides!');
  console.log(slides);

  // Find the height of each text area to find the tallest area and then update all containers to match that height.
}
