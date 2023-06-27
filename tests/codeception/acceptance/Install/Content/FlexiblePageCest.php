<?php

/**
 * Class FlexiblePageCest.
 *
 * @group install
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
    $I->canSeeNumberOfElements('#edit-field-hs-page-components-add-more input[type="submit"]', 15);
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
   * I can add a Back To Top Block.
   */
  public function testBackToTopExists(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Back To Top');
    $I->click('Add Text Area');
    $I->fillField('Text Area',
'Sit aliquid minus autem iste labore Quos repellendus voluptas laborum atque incidunt quis. Facilis voluptates nemo ducimus facilis inventore. Fugit quod maiores et placeat modi error Voluptates recusandae facilis minus soluta minima illo Eligendi velit minus animi mollitia quisquam fuga? Ducimus eligendi in praesentium placeat unde Iure totam id inventore doloremque optio Accusamus nesciunt adipisci praesentium provident repellendus Pariatur quam quos dolorem porro rem provident. Natus fuga dolor sunt tenetur debitis? Alias exercitationem fuga impedit nihil facilis ab nam rerum, nam! Minus optio repellendus nesciunt repudiandae maxime. Iure vel sapiente dignissimos accusantium eius Expedita veniam error distinctio deserunt iusto Eius omnis impedit odio delectus recusandae Voluptatum id a repellendus ab illum Labore dignissimos nihil corporis nemo fuga Sit natus odit facilis vitae numquam! Voluptatum doloremque quis voluptate dolorem possimus minus. Iure fuga expedita facilis magni temporibus Delectus odio aliquid at enim fuga? Consequuntur quaerat quia fuga eum earum Accusamus distinctio provident non debitis vero Quos ad a mollitia veritatis natus eius eius. Quisquam ad fugiat rem libero saepe Ipsam nam laboriosam ullam accusamus aspernatur Quasi est fugiat veritatis distinctio facilis Voluptatem enim velit qui maxime culpa mollitia magni Ipsa cupiditate in dolores velit dignissimos nemo. Commodi repellendus officia dolor accusamus');
    $I->click('Save');
    $I->click('Layout', '.tabs');
    $I->canSee('Add Block', 'a');
    $I->click('Add block');
    $I->click('Back To Top Block');
    $I->canSee('Configure block');
    $I->click('Add block');
    $I->click('Save layout');
    $I->seeElement('.hs-back-to-top');
  }

  /**
   * I can create a text area on the page.
   */
  public function testTextArea(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Add Component');
    $I->fillField('Search', 'Text Area');
    $I->click('Add', '[data-drupal-selector="edit-add-more-button-hs-text-area"]');
    $I->fillField('Text Area', 'Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Vivamus in erat ut urna cursus vestibulum');
  }

  /**
   * I can create a row with a text area on the page.
   *
   * Protected function to prevent it from running.
   */
  protected function testRow(AcceptanceTester $I) {
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
    $I->canSeeNumberOfElements('[data-drupal-selector="edit-field-hs-page-components-1-subform-field-hs-collection-per-row"] option', 4);
    $I->selectOption('Items Per Row', 2);
    $I->canSeeOptionIsSelected('Style', '- None -');
    $I->click('Add Text Area', '[data-drupal-selector="edit-field-hs-page-components-1-subform-field-hs-collection-items"]');
    $I->fillField('Text Area', 'Foo Bar Baz');
    $I->click('Add Postcard', '[data-drupal-selector="edit-field-hs-page-components-1-subform-field-hs-collection-items"]');
    $I->fillField('Card Title', 'Demo card title');
    $I->fillField('Card Body', 'Bar Foo Baz');
    $I->click('Save');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Foo Bar Baz', '.item-per-row--2');
    $I->canSee('Demo card title', '.item-per-row--2 h2');
    $I->canSee('Bar Foo Baz', '.item-per-row--2');
  }

}
