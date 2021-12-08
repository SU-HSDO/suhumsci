// This function accepts a list of elements that you would like to be the same height.
// Ideally these types of elements are laid out in a grid that you'd like the height to be the same.
const equalHeightGrid = (elements) => {
  if (elements.length > 0) {
    // Create array with all of the heights of each element
    const elementHeights = Array.prototype.map.call(elements, (el) => el.scrollHeight);

    // Create array with _unique_ height values
    // const uniqueHeights = elementHeights.filter((height, index, array) => {
    //   return array.indexOf(height) == index;
    // });

    return new Promise((resolve) => {
      const maxHeight = Math.max.apply(null, elementHeights);
      const tallestElementIndex = elementHeights.indexOf(maxHeight);

      Array.prototype.forEach.call(elements, (el, index) => {
        // Ignore the tallest element as it is already set to the right height
        if (index !== tallestElementIndex) {
          const element = el;
          element.style.minHeight = `${maxHeight}px`;
        }
      });
      resolve();
    });
  }
};

export default equalHeightGrid;
