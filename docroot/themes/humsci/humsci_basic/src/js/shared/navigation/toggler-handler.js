import changeNav from './change-nav';

const togglerHandler = (e, toggler, togglerContent) => {
  e.preventDefault();

  const isExpanded = e.target.getAttribute('aria-expanded') === 'true';
  changeNav(toggler, togglerContent, !isExpanded);
};

export default togglerHandler;
