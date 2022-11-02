<?php

use Faker\Factory;

/**
 * Class VideoEmbedCest.
 *
 * @group install
 */
class VideoEmbedCest {

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
   * I can create a page with a video embed and verify caption.
   */
  public function testVideoEmbed(FunctionalTester $I) {
    // Login and add flexible page
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    $I->fillField('Title', $this->faker->words(3, TRUE));

    // Add text field
    $I->scrollTo('#edit-field-hs-page-components-add-more');
    $I->click('List additional actions', '#edit-field-hs-page-components-add-more');
    $I->scrollTo('#field-hs-page-components-hs-text-area-add-more');
    $I->click('#field-hs-page-components-hs-text-area-add-more');
    $I->wait(2);

    // Add media
    $I->click('.cke_button__drupalmedialibrary');
    $I->waitForText('Add or select media');
    $I->click('.media-library-menu__link[data-title="Video"]');
    $I->waitForText('Add Video via URL');
    $I->fillField('Add Video via URL', 'https://www.youtube.com/watch?v=95N_spFNEkY');
    $I->click('.js-form-submit[value="Add"]');
    $I->executeJS('document.querySelector("body").removeChild(document.querySelector(".ui-widget-overlay"))', []);
    $I->waitForText('Save and select');
    $I->executeJS('document.querySelector(".ui-dialog-buttonpane .button--primary").click()', []);
    $I->waitForText('Insert selected');
    $I->executeJS('document.querySelector("button.media-library-select").click()', []);
    $I->wait(2);

    // Enable caption
    $I->switchToIFrame(".cke_wysiwyg_frame");
    $I->executeJS('document.querySelector(".media-library-item__edit").style.display = "inline"', []);
    $I->executeJS('document.querySelector(".media-library-item__edit").click()', []);
    $I->switchToIFrame();
    $I->waitForText('Caption');
    $I->executeJS('document.querySelector("input[name=\"hasCaption\"]").click()', []);
    $I->executeJS('document.querySelector(".ui-dialog-buttonset .js-form-submit").click()', []);
    $I->wait(1);

    // Add caption
    $I->switchToIFrame(".cke_wysiwyg_frame");
    $I->executeJS('document.querySelector("figcaption").textContent += "Caption for video goes here"', []);

    // Save node
    $I->switchToIFrame();
    $I->click('Save');

    // Verify figure and figcaption
    $I->seeElement('figure');
    $I->seeElement('figcaption');
    $I->see("Caption for video goes here");
    $I->makeScreenshot('edit_page');
  }
}
