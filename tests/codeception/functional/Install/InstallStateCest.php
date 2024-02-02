<?php


use Faker\Factory;

/**
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
   * A site manager should be able to place a page under an unpublished page.
   */
  public function testUnpublishedMenuItems(FunctionalTester $I) {
    $parent_page = $I->createEntity([
      'type' => 'hs_basic_page',
      'title' => $this->faker->words(3, TRUE),
      'status' => 0,
    ]);
    $I->logInWithRole('site_manager');
    $I->amOnPage($parent_page->toUrl('edit-form')->toString());
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $parent_page->label());
    $I->click('Save');
    $I->canSee($parent_page->label(), 'h1');
    $I->canSee($parent_page->label(), 'nav a[data-unpublished-node]');

    $child_page = $I->createEntity([
      'type' => 'hs_basic_page',
      'title' => $this->faker->words(3, TRUE),
      'status' => 0,
    ]);
    $I->amOnPage($child_page->toUrl('edit-form')->toString());

    $I->checkOption('Provide a menu link');
    $I->canSee('Menu link weight');
    $I->fillField('Menu link title', $child_page->label());
    $I->selectOption('#edit-field-menulink-0-menu-parent--level-1', $parent_page->label());
    $I->waitForAjaxToFinish();

    $I->click('Save');
    $I->canSee($child_page->label(), 'h1');
    $I->canSee($child_page->label(), 'nav a[data-unpublished-node]');

    $I->canSeeInCurrentUrl($parent_page->toUrl()->toString());
  }

}
