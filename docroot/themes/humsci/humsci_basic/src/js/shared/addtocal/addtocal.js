function addToCalAria() {
  const addToCal = document.querySelectorAll('.addtocal');
  const body = document.querySelector('body');

  // For each .addtocal button, when clicked, change aria-expanded to true.
  addToCal.forEach((button) => {
    button.addEventListener('click', () => {
      if (button.getAttribute('aria-expanded') === 'true') {
        button.setAttribute('aria-expanded', 'false');
      } else {
        button.setAttribute('aria-expanded', 'true');
      }
    });
  });

  // When the body except button is clicked, change aria-expanded to false.
  body.addEventListener('click', (e) => {
    addToCal.forEach((button) => {
      if (e.target !== button) {
        button.setAttribute('aria-expanded', 'false');
      }
    });
  });
}

addToCalAria();
