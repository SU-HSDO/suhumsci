<?php

use Codeception\Util\Locator;
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
    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Collection');
    $I->click('field_hs_page_components_hs_collection_add_more');
    $I->waitForText('Items Per Row');
    $I->click('.dropbutton__toggle');
    $I->scrollTo('.add-more-button-hs-postcard');
    $I->click('Add Postcard');
    $I->waitForText('No media items are selected.');
    $card_title = $this->faker->words(3, TRUE);
    $I->fillField('field_hs_page_components[0][subform][field_hs_collection_items][0][subform][field_hs_postcard_title][0][value]', $card_title);
    $I->cantSeeElement('.hs-duplicated');
    $I->click('Toggle Actions', '.paragraph-type--hs-postcard');
    $I->click('Duplicate', '.paragraph-type--hs-postcard');
    $I->waitForText('Title', 10, '.hs-duplicated');
    $I->canSeeInField('Title', $card_title);
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
    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Hero');
    $I->scrollTo('.field-add-more-submit');
    $I->click('field_hs_page_components_hs_hero_image_add_more');
    $I->waitForText('No media items are selected');
    $I->click('field_hs_hero_image-media-library-open-button-field_hs_page_components-1-subform');
    $I->waitForText('Drop files here to upload them');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Decorative Image');
    $I->waitForText('Save and insert');
    $I->click('Save and insert', '.ui-dialog-buttonset');
    $I->waitForElementNotVisible('.media-library-widget-modal');
    $I->waitForText('logo.jpg');
    $I->waitForText('Body');
    $I->canSee('Link text');
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
    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('Search', 'Photo Album');
    $I->click('field_hs_page_components_stanford_gallery_add_more');
    $I->waitForText('No media items are selected.');
    $I->fillField('field_hs_page_components[1][subform][su_gallery_headline][0][value]', 'Photo Album Headline');
    $I->scrollTo('.js-media-library-selection');
    $I->click('su_gallery_images-media-library-open-button-field_hs_page_components-1-subform');
    $I->waitForText('Add or select media');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Decorative Image');
    $I->click('Save and insert', '.ui-dialog-buttonset');
    $I->waitForElementNotVisible('.media-library-widget-modal');
    $I->waitForElementVisible('.media-library-item__preview img');
    $I->executeJS('window.scrollTo(0,0);');
    $I->click('#field-hs-page-components-add-more-wrapper > ul:nth-child(1) > li:nth-child(2)');
    $I->waitForText('Display Mode');
    $I->click('Save');

    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Photo Album Headline', 'h2');
    $I->canSeeNumberOfElements('.su-gallery-images img', 1);
    $I->canSeeNumberOfElements('#cboxContent img', 0);
    $I->waitForText('Edit');
    $I->click('Edit', '.tabs');
    $I->click('field_hs_page_components_1_edit');
    $I->waitForText('Content');
    $I->scrollTo('Style');
    $I->selectOption('Style', 'Slideshow');
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
  public function testSpotlightSlider(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('node/add/hs_basic_page');
    $node = $I->createEntity([
      'title' => $this->faker->words(3, TRUE),
      'type' => 'hs_basic_page',
    ]);
    $I->amOnPage($node->toUrl('edit-form')->toString());

    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Spotlight - Slider');
    $I->click('field_hs_page_components_hs_sptlght_slder_add_more');
    $I->waitForText('No media items are selected');
    $I->scrollTo('.paragraph-type--hs-sptlght-slder');
    $I->click('Save');

    // Populating spotlight #1.
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->click('field_hs_page_components_0_edit');
    $I->waitForText('Slides');
    $I->fillField('.ck-editor__editable_inline', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');
    $I->fillField('field_hs_page_components[0][subform][field_hs_sptlght_sldes][0][subform][field_hs_spotlight_link][0][uri]', 'http://google.com');
    $I->fillField('field_hs_page_components[0][subform][field_hs_sptlght_sldes][0][subform][field_hs_spotlight_link][0][title]', 'Google Link');
    $I->fillField('field_hs_page_components[0][subform][field_hs_sptlght_sldes][0][subform][field_hs_spotlight_title][0][value]', 'Spotlight #1 Title');
    $I->click('Add media', '.paragraph-type--hs-sptlght-slder');
    $I->waitForText('Add or select media');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Decorative Image');
    $I->click('Save and insert', '.ui-dialog-buttonset');
    $I->waitForElementNotVisible('.media-library-widget-modal');
    $I->waitForText('The maximum number of media items have been selected');
    $I->click('Save');

    // Check spotlight 1.
    $I->waitForText('Spotlight #1 Title');
    $I->canSee('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');
    $I->canSee('Google Link', 'a');
    $I->canSeeNumberOfElements('picture img', 1);
    // Uploaded spotlight image does not have alt text.
    $I->seeElement('picture img', ['alt' => '']);

    // // Populating spotlight #2.
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->click('field_hs_page_components_0_edit');
    $I->waitForText('Slides');
    $I->click('field_hs_page_components_0_subform_field_hs_sptlght_sldes_0_collapse');
    $I->wait(1);
    $I->scrollTo('.field-add-more-submit');
    $I->click('field_hs_page_components_0_subform_field_hs_sptlght_sldes_hs_spotlight_add_more');

    $I->waitForText('No media items are selected');
    $I->fillField('.ck-editor__editable_inline', 'Aliquet porttitor lacus luctus accumsan tortor posuere ac.');
    $I->fillField('field_hs_page_components[0][subform][field_hs_sptlght_sldes][1][subform][field_hs_spotlight_link][0][uri]', 'http://yahoo.com');
    $I->fillField('field_hs_page_components[0][subform][field_hs_sptlght_sldes][1][subform][field_hs_spotlight_link][0][title]', 'Yahoo Link');
    $I->fillField('field_hs_page_components[0][subform][field_hs_sptlght_sldes][1][subform][field_hs_spotlight_title][0][value]', 'Spotlight #2 Title');

    $I->click('field_hs_spotlight_image-media-library-open-button-field_hs_page_components-0-subform-field_hs_sptlght_sldes-1-subform');
    $I->waitForText('Add or select media');
    $I->dropFileInDropzone(dirname(__FILE__, 3) . '/logo.jpg');
    $I->click('Upload and Continue');
    $I->waitForText('Decorative Image');
    $I->selectOption("input", 'Add new');
    $I->click('Save and insert', '.ui-dialog-buttonset');
    $I->waitForElementNotVisible('.media-library-widget-modal');
    $I->waitForText('The maximum number of media items have been selected');
    $I->click('Save');

    // Check spotlight 2.
    $I->click('.slick-next');
    $I->waitForText('Spotlight #2 Title');
    $I->canSee('Aliquet porttitor lacus luctus accumsan tortor posuere ac.');
    $I->canSee('Yahoo Link', 'a');
    $I->canSeeNumberOfElements('picture img', 1);
    // Uploaded spotlight image does not have alt text.
    $I->seeElement('picture img', ['alt' => '']);
  }

  /**
   * I can find appropriate aria attributes on a timeline item.
   */
  public function testVerticalTimeline(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('node/add/hs_basic_page');
    $I->fillField('Title', $this->faker->words(3, TRUE));
    $I->click('#edit-field-hs-page-components-add-more-browse');
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
    $I->waitForText('Timeline Item #1 Title');
    $I->waitForText('Timeline Item #2 Title');

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
    $I->waitForText('Timeline item #1 description.');
  }

  /**
   * I can create a postcard on the page.
   */
  public function testPostCard(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    $I->click('#edit-field-hs-page-components-add-more-browse');
    $I->waitForText('Browse');
    $I->fillField('pb_modal_text', 'Postcard');
    $I->click('field_hs_page_components_hs_postcard_add_more');
    $I->waitForText('No media items are selected.');
    $I->fillField('field_hs_page_components[1][subform][field_hs_postcard_title][0][value]', 'Nam at tortor in tellus');
    $I->fillField('.ck-editor__editable_inline', 'Maecenas vestibulum mollis diam.');
    $I->fillField('URL', 'http://google.com');
    $I->fillField('Link text', 'Praesent egestas tristique nibh');
    $I->click('Save');

    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->waitForText('Nam at tortor in tellus');
    $I->waitForText('Maecenas vestibulum mollis diam.');
    $I->seeElement(Locator::href('http://google.com'));
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
    $I->click('.field-hs-accordion-summary');
    $I->canSee('Vivamus in erat ut urna cursus vestibulum');
  }

  /**
   * I can add a Back To Top Block.
   */
  public function testBackToTopExists(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Back To Top');
    try {
      // Use existing text area component.
      $I->canSee('Text format');
    }
    catch (\Exception $e) {
      // Add component if does not already exist.
      $I->click('#edit-field-hs-page-components-add-more-browse');
      $I->waitForText('Browse');
      $I->fillField('pb_modal_text', 'text area');
      $I->click('field_hs_page_components_hs_text_area_add_more');
      $I->waitForText('Text format');
    }
    $I->fillField('.ck-editor__editable_inline', $this->faker->paragraphs(10, TRUE));
    $I->click('Save');

    $I->click('Layout', '.tabs');
    $I->scrollTo('.layout-builder__link--add');
    $I->canSee('Add Block', 'a');
    $I->click('Add block');
    $I->waitForText('Choose a block');
    $I->fillField('.js-layout-builder-filter', 'back to top');
    $I->waitForText('Back To Top Block');
    $I->click('Back To Top Block');
    $I->waitForText('Configure block');
    $I->click('Add block');
    $I->waitForElementNotVisible('.ui-dialog-position-side');
    $I->executeJS('window.scrollTo(0,0);');
    $I->wait(1);
    $I->click('Save layout');
    $I->waitForText('Back To Top');
    $I->executeJS('window.scrollTo(0,document.body.scrollHeight);');
    $I->waitForElement('.hs-back-to-top');
  }

  /**
   * I can create a text area on the page.
   */
  public function testTextArea(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Demo Basic Page');
    try {
      // Use existing text area component.
      $I->canSee('Text Area');
    }
    catch (\Exception $e) {
      // Add component if does not already exist.
      $I->click('#edit-field-hs-page-components-add-more-browse');
      $I->waitForText('Browse');
      $I->fillField('pb_modal_text', 'text area');
      $I->click('field_hs_page_components_hs_text_area_add_more');
      $I->waitForText('Text Area');
    }
    $I->cantSee('Text format');
    $paragraph = $this->faker->paragraphs(1, TRUE);
    $I->fillField('.ck-editor__editable_inline', $paragraph);
    $I->click('Save');

    $I->canSeeInCurrentUrl('/demo-basic-page');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee($paragraph);
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
    $I->canSeeOptionIsSelected('Background Color', '- None -');
    $I->click('.dropbutton__toggle');
    $I->click('.add-more-button-hs-text-area');
    $I->scrollTo('.dropbutton__toggle');
    $I->waitForText('Text format');
    $I->fillField('.ck-editor__editable_inline:nth-child(1)', 'Foo Bar Baz');
    $I->scrollTo('.dropbutton__toggle');
    $I->click('.dropbutton__toggle');
    $I->click('.add-more-button-hs-postcard');
    $I->scrollTo('.dropbutton__toggle');
    $I->waitForText('No media items are selected.');
    $I->scrollTo('.field--name-field-hs-postcard-body');
    $I->fillField('field_hs_page_components[1][subform][field_hs_collection_items][1][subform][field_hs_postcard_title][0][value]', 'Demo card title');
    $I->fillField('.ck-editor__editable_inline:nth-child(1)', 'Bar Foo Baz');
    $I->click('Save');
    $I->canSee('Demo Basic Page', 'h1');
    $I->canSee('Foo Bar Baz', '.item-per-row--2');
    $I->canSee('Demo card title', '.item-per-row--2 h2');
    $I->canSee('Bar Foo Baz', '.item-per-row--2');
  }

}
