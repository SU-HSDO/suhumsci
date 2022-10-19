<?php

/**
 * Class VideoEmbedCest.
 *
 * @group videoembed
 */
class VideoEmbedCest {

  /**
   * I can create a page with a hero banner.
   */
  public function testVideoEmbed(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->scrollTo('#edit-field-hs-page-components-add-more');
    $I->click('List additional actions', '#edit-field-hs-page-components-add-more');
    $I->scrollTo('#field-hs-page-components-hs-text-area-add-more');
    $I->click('#field-hs-page-components-hs-text-area-add-more');
    $I->waitForText('Text format');
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

    $I->switchToIFrame(".cke_wysiwyg_frame");
    $I->executeJS('document.querySelector(".media-library-item__edit").style.display = "inline"', []);
    $I->executeJS('document.querySelector(".media-library-item__edit").click()', []);
    $I->switchToIFrame();
    $I->waitForText('Caption');
    $I->executeJS('document.querySelector("input[name=\"hasCaption\"]").click()', []);


    $I->makeScreenshot('edit_page');

    // $I->click('field_hs_page_hero_hs_hero_image_add_more');
    // $I->waitForText('No media items are selected');
    // $I->canSee('Overlay Details');
    // $I->cantSee('Optionally add some overlay text on top of the image');
    // $I->cantSee('Body');
    // $I->cantSee('Link text');
    // $I->cantSee('Overlay Color');
    // $I->click('Add media', '.paragraph-type--hs-hero-image');
    // $I->waitForText('Add or select media');
    // $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    // $I->click('Upload and Continue');
    // $I->waitForText('Decorative Image');
    // $I->click('Save and insert', '.ui-dialog-buttonset');
    // $I->waitForText('The maximum number of media items have been selected');
    // $I->click('.paragraph-type--hs-hero-image summary');
    // $I->canSee('Optionally add some overlay text on top of the image');
    // $I->canSee('Body');
    // $I->canSee('Link text');
    // $I->cantSee('Overlay Color');
    // $I->fillField('field_hs_page_hero[0][subform][field_hs_hero_title][0][value]', 'Overlay Title');

    // $I->fillField('URL', 'http://google.com');
    // $I->fillField('Link text', 'Google CTA');
    // $I->click('Save');
    // $I->canSeeNumberOfElements('#main-content img', 1);
    // $I->canSee('Overlay Title');
    // $I->canSee('Google CTA', 'a');
  }

}
