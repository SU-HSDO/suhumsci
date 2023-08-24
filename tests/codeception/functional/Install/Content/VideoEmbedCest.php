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
    $I->click('Add Component');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Text Area');
    $I->click('field_hs_page_components_hs_text_area_add_more');
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
    $I->click('//button[@data-cke-tooltip-text="Toggle caption on"]');
    $I->fillField('figcaption.ck-editor__nested-editable', 'sore was I ere I saw eros');
    // Save node
    $I->click('Save');

    // Verify figure and figcaption
    $I->seeElement('figure');
    $I->seeElement('figcaption');
    $I->scrollTo('figcaption');
    $I->see("sore was I ere I saw eros");
  }
}
