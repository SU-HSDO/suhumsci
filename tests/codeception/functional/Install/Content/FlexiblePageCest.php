<?php

use Faker\Factory;

/**
 * Class FlexiblePageCest.
 *
 * @group install
 */
class FlexiblePageCest {

  protected $disableCollection = FALSE;

  /**
   * Faker service.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * Test constructor.
   */
  public function __construct() {
    $this->faker = Factory::create();
  }

  /**
   * Resize the window at the start.
   */
  public function _before(FunctionalTester $I) {
    $I->resizeWindow(2000, 1400);
  }


  /**
   * Disable the collection if it was originally disabled.
   */
  public function _after(FunctionalTester $I) {
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
   * Duplicated paragraphs should have a class available.
   *
   * @group paragraphs
   */
  public function testDuplicateScroll(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    $node = $I->createEntity([
      'title' => $this->faker->words(3, TRUE),
      'type' => 'hs_basic_page',
    ]);
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->scrollTo('#edit-field-hs-page-components-add-more-browse');
    $I->click('Add Component', '#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Collection');
    $I->click('field_hs_page_components_hs_collection_add_more');
    $I->waitForText('Items Per Row');
    $I->scrollTo('#edit-field-hs-page-components-add-more-browse');
    $I->click('Add Component', '#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Postcard');
    $I->click('field_hs_page_components_hs_postcard_add_more');
    $I->waitForText('Card Title');
    $card_title = $this->faker->words(3, TRUE);
    $I->fillField('Card Title', $card_title);
    $I->cantSeeElement('.hs-duplicated');
    $I->click('Toggle Actions', '.paragraph-type--hs-postcard');
    $I->click('Duplicate', '.paragraph-type--hs-postcard');
    $I->waitForText('Card Title', 10, '.hs-duplicated');
    $I->canSeeInField('Card Title', $card_title);
  }

  /**
   * I can create a page with a hero banner.
   */
  public function testHeroParagraph(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    // Prevent JS alerts from firing before loading a new page.
    $I->executeJS('window.onbeforeunload = undefined;');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Add Component');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Hero');
    $I->scrollTo('.field-add-more-submit');
    $I->click('field_hs_page_components_hs_hero_image_add_more');
    $I->waitForText('No media items are selected');
    $I->canSee('Overlay Details');
    $I->cantSee('Optionally add some overlay text on top of the image');
    $I->cantSee('Body');
    $I->cantSee('Link text');
    $I->cantSee('Overlay Color');
    $I->click('Add Media', '.media-library-open-button');
    $I->waitForText('Drop files here to upload them');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Decorative Image');
    $I->click('Save and insert');
    $I->waitForElementNotVisible('.media-library-widget-modal');
    $I->waitForText('logo.jpg');
    $I->click('//details[@data-drupal-selector="edit-field-hs-page-components-widget-1-subform-group-overlay-details"]');
    $I->waitForText('Body');
    $I->canSee('Link text');
    $I->cantSee('Overlay Color');
    $I->fillField('field_hs_page_components[1][subform][field_hs_hero_title][0][value]', 'Overlay Title');
    $I->fillField('URL', 'http://google.com');
    $I->fillField('Link text', 'Google CTA');
    $I->click('Save');
    $I->canSeeNumberOfElements('#main-content img', 1);
    $I->canSee('Overlay Title');
    $I->canSee('Google CTA', 'a');
  }

  /**
   * Create a photo album page.
   */
  public function testPhotoAlbum(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/structure/types/manage/hs_basic_page/fields/node.hs_basic_page.field_hs_page_components');
    $this->disableCollection = (bool) $I->grabAttributeFrom('[name="settings[handler_settings][target_bundles_drag_drop][stanford_gallery][enabled]"]', 'checked');
    if ($this->disableCollection) {
      $I->uncheckOption('Photo Album');
      $I->click('Save settings');
    }

    $I->amOnPage('/node/add/hs_basic_page');
    // Prevent JS alerts from firing before loading a new page.
    $I->executeJS('window.onbeforeunload = undefined;');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Add Component');
    $I->waitForText('Browse');
    $I->fillField('Search', 'Photo Album');
    $I->click('field_hs_page_components_stanford_gallery_add_more');
    $I->waitForText('Headline');
    $I->fillField('Headline', 'Photo Album Headline');
    $I->click('Add media', '.field--name-su-gallery-images');
    $I->waitForText('Add or select media');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Decorative Image');
    $I->click('Save and insert', '.ui-dialog-buttonset');
    $I->waitForElementNotVisible('.media-library-widget-modal');
    $I->waitForElementVisible('.media-library-item__preview img');
    $I->executeJS('window.scrollTo(0,0);');
    $I->click('Save');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Photo Album Headline', 'h2');
    $I->canSeeNumberOfElements('.su-gallery-images img', 1);
    $I->canSeeNumberOfElements('#cboxContent img', 0);

    $I->click('Edit', '.tabs');
    $I->click('field_hs_page_components_1_edit');
    $I->waitForText('Description');
    $I->click('Behavior');
    $I->waitForText('Display Mode');
    $I->selectOption('Display Mode', 'Slideshow');
    $I->executeJS('window.scrollTo(0,0);');
    $I->click('Save');
    $I->waitForText('Demo Basic Page');
    $I->canSeeNumberOfElements('.slick img', 1);
  }

  /**
   * Verify main menu links at mobile size
   */
  public function testMobileMenu(FunctionalTester $I) {
    // Check standard menu item links
    $I->amOnPage('/');
    $I->resizeWindow(800, 1100);
    $I->seeElement('.hb-main-nav');

    // This try/catch keeps the toggle consistent between environment testing.
    // It will check for the visible element and continue steps for either scenario.
    try {
      $I->waitForElementVisible('.hb-main-nav__link');
      // Continue to do this if it's present.
      $I->seeElement('.hb-main-nav__link');
      $I->click('.hb-main-nav__link');
      echo('If you see this, the menu was open and the link was clicked.');
    }
    catch (\Exception $e) {
      // Do this if it's not present.
      echo('If you see this, the menu needs toggled.');
      $I->click('button.hb-main-nav__toggle');
      $I->waitForElementVisible('.hb-main-nav__link');
      $I->seeElement('.hb-main-nav__link');
      $I->click('.hb-main-nav__link');
    }

    // This try/catch keeps the toggle consistent between environment testing.
    // Check nested menu item links
    try {
      echo('If you see this, the nested menu link was already available to click.');
      $I->waitForElementVisible('.hb-main-nav__menu-lv2');
      // Click nested menu link if it's already visible.
      $I->click('.hb-main-nav__menu-lv2 a');
    }
    catch (\Exception $e) {
      // Do this if the nested menu link is not already visible.
      echo('If you see this, the nested menu link needs to be opened to click.');
      $I->click('.hb-main-nav__toggle');
      $I->waitForElementVisible('.hb-nested-toggler');
      $I->click('.hb-nested-toggler');
      $I->waitForElementVisible('.hb-main-nav__menu-lv2');
      $I->click('.hb-main-nav__menu-lv2 a');
    }

    // Check standard menu item links for logged in users
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->click('.hb-main-nav__toggle');
    $I->seeElement('.hb-main-nav__menu');
    $I->click('.hb-main-nav__link');
  }

  /**
   * I can create a page with a spotlight slider.
   */
  // public function testSpotlightSlider(FunctionalTester $I) {
    // $I->logInWithRole('contributor');
    // $I->amOnPage('node/add/hs_basic_page');
    // $I->fillField('Title', $this->faker->words(3, TRUE));
    // $I->click('List additional actions', '#edit-field-hs-page-hero-add-more');
    // $I->click('field_hs_page_hero_hs_sptlght_slder_add_more');
    // $I->waitForText('No media items are selected');
    // $I->canSee('Title');
    // $I->canSee('Height');
    // $I->canSee('Background Color');
    // $I->canSee('Image Alignment');
    // $I->canSee('Body');

    // Populating spotlight #1.
    // $I->click('Add media', '.paragraph-type--hs-sptlght-slder');
    // $I->waitForText('Add or select media');
    // $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    // $I->click('Upload and Continue');
    // $I->waitForText('Decorative Image');
    // $I->click('Save and insert', '.ui-dialog-buttonset');
    // $I->waitForElementNotVisible('.media-library-widget-modal');
    // $I->waitForText('The maximum number of media items have been selected');
    // $I->waitForText('HTML');
    // $I->click('.ck-source-editing-button.ck-off');
    // $I->fillField('.ck-source-editing-area textarea', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>');
    // $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][0][subform][field_hs_spotlight_link][0][uri]', 'http://google.com');
    // $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][0][subform][field_hs_spotlight_link][0][title]', 'Google Link');
    // $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][0][subform][field_hs_spotlight_title][0][value]', 'Spotlight #1 Title');

    // Populating spotlight #2.
    // $I->scrollTo('.paragraphs-add-wrapper');
    // $I->click('Add Spotlight');
    // $I->wait(1);
    // $I->click('Add media', 'div[data-drupal-selector="edit-field-hs-page-hero-0-subform-field-hs-sptlght-sldes-1"]');
    // $I->waitForText('Add or select media');
    // $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    // $I->click('Upload and Continue');
    // $I->waitForText('Decorative Image');
    // $I->selectOption("input", 'Add new');
    // $I->click('Save and insert', '.ui-dialog-buttonset');
    // $I->waitForElementNotVisible('.media-library-widget-modal');
    // $I->waitForText('The maximum number of media items have been selected');
    // $I->waitForText('HTML');
    // $I->scrollTo('.paragraph-type--hs-spotlight.even .field--type-text-long', 0, -300);
    // $I->click('.paragraph-type--hs-spotlight.even .ck-source-editing-button.ck-off ');
    // $I->fillField('.ck-source-editing-area textarea', '<p>Aliquet porttitor lacus luctus accumsan tortor posuere ac.</p>');
    // $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][1][subform][field_hs_spotlight_link][0][uri]', 'http://yahoo.com');
    // $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][1][subform][field_hs_spotlight_link][0][title]', 'Yahoo Link');
    // $I->fillField('field_hs_page_hero[0][subform][field_hs_sptlght_sldes][1][subform][field_hs_spotlight_title][0][value]', 'Spotlight #2 Title');
    // $I->wait(2);
    // $I->click('Save');

    // Check spotlight 1.
    // $I->waitForText('Spotlight #1 Title');
    // $I->canSee('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');
    // $I->canSee('Google Link', 'a');
    // $I->canSeeNumberOfElements('picture img', 1);

    // Check spotlight 2.
    // $I->click('.slick-next');
    // $I->waitForText('Spotlight #2 Title');
    // $I->canSee('Aliquet porttitor lacus luctus accumsan tortor posuere ac.');
    // $I->canSee('Yahoo Link', 'a');
    // $I->canSeeNumberOfElements('picture img', 1);
  // }

  /**
   * I can find appropriate aria attributes on a timeline item.
   */
  public function testVerticalTimeline(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('node/add/hs_basic_page');
    $I->fillField('Title', $this->faker->words(3, TRUE));
    $I->click('Add Component');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Vertical Timeline');
    $I->click('field_hs_page_components_hs_timeline_add_more');
    $I->waitForText('Collapse by default');
    $I->checkOption('Collapse by default');
    $I->fillField('field_hs_page_components[1][subform][field_hs_timeline][0][subform][field_hs_timeline_item_summary][0][value]', 'Timeline Item #1 Title');
    $I->click('.ck-source-editing-button.ck-off ');
    $I->fillField('.ck-source-editing-area textarea', '<p>Timeline item #1 description.</p>');
    $I->click('Add Timeline Item');
    $I->wait(1);
    $I->fillField('field_hs_page_components[1][subform][field_hs_timeline][1][subform][field_hs_timeline_item_summary][0][value]', 'Timeline Item #2 Title');
    $I->click('Save');
    $I->canSee('Timeline Item #1 Title');
    $I->canSee('Timeline Item #2 Title');

    // Check aria attributes for first item.
    $this->firstItemAriaPressed = $I->grabAttributeFrom('.hb-timeline-item__summary:first-child', 'aria-pressed');
    $I->assertFalse(filter_var($this->firstItemAriaPressed, FILTER_VALIDATE_BOOL), 'Aria-pressed should be false in the first item.');
    $this->firstItemAriaExpanded = $I->grabAttributeFrom('.hb-timeline-item__summary:first-child', 'aria-expanded');
    $I->assertFalse(filter_var($this->firstItemAriaExpanded, FILTER_VALIDATE_BOOL), 'arria-expanded should be false in the first item');

    // Check aria attributes for second item.
    $this->secondItemAriaPressed = $I->grabAttributeFrom('.hb-timeline-item__summary:last-child', 'aria-pressed');
    $I->assertFalse(filter_var($this->secondItemAriaPressed, FILTER_VALIDATE_BOOL), 'Aria-pressed should be false in the second item.');
    $this->secondItemAriaExpanded = $I->grabAttributeFrom('.hb-timeline-item__summary:last-child', 'aria-expanded');
    $I->assertFalse(filter_var($this->secondItemAriaExpanded, FILTER_VALIDATE_BOOL), 'Aria-expanded should be false in the second item.');

    // Open first summary.
    $I->click('.hb-timeline-item__summary:first-child');
    $this->firstItemAriaPressed = $I->grabAttributeFrom('.hb-timeline-item__summary:first-child', 'aria-pressed');
    $I->assertTrue(filter_var($this->firstItemAriaPressed, FILTER_VALIDATE_BOOL), 'Aria-pressed should be true in the first item.');
    $this->firstItemAriaExpanded = $I->grabAttributeFrom('.hb-timeline-item__summary:first-child', 'aria-expanded');
    $I->assertTrue(filter_var($this->firstItemAriaExpanded, FILTER_VALIDATE_BOOL), 'Aria-expanded should be true in the first item.');
    $I->canSee('Timeline item #1 description.');
  }

  /**
   * I can create a postcard on the page.
   */
  public function testPostCard(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('Add Component');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Postcard');
    $I->click('field_hs_page_components_hs_postcard_add_more');
    $I->waitForText('Card Title');
    $I->canSee('Card Body');
    $I->canSee('Read More Link');
    $I->fillField('Card Title', 'Nam at tortor in tellus');
    $I->fillField('.ck-editor__editable_inline', 'Maecenas vestibulum mollis diam.');
    $I->fillField('URL', 'http://google.com');
    $I->fillField('Link text', 'Praesent egestas tristique nibh');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');

    $I->canSee('Nam at tortor in tellus', 'h2');
    $I->canSee('Maecenas vestibulum mollis diam.');
    $I->canSeeLink('Praesent egestas tristique nibh', 'http://google.com');
  }

  /**
   * I can create an accordion on the page.
   */
  public function testAccordion(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'accordion');
    $I->click('field_hs_page_components_hs_accordion_add_more');
    $I->waitForText('Summary');
    $I->fillField('Summary', 'Sed augue ipsum egestas nec');
    $I->fillField('.ck-editor__editable_inline', 'Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Sed augue ipsum egestas nec');
    $I->click('Sed augue ipsum egestas nec');
    $I->canSee('Vivamus in erat ut urna cursus vestibulum');
  }

  /**
   * I can add a Back To Top Block.
   */
  public function testBackToTopExists(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Back To Top');
    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'text area');
    $I->click('field_hs_page_components_hs_text_area_add_more');
    $I->waitForText('Text format');
    $I->fillField('.ck-editor__editable_inline',
'Sit aliquid minus autem iste labore Quos repellendus voluptas laborum atque incidunt quis. Facilis voluptates nemo ducimus facilis inventore. Fugit quod maiores et placeat modi error Voluptates recusandae facilis minus soluta minima illo Eligendi velit minus animi mollitia quisquam fuga? Ducimus eligendi in praesentium placeat unde Iure totam id inventore doloremque optio Accusamus nesciunt adipisci praesentium provident repellendus Pariatur quam quos dolorem porro rem provident. Natus fuga dolor sunt tenetur debitis? Alias exercitationem fuga impedit nihil facilis ab nam rerum, nam! Minus optio repellendus nesciunt repudiandae maxime. Iure vel sapiente dignissimos accusantium eius Expedita veniam error distinctio deserunt iusto Eius omnis impedit odio delectus recusandae Voluptatum id a repellendus ab illum Labore dignissimos nihil corporis nemo fuga Sit natus odit facilis vitae numquam! Voluptatum doloremque quis voluptate dolorem possimus minus. Iure fuga expedita facilis magni temporibus Delectus odio aliquid at enim fuga? Consequuntur quaerat quia fuga eum earum Accusamus distinctio provident non debitis vero Quos ad a mollitia veritatis natus eius eius. Quisquam ad fugiat rem libero saepe Ipsam nam laboriosam ullam accusamus aspernatur Quasi est fugiat veritatis distinctio facilis Voluptatem enim velit qui maxime culpa mollitia magni Ipsa cupiditate in dolores velit dignissimos nemo. Commodi repellendus officia dolor accusamus');
    $I->click('Save');
    $I->click('Layout', '.tabs');
    $I->canSee('Add Block', 'a');
    $I->click('Add block');
    $I->waitForText('Choose a block');
    $I->fillField('.js-layout-builder-filter', 'back to top');
    $I->waitForText('Back To Top Block');
    $I->click('Back To Top Block');
    $I->waitForText('Configure block');
    $I->click('Add block');
    $I->click('Save layout');
    $I->seeElement('.hs-back-to-top');
  }

  /**
   * I can create a text area on the page.
   */
  public function testTextArea(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'text area');
    $I->click('field_hs_page_components_hs_text_area_add_more');
    $I->waitForText('Text format');
    $I->fillField('.ck-editor__editable_inline', 'Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Vivamus in erat ut urna cursus vestibulum');
  }


  /**
   * I can create a collection of items and display them in 2, 3 or 4 per row.
   */
  public function testCollections(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/structure/types/manage/hs_basic_page/fields/node.hs_basic_page.field_hs_page_components');
    $this->disableCollection = (bool) $I->grabAttributeFrom('[name="settings[handler_settings][target_bundles_drag_drop][hs_collection][enabled]"]', 'checked');
    if ($this->disableCollection) {
      $I->uncheckOption('Collection');
      $I->click('Save settings');
    }

    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Collection');
    $I->click('field_hs_page_components_hs_collection_add_more');
    $I->waitForText('Items Per Row');
    $I->canSeeNumberOfElements('[data-drupal-selector="edit-field-hs-page-components-1-subform-field-hs-collection-per-row"] option', 4);
    $I->selectOption('Items Per Row', 2);
    $I->canSeeOptionIsSelected('Style', '- None -');
    $I->click('Add Text Area', '[data-drupal-selector="edit-field-hs-page-components-1-subform-field-hs-collection-items"]');
    $I->fillField('.ck-editor__editable_inline', 'Foo Bar Baz');
    $I->click('Add Postcard', '[data-drupal-selector="edit-field-hs-page-components-1-subform-field-hs-collection-items"]');
    $I->fillField('Card Title', 'Demo card title');
    $I->fillField('.ck-editor__editable_inline', 'Bar Foo Baz');
    $I->click('Save');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Foo Bar Baz', '.item-per-row--2');
    $I->canSee('Demo card title', '.item-per-row--2 h2');
    $I->canSee('Bar Foo Baz', '.item-per-row--2');
  }

}
