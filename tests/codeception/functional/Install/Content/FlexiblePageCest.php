<?php

/**
 * Class FlexiblePageCest.
 *
 * @group install
 */
class FlexiblePageCest {

  /**
   * Resize the window at the start.
   */
  public function _before(FunctionalTester $I){
    $I->resizeWindow(2000, 1400);
  }

  /**
   * I can create a page with a hero banner.
   */
  public function testHeroParagraph(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('List additional actions', '#edit-field-hs-page-hero-add-more');
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

  /**
   * Create a photo album page.
   */
  public function testPhotoAlbum(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/structure/types/manage/hs_basic_page/fields/node.hs_basic_page.field_hs_page_components');
    $this->disableCollection = (bool) $I->grabAttributeFrom('[name="settings[handler_settings][target_bundles_drag_drop][stanford_gallery][enabled]"]', 'checked');
    if ($this->disableCollection) {
      $I->uncheckOption('Photo Album');
      $I->click('Save settings');
    }

    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->scrollTo('#field-hs-page-components-hs-text-area-add-more');
    $I->click('List additional actions', '#edit-field-hs-page-components-add-more');
    $I->scrollTo('#field-hs-page-components-stanford-gallery-add-more');
    $I->click('#field-hs-page-components-stanford-gallery-add-more');
    $I->waitForText('Headline');
    $I->fillField('Headline', 'Photo Album Headline');
    $I->click('Add media', '.field--name-su-gallery-images');
    $I->waitForText('Add or select media');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Alternative text');
    $I->click('Save and insert', '.ui-dialog-buttonset');
    $I->waitForElementVisible('.media-library-item__preview img');
    $I->click('Save');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Photo Album Headline', 'h2');
    $I->canSeeNumberOfElements('.su-gallery-images img', 1);
    $I->canSeeNumberOfElements('#cboxContent img', 0);

    $I->click('Edit', '.tabs');
    $I->click('Edit', '.paragraph-type--stanford-gallery');
    $I->waitForText('Description');
    $I->click('Style');
    $I->selectOption('Display Mode', 'Slideshow');
    $I->click('Save');
    $I->canSeeNumberOfElements('.slick img', 1);
  }

   /**
   * Verify main menu links at mobile size
   */
  public function testMobileMenu(FunctionalTester $I) {
    // Check standard menu item links
    $I->amOnPage('/');
    $I->resizeWindow(800, 1100);
    $I->seeElement('.hb-main-nav');

    // This try/catch keeps the toggle consistent between environment testing.
    // It will check for the visible element and continue steps for either scenario.
    try {
      $I->waitForElementVisible('.hb-main-nav__link');
      // Continue to do this if it's present.
      $I->seeElement('.hb-main-nav__link');
      $I->click('.hb-main-nav__link');
      echo ('If you see this, the menu was open and the link was clicked.');

    }
    catch (\Exception $e) {
      // Do this if it's not present.
      echo ('If you see this, the menu needs toggled.');
      $I->click('button.hb-main-nav__toggle');
      $I->waitForElementVisible('.hb-main-nav__link');
      $I->seeElement('.hb-main-nav__link');
      $I->click('.hb-main-nav__link');
    }

    // This try/catch keeps the toggle consistent between environment testing.
    // Check nested menu item links
    try {
      echo ('If you see this, the nested menu link was already available to click.');
      $I->waitForElementVisible('.hb-main-nav__menu-lv2');
      // Click nested menu link if it's already visible.
      $I->click('.hb-main-nav__menu-lv2 a');

    }
    catch (\Exception $e) {
      // Do this if the nested menu link is not already visible.
      echo ('If you see this, the nested menu link needs to be opened to click.');
      $I->click('.hb-main-nav__toggle');
      $I->waitForElementVisible('.hb-nested-toggler');
      $I->click('.hb-nested-toggler');
      $I->waitForElementVisible('.hb-main-nav__menu-lv2');
      $I->click('.hb-main-nav__menu-lv2 a');
    }

    // Check standard menu item links for logged in users
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->click('.hb-main-nav__toggle');
    $I->seeElement('.hb-main-nav__menu');
    $I->click('.hb-main-nav__link');
  }

}
