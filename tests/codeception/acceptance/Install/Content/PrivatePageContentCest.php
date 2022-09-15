<?php

/**
 * Class PrivatePageContentCest.
 *
 * @group install
 */
class PrivatePageContentCest{

  private $fieldsToCheck = [
    'input[value="Add Private Text Area"]',
    'input[value="Add Private Collection"]',
    'input[value="Add Spotlight - Slider"]',
    'input[value="Add Accordion"]',
    'input[value="Add Postcard"]',
  ];

  /**
   * A private page has certain fields that should be available.
   */
  public function testPrivatePageContent(AcceptanceTester $I){
    $I->logInWithRole('site_manager');
    $I->amOnPage('/node/add/hs_private_page');
    $I->fillField('Title', 'Test Private Page');
    foreach ($this->fieldsToCheck as $field) {
      $I->click($field);
    }
    $I->see('PRIVATE FILE INSERT');
  }
}
