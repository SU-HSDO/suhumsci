<?php

/**
 * Class FlexiblePageCest.
 *
 * @group install
 */
class FlexiblePageCest {

  /**
   * I can create a row with a text area on the page.
   *
   * Protected function to prevent it from running.
   */
  protected function testRow(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->canSee('Browse');
    $I->canSee('Search');
    $I->canSee('Add');
    $I->click('Add', 'field_hs_page_components_hs_row_add_more');
    $I->canSee('Paragraph Style');
    $I->click('Add Text Area');
    $I->fillField('Text Area', 'Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.');

    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.');
  }

}
