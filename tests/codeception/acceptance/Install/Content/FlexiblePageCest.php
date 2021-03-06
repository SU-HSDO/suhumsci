<?php

/**
 * Class FlexiblePageCest.
 *
 * @group install
 * @group testme
 */
class FlexiblePageCest {

  protected $disableCollection = FALSE;

  /**
   * Disable the collection if it was originally disabled.
   */
  public function _after(AcceptanceTester $I) {
    if ($this->disableCollection) {
      $I->amOnPage('/user/logout');
      $I->logInWithRole('administrator');
      $I->amOnPage('/admin/structure/types/manage/hs_basic_page/fields/node.hs_basic_page.field_hs_page_components');
      $I->checkOption('Collection');
      $I->click('Save settings');
      $this->disableCollection = FALSE;
    }
  }

  /**
   * I can create a postcard on the page.
   */
  public function testPostCard(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->canSeeNumberOfElements('#edit-field-hs-page-hero-wrapper', 1);
    $I->canSeeNumberOfElements('#edit-field-hs-page-components-add-more input', 14);
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Add Postcard');
    $I->canSee('Card Title');
    $I->canSee('Card Body');
    $I->canSee('Read More Link');
    $I->fillField('Card Title', 'Nam at tortor in tellus');
    $I->fillField('Card Body', 'Maecenas vestibulum mollis diam.');
    $I->fillField('URL', 'http://google.com');
    $I->fillField('Link text', 'Praesent egestas tristique nibh');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->canSeeResponseCodeIs(200);
    $I->canSee('Nam at tortor in tellus', 'h2');
    $I->canSee('Maecenas vestibulum mollis diam.');
    $I->canSeeLink('Praesent egestas tristique nibh', 'http://google.com');
  }

  /**
   * I can create an accordion on the page.
   */
  public function testAccordion(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Add Accordion');
    $I->fillField('Summary', 'Sed augue ipsum egestas nec');
    $I->fillField('Description', 'Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Sed augue ipsum egestas nec');
    $I->canSee('Vivamus in erat ut urna cursus vestibulum');
  }

  /**
   * I can create a text area on the page.
   */
  public function testTextArea(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Add Text Area');
    $I->fillField('Text Area', 'Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Vivamus in erat ut urna cursus vestibulum');
  }

  /**
   * I can create a row with a text area on the page.
   */
  public function testRow(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');

    $I->click('Add Row');
    $I->canSee('Paragraph Style');
    $I->click('Add Text Area');
    $I->fillField('Text Area', 'Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.');

    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.');
  }

  /**
   * I can create a collection of items and display them in 2, 3 or 4 per row.
   */
  public function testCollections(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/structure/types/manage/hs_basic_page/fields/node.hs_basic_page.field_hs_page_components');
    $this->disableCollection = (bool) $I->grabAttributeFrom('[name="settings[handler_settings][target_bundles_drag_drop][hs_collection][enabled]"]', 'checked');
    if ($this->disableCollection) {
      $I->uncheckOption('Collection');
      $I->click('Save settings');
    }

    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Add Collection');
    $I->selectOption('Items Per Row', 2);
    $I->canSeeOptionIsSelected('Paragraph Style', '- None -');
    $I->click('Add Text Area', '.field--name-field-hs-collection-items');
    $I->fillField('Text Area', 'Foo Bar Baz');
    $I->click('Add Postcard', '.field--name-field-hs-collection-items');
    $I->fillField('Card Title', 'Demo card title');
    $I->fillField('Card Body', 'Bar Foo Baz');
    $I->click('Save');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Foo Bar Baz', '.item-per-row--2');
    $I->canSee('Demo card title', '.item-per-row--2 h2');
    $I->canSee('Bar Foo Baz', '.item-per-row--2');
  }

}
