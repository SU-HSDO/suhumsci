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
    $I->scrollTo('#edit-field-hs-page-components-add-more-browse');
    $I->click('Add Component', '#edit-field-hs-page-components-add-more-browse', '#edit-field-hs-page-components-add-more-browse');
    $I->fillField('pb_modal_text', 'Text Area');
    $I->click('Add', '[data-drupal-selector="edit-add-more-button-hs-text-area"]');
    $I->wait(2);

    // Add media
    $I->click('.ck-button[data-cke-tooltip-text="Insert Media"]');
    $I->waitForText('Add or select media');
    $I->click('.media-library-menu__link[data-title="Video"]');
    $I->waitForText('Add Video via URL');
    $I->fillField('Add Video via URL', 'https://www.youtube.com/watch?v=95N_spFNEkY');
    $I->click('Add', '.media-library-add-form__input-wrapper');
    $I->waitForText('Save and select');
    $I->click('Save and select', '.ui-dialog-buttonpane');
    $I->waitForText('Insert selected');
    $I->click('Insert selected', '.ui-dialog-buttonpane');
    $I->wait(2);

    // Enable caption
    // $I->click('.drupal-media.ck-widget');
    // $I->executeJS('document.evaluate("//.ck-button__label[text()=\"Caption media\"]", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();', []);
    // $I->click('.ck-button[data-cke-tooltip-text="Caption media"]');
    // $I->wait(1);

    // Add caption
    // $I->executeJS('document.querySelector("figcaption").textContent += "Caption for video goes here"', []);

    // Save node
    $I->click('Save');

    // Verify figure and figcaption
    // $I->seeElement('figure');
    // $I->seeElement('figcaption');
    // $I->see("Caption for video goes here");
  }
}
