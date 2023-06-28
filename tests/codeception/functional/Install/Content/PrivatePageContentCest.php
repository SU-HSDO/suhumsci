<?php

/**
 * Class PrivatePageContentCest.
 *
 * @group install
 */
class PrivatePageContentCest{

  private $fieldsToCheck = [
    'Private Text Area',
    'Private Collection',
    'Spotlight - Slider',
    'Accordion',
    'Postcard',
  ];

  /**
   * A private page has certain fields that should be available.
   */
  // Error: Add Paragraph not found
  // public function testPrivatePageContent(FunctionalTester $I){
  //   $I->logInWithRole('site_manager');
  //   $I->amOnPage('/node/add/hs_private_page');
  //   $I->fillField('Title', 'Test Private Page');
  //   foreach ($this->fieldsToCheck as $field) {
  //     $I->scrollTo('#edit-field-hs-priv-page-components-add-more-browse');
  //     $I->click('Add Paragraph', '#edit-field-hs-priv-page-components-add-more-browse');
  //     $I->waitForText('Browse');
  //     $I->fillField('pb_modal_text', $field);
  //     $I->click('Add');
  //   }
  //   $I->see('PRIVATE FILE INSERT');
  // }
}
