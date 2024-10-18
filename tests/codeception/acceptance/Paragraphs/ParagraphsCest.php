<?php

/**
 * Tests on paragraph types.
 */
class ParagraphsCest {

  /**
   * Private collections should be disabled on most places.
   *
   * @group install
   * @group existingSite
   */
  public function testPrivateCollectionParagraph(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/node/add/hs_basic_page');

    // Enabled on private pages.
    $I->amOnPage('/admin/structure/types/manage/hs_private_page/fields/node.hs_private_page.field_hs_priv_page_components');
    $I->canSeeCheckboxIsChecked('Include the selected below');

    // Disabled in public collections.
    $I->amOnPage('/admin/structure/paragraphs_type/hs_collection/fields/paragraph.hs_collection.field_hs_collection_items');
    $I->canSeeCheckboxIsChecked('Exclude the selected below');
    $I->canSeeCheckboxIsChecked('settings[handler_settings][target_bundles_drag_drop][hs_priv_collection][enabled]');

    // Disabled in flexible page components.
    $I->amOnPage('/admin/structure/types/manage/hs_basic_page/fields/node.hs_basic_page.field_hs_page_components');
    $I->canSeeCheckboxIsChecked('Exclude the selected below');
    $I->canSeeCheckboxIsChecked('settings[handler_settings][target_bundles_drag_drop][hs_priv_collection][enabled]');
  }

}
