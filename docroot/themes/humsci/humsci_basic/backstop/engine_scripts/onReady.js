module.exports = async (page, scenario) => {
  console.log(`SCENARIO > ${scenario.label}`); // eslint-disable-line no-console
  await require('./clickAndHoverHelper')(page, scenario); // eslint-disable-line global-require

  const site = scenario.label.split(' ')[0];
  const pairing = scenario.label.split(' ')[2];

  if (pairing === 'cardinal') {
    return;
  }

  const classPrefix = site === 'hs-colorful' ? 'hc' : 'ht';

  await page.evaluate(`
    const html = document.querySelector('html');
    html.classList.remove('${classPrefix}-pairing-cardinal');
    html.classList.add('${classPrefix}-pairing-${pairing}');
  `);
};
