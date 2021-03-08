// This function takes two parameters:
// The first parameter should be whatever type of element you'd like to be the same height, and
// ideally these types of elements are laid out in a grid that you'd like the height to be the same
// The second parameter is the special class that will make this function execute and turn elements
// into the same height. If the special class isn't present, we won't run this function.
const equalHeightGrid = (element, specialWrapperClass) => {
  const equalGridWrapper = document.getElementsByClassName(specialWrapperClass);

  // If the wrapper class for specifying making things the same height is present,
  // keep going
  if (equalGridWrapper.length > 0) {
    const elementList = document.getElementsByClassName(element);

    if (elementList.length > 0) {
      // Create array with all of the heights of each element
      const elementHeights = Array.prototype.map.call(elementList, (el) => {
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

          Array.prototype.forEach.call(elementList, (el, index) => {
            // Ignore the tallest element as it is already set to the right height
            if (index != tallestElementIndex) {
              el.style.height =`${maxHeight}px`;
            }
          });
        resolve();
        });
      }
    }
  } else {
    return Promise.resolve();
  }
}

export default equalHeightGrid;
