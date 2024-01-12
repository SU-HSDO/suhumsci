<?php

use Faker\Factory;

/**
 * Class InstallStateCest.
 *
 * @group install
 */
class InstallStateCest {

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
   * Default content should be visible.
   */
  public function testDefaultContent(AcceptanceTester $I) {
    $I->amOnPage('/');
    $I->canSeeResponseCodeIs(200);
    $I->canSeeElement('input[type="text"]');
    $I->canSeeElement('input[value="Search"]');
    $I->canSee('Class aptent taciti sociosqu ad litora torquent per conubia nostra');
    $I->canSee('About', 'h2');
    $I->canSee('People', 'h2');
    $I->canSee('Connect With Us', 'h2');
    $I->canSee('Contact Us', 'h2');
  }

  /**
   * I can see some links as an admin.
   *
   * @group roles
   */
  public function testVisibleAdminItems(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/content');
    $I->canSee('Content');
    $I->canSee('Files');
    $I->canSee('Media');
    $I->canSee('Add content');
    $I->canSee('Home Page');
    $I->amOnPage('/admin/users');
    $I->canSee('Howard');
    $I->canSee('Lindsey');
  }

  /**
   * Contributor can see a certain number of shortcuts.
   *
   * @group shotcuts
   * @group roles
   */
  public function testContributorShortcuts(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/');
    $I->canSeeNumberOfElements('#toolbar-item-shortcuts-tray a', 28);
  }

  /**
   * Site Managers can see a certain number of shortcuts.
   *
   * @group shotcuts
   * @group roles
   */
  public function testSiteManagerShortcuts(AcceptanceTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/');
    $I->canSeeNumberOfElements('#toolbar-item-shortcuts-tray a', 34);
  }

  /**
   * Developers/Admins can see a certain number of shortcuts.
   *
   * @group shotcuts
   * @group roles
   */
  public function testDeveloperShortcuts(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/');
    $I->canSeeNumberOfElements('#toolbar-item-shortcuts-tray a', 38);
  }

  /**
   * A site manager should be able to place a page under an unpublished page.
   */
  public function testUnpublishedMenuItems(AcceptanceTester $I) {
    $parent_page = $I->createEntity(['type' => 'hs_basic_page', 'title' => $this->faker->words(3,TRUE)]);
    $I->logInWithRole('site_manager');
    $I->amOnPage($parent_page->toUrl('edit-form')->toString());
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $parent_page->label());
    $I->uncheckOption('Publish');
    $I->click('Save');
    $I->canSee($parent_page->label(), 'h1');
    $I->canSee($parent_page->label(), 'nav a[data-unpublished-node]');

    $child_page = $I->createEntity(['type' => 'hs_basic_page', 'title' => $this->faker->words(3,TRUE)]);
    $I->amOnPage($child_page->toUrl('edit-form')->toString());

    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $child_page->label());
    $I->selectOption('Parent item', 'main:menu_link_field:node_field_menulink_' . $parent_page->uuid() . '_und');

    $I->uncheckOption('Publish');
    $I->click('Save');
    $I->canSee($child_page->label(), 'h1');
    $I->canSee($child_page->label(), 'nav a[data-unpublished-node]');

    $I->canSeeInCurrentUrl($parent_page->toUrl()->toString());
  }

  /**
   * Test fast 404 page.
   *
   * @group fast404
   */
  public function testFast404(AcceptanceTester $I) {
    $path = $this->faker->words(2, TRUE);
    $path = preg_replace('/[^a-z]/', '-', strtolower($path));
    $I->amOnPage($path);
    $I->canSeeResponseCodeIs(404);

    $redirect_source = $this->faker->words(2, TRUE);
    $redirect_source = preg_replace('/[^a-z]/', '-', strtolower($redirect_source));

    $node = $I->createEntity([
      'type' => 'hs_basic_page',
      'title' => $this->faker->words(3, TRUE),
    ]);

    $I->createEntity([
      'redirect_source' => [
        [
          'path' => $redirect_source,
          'query' => [],
        ],
      ],
      'redirect_redirect' => [
        [
          'uri' => 'internal:/node/' . $node->id(),
          'options' => [],
        ],
      ],
      'status_code' => 301,
    ], 'redirect');
    $I->amOnPage($redirect_source);

    $I->canSeeResponseCodeIs(200);
    $I->canSeeInCurrentUrl($node->toUrl()->toString());
  }

}
