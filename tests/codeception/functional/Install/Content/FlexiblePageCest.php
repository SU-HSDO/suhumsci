<?php

use Faker\Factory;

/**
 * Class FlexiblePageCest.
 *
 * @group install
 */
class FlexiblePageCest {

  /**
   * Faker service.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * Test constructor.
   */
  public function __construct() {
    $this->faker = Factory::create();
  }

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
    $I->waitForText('Decorative Image');
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
    $I->waitForText('Decorative Image');
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

    /**
   * I can create a page with a spotlight slider.
   */
  public function testSpotlightSlider(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    $I->fillField('Title', $this->faker->words(3, TRUE));
    $I->click('List additional actions','#edit-field-hs-page-hero-add-more');
    $I->click('field_hs_page_hero_hs_sptlght_slder_add_more');
    $I->waitForText('No media items are selected');
    $I->canSee('Title');
    $I->canSee('Height');
    $I->canSee('Background Color');
    $I->canSee('Image Alignment');
    $I->canSee('Body');

    // Populating spotlight #1.
    $I->click('Add media', '.paragraph-type--hs-sptlght-slder');
    $I->waitForText('Add or select media');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Decorative Image');
    $I->click('Save and insert', '.ui-dialog-buttonset');
    $I->waitForText('The maximum number of media items have been selected');
    $I->waitForText('Source');
    $I->click('.cke_button__source.cke_button_off');
    $I->fillField('.cke_source', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>');
    $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][0][subform][field_hs_spotlight_link][0][uri]', 'http://google.com');
    $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][0][subform][field_hs_spotlight_link][0][title]', 'Google Link');
    $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][0][subform][field_hs_spotlight_title][0][value]', 'Spotlight #1 Title');

    // Populating spotlight #2.
    $I->scrollTo('.paragraphs-add-wrapper');
    $I->click('Add Spotlight');
    $I->wait(1);
    $I->click('//input[@class="js-media-library-open-button media-library-open-button button js-form-submit form-submit"][1]');
    $I->waitForText('Add or select media');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Decorative Image');
    $I->click(['class' => "form-radio"]);
    $I->click('Save and insert', '.ui-dialog-buttonset');
    $I->waitForText('The maximum number of media items have been selected');
    $I->waitForText('Source');
    $I->scrollTo('.paragraph-type--hs-spotlight.even .field--type-text-long', 0, -300);
    $I->click('.paragraph-type--hs-spotlight.even .cke_button__source.cke_button_off');
    $I->fillField('.cke_source', '<p>Aliquet porttitor lacus luctus accumsan tortor posuere ac.</p>');
    $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][1][subform][field_hs_spotlight_link][0][uri]', 'http://yahoo.com');
    $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][1][subform][field_hs_spotlight_link][0][title]', 'Yahoo Link');
    $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][1][subform][field_hs_spotlight_title][0][value]', 'Spotlight #2 Title');
    $I->click('Save');

    // Check spotlight 1.
    $I->wait(2);
    $I->waitForText('Spotlight #1 Title');
    $I->canSee('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');
    $I->canSee('Google Link', 'a');
    $I->canSeeNumberOfElements('picture img', 1);

    // Check spotlight 2.
    $I->click('.slick-next');
    $I->waitForText('Spotlight #2 Title');
    $I->canSee('Aliquet porttitor lacus luctus accumsan tortor posuere ac.');
    $I->canSee('Yahoo Link', 'a');
    $I->canSeeNumberOfElements('picture img', 1);
  }

}
