const changeNav = (toggle, parent, isOpen) => {
  toggle.setAttribute('aria-expanded', isOpen);
  parent.setAttribute('aria-hidden', !isOpen);
  console.log(parent);
};

export default changeNav;
