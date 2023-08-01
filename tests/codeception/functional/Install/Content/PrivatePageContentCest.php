<?php

use Codeception\Util\Locator;

/**
 * Class PrivatePageContentCest.
 *
 * @group install
 */
class PrivatePageContentCest{

  private $fieldsToCheck = [
    'Private Collection' => [
      'component_text' => 'Items Per Row',
      'component_button_name' => 'field_hs_priv_page_components_hs_priv_collection_add_more',
    ],
    'Spotlight - Slider' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'field_hs_priv_page_components_hs_sptlght_slder_add_more',
    ],
    'Accordion' => [
      'component_text' => 'Summary',
      'component_button_name' => 'field_hs_priv_page_components_hs_accordion_add_more',
    ],
    'Postcard' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'field_hs_priv_page_components_hs_postcard_add_more',
    ],
    'Private Text Area' => [
      'component_text' => 'Private Text Area',
      'component_button_name' => 'field_hs_priv_page_components_hs_priv_text_area_add_more',
    ],
  ];

  /**
   * A private page has certain fields that should be available.
   */
  public function testPrivatePageContent(FunctionalTester $I){
    $I->logInWithRole('site_manager');
    $I->amOnPage('/node/add/hs_private_page');
    $I->fillField('Title', 'Test Private Page');
    foreach ($this->fieldsToCheck as $component => $component_info) {
      $I->scrollTo('.field--name-field-priv-wysiwyg-files');
      $I->click('Add Paragraph');
      $I->waitForText('Browse');
      $I->fillField('pb_modal_text', $component);
      $I->click($component_info['component_button_name']);
      $I->waitForText($component_info['component_text']);
    }
    $I->see('PRIVATE FILE INSERT');
  }
}
