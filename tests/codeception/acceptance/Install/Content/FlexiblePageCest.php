<?php

/**
 * Class FlexiblePageCest.
 *
 * @group install
 */
class FlexiblePageCest {

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

}
