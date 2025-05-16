<?php

/**
 * Class PrivatePageContentCest.
 *
 * @group install
 * @group private-page
 */
class PrivatePageContentCest {

  private $fieldsToCheck = [
    'Accordion' => [
      'component_text' => 'Summary',
      'component_button_name' => 'Accordion',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_accordion][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Banner image' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Banner image',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_banner][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Banner image with full overlay and text' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Banner image with full overlay and text',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_hero_image][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Banner image(s) with text box' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Banner image(s) with text box',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_carousel][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Banner image(s) with partial overlay and text' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Banner image(s) with partial overlay and text',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_gradient_hero_slider][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Callout Box' => [
      'component_text' => 'Body',
      'component_button_name' => 'Callout Box',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_callout_box][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Collection' => [
      'component_text' => 'Title',
      'component_button_name' => 'Collection',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_collection][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Color Band' => [
      'component_text' => 'Additional Text',
      'component_button_name' => 'Color Band',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_clr_bnd][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Developers\' Catalog' => [
      'component_text' => 'View',
      'component_button_name' => 'Developers\' Catalog',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_view][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Photo Album' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Photo Album',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][stanford_gallery][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Postcard' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Postcard',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_postcard][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Spotlight(s)' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Spotlight(s)',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_sptlght_slder][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Testimonial' => [
      'component_text' => 'No media items are selected.',
      'component_button_name' => 'Testimonial',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_testimonial][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Text Area' => [
      'component_text' => 'Body',
      'component_button_name' => 'Text Area',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_text_area][enabled]"]',
      'disable_component' => FALSE,
    ],
    'Vertical Timeline' => [
      'component_text' => 'Item Title',
      'component_button_name' => 'Vertical Timeline',
      'admin_name' => '[name="settings[handler_settings][target_bundles_drag_drop][hs_timeline][enabled]"]',
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
      $add_button = 'table[id^="field-hs-priv-page-components-values"] tr:last-child .paragraphs-features__add-in-between__button';
      $I->waitForElement($add_button);
      $I->scrollTo($add_button);
      $I->click($add_button);      $I->waitForText('Add Component');
      $I->fillField('.paragraphs-ee-add-dialog input[type="search"]', $component);
      $I->click($component_info['component_button_name'], '.paragraphs-ee-add-dialog');
      $I->waitForText($component_info['component_text']);
    }
    $I->see('PRIVATE FILE INSERT');
  }

}
