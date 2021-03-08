// This function accepts a list of elements that you would like to be the same height.
// Ideally these types of elements are laid out in a grid that you'd like the height to be the same.
const equalHeightGrid = (elements) => {
  if (elements.length > 0) {
    // Create array with all of the heights of each element
    const elementHeights = Array.prototype.map.call(elements, (el) => {
      return el.scrollHeight;
    });

    // Create array with _unique_ height values
    const uniqueHeights = elementHeights.filter((height, index, array) => {
      return array.indexOf(height) == index;
    });

    // If there is only 1 unique value, then all elements are the same height,
    // and in that case, we don't need to change the height at all
    if (uniqueHeights.length > 1) {
      return new Promise((resolve, reject) => {
        const maxHeight = Math.max.apply(null, elementHeights);
        const tallestElementIndex = elementHeights.indexOf(maxHeight);

        Array.prototype.forEach.call(elements, (el, index) => {
          // Ignore the tallest element as it is already set to the right height
          if (index != tallestElementIndex) {
            el.style.minHeight =`${maxHeight}px`;
          }
        });
      resolve();
      });
    }
  }
}

export default equalHeightGrid;
