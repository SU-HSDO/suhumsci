/* global ed11yLang */
window.editoria11yOptionsOverride = true;
window.editoria11yOptions = (options) => {
  // options.sleekTheme = {
  //   bg: '#fffffe',
  //   bgHighlight: '#7b1919',
  //   text: '#20160c',
  //   primary: '#276499',
  //   primaryText: '#fffdf7',
  //   secondary: '#20160c',
  //   button: 'transparent',
  //   panelBar: '#fffffe',
  //   panelBarText: '#20160c',
  //   panelBarShadow: 'inset 0 -1px #0002, -1px 0 #0002',
  //   panelBorder: 0,
  //   activeTab: '#276499',
  //   activeTabText: '#fffffe',
  //   outlineWidth: 0,
  //   borderRadius: 1,
  //   ok: '#0098db',
  //   warning: '#0098db',
  //   alert: '#0098db',
  //   focusRing: '#276499',
  // };

  if (ed11yLang && ed11yLang.en) ed11yLang.en.buttonOutlineContent = 'Heading Outline';
  return options;
};
