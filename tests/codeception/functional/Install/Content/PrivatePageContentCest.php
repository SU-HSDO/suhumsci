<?php

/**
 * Class PrivatePageContentCest.
 *
 * @group install
 * @group private-page
 */
class PrivatePageContentCest {

  private $fieldsToCheck = [
    'Private Collection' => [
      'component_text' => 'Items Per Row',
      'component_button_name' => 'Private Collection',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_priv_collection][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Spotlight' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Spotlight(s)',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_sptlght_slder][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Accordion' => [
      'component_text' => 'Summary',
      'component_button_name' => 'Accordion',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_accordion][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Postcard' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Postcard',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_postcard][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Private Text Area' => [
      'component_text' => 'Private Text Area',
      'component_button_name' => 'Private Text Area',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_priv_text_area][enabled]"]',
      'disable_component' => FALSE,
    ],
  ];

  /**
   * Enable components at the start.
   */
  public function _before(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    foreach ($this->fieldsToCheck as $component => $component_info) {
      $I->amOnPage('/admin/structure/types/manage/hs_private_page/fields/node.hs_private_page.field_hs_priv_page_components');
      $component_info['disable_component'] = (bool) $I->grabAttributeFrom($component_info['admin_name'], 'checked');
      if (!$component_info['disable_component']) {
        $I->checkOption($component);
        $I->click('Save settings');
      }
      $this->fieldsToCheck[$component] = $component_info;
    }
    $I->amOnPage('/user/logout');
    $I->click('.user-logout-confirm #edit-submit');
  }

  /**
   * A private page has certain fields that should be available.
   */
  public function testPrivatePageContent(FunctionalTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/node/add/hs_private_page');
    $I->fillField('Title', 'Test Private Page');
    foreach ($this->fieldsToCheck as $component => $component_info) {
      $I->scrollTo('.field--name-field-priv-wysiwyg-files');
      $I->click('Add Component');
      $I->waitForText('Add Component');
      $I->fillField('.paragraphs-ee-add-dialog input[type="search"]', $component);
      $I->click($component_info['component_button_name'], '.paragraphs-ee-add-dialog');
      $I->waitForText($component_info['component_text']);
    }
    $I->see('PRIVATE FILE INSERT');
  }

}
