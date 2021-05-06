<?php

/**
 * Class FlexiblePageCest.
 *
 * @group install
 */
class FlexiblePageCest {

  /**
   * I can create a page with a hero banner.
   */
  public function testHeroParagraph(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('field_hs_page_hero_hs_hero_image_add_more');
    $I->waitForText('No media items are selected');
    $I->canSee('Overlay Details');
    $I->cantSee('Optionally add some overlay text on top of the image');
    $I->cantSee('Body');
    $I->cantSee('Link text');
    $I->cantSee('Overlay Color');
    $I->click('Add media', '.paragraph-type--hs-hero-image');
    $I->waitForText('Add or select media');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Alternative text');
    $I->click('Save and insert', '.ui-dialog-buttonset');
    $I->waitForText('The maximum number of media items have been selected');
    $I->click('.paragraph-type--hs-hero-image summary');
    $I->canSee('Optionally add some overlay text on top of the image');
    $I->canSee('Body');
    $I->canSee('Link text');
    $I->cantSee('Overlay Color');
    $I->fillField('field_hs_page_hero[0][subform][field_hs_hero_title][0][value]', 'Overlay Title');

    $I->fillField('URL', 'http://google.com');
    $I->fillField('Link text', 'Google CTA');
    $I->click('Save');
    $I->canSeeNumberOfElements('#main-content img', 1);
    $I->canSee('Overlay Title');
    $I->canSee('Google CTA', 'a');
  }

}
