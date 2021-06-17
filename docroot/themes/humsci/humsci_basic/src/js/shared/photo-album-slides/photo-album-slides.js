const photoAlbumSlider = document.querySelectorAll('.slick--su-gallery-images--hs-gallery-slideshow');

if (photoAlbumSlider.length > 0) {
  console.log('This page has photo album slides!');

  // console.log(photoAlbumSlider);

  // find each slider
  photoAlbumSlider.forEach(slider => {
    console.log(slider);
  });
}


const slickSlides = document.querySelectorAll('.slick__slide');

console.log(slickSlides);
console.log(slickSlides.length);
console.log(slickSlides.length - 1); // correct number of slides

// find the active slide
slickSlides.forEach(slide => {
  console.log(slide);
  console.log(slide.classList);
  // console.log(slide.classList.value); // slick_slide slide slide--0
  // console.log(slide.attributes); // NamedNodeMap
  // console.log(slide.classList.contains('slide-active')); // false!

  console.log(slide.getAttributeNames()); // only returns "class"
})
