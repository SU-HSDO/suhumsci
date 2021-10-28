// This function accepts a list of elements that you would like to reset the height of.
// Ideally these types of elements are laid out in a grid that you'd like the height to be the same.
const resetHeightGrid = (elements) => {
  elements.forEach((el) => {
    const element = el;
    element.style.minHeight = 'auto';
  });
};

export default resetHeightGrid;
