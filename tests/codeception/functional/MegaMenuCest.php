<?php

use Drupal\Core\Url;
use Faker\Factory;

/**
 * Class MegaMenuCest.
 *
 * @group install
 */
class MegaMenuCest {

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

  public function _before(FunctionalTester $I) {
    $I->resizeWindow(2000, 1400);
  }

  public function _after(FunctionalTester $I) {
    $I->resizeWindow(2000, 1400);
  }

  /**
   * Every main menu item should not error.
   */
  public function testMegaMenu(FunctionalTester $I) {

    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/config/site-options');
    $I->see('Enable New Mega Menu');

    $this->megaMenuEnabled = (bool) $I->grabAttributeFrom('[name="field_en_mega_menu[value]"]', 'checked');
    if (!$this->megaMenuEnabled) {
      $I->checkOption('Enable New Mega Menu');
      $I->click('Save');
      $I->runDrush('cache:rebuild');
    }

    $topLevelTitle = $this->faker->words(3, TRUE);
    $secondLevelTitle = $this->faker->words(3, TRUE);

    $top_level = $I->createEntity([
      'title' => $topLevelTitle,
      'type' => 'hs_basic_page',
    ]);

    $I->amOnPage($top_level->toUrl('edit-form')->toString());

    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $topLevelTitle);
    $I->scrollTo(['css' => '.form-submit']);
    $I->click('Save');

    $second_level = $I->createEntity([
      'title' => $secondLevelTitle,
      'type' => 'hs_basic_page',
    ]);
    $I->amOnPage($second_level->toUrl('edit-form')->toString());

    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $secondLevelTitle);

    $I->selectOption('#edit-field-menulink-0-menu-parent--level-1', $topLevelTitle);
    $I->waitForAjaxToFinish();
    $I->scrollTo(['css' => '.form-submit']);
    $I->click('Save');

    $I->amOnPage('/admin/structure/menu/manage/main');
    $I->see($topLevelTitle);
    $I->see($secondLevelTitle);

    // Desktop Testing
    $I->amOnPage('/user/logout');
    $I->amOnPage('/');
    $I->see($topLevelTitle, '.js-megamenu__toggle');

    // Open first level nav and verify second level title exists
    $I->click($topLevelTitle);
    $I->see($secondLevelTitle, '.megamenu__link');

    // Close first level nav and verify second level title does not exist
    $I->click($topLevelTitle);
    $I->dontSeeElement($secondLevelTitle);

    // Open first level nav and then click on second level link
    $I->click($topLevelTitle);
    $I->see($secondLevelTitle, '.megamenu__link');
    $I->click($secondLevelTitle);

    // Mobile Testing
    $I->resizeWindow(800, 600);
    $I->click('Menu', '.js-megamenu');
    $I->waitForText($topLevelTitle);

    // Open first level nav and verify second level title exists
    $I->see($topLevelTitle, '.js-megamenu__toggle');
    $I->scrollTo(['css' => '.js-megamenu']);
    $I->click($topLevelTitle);
    $I->see($secondLevelTitle, '.megamenu__link');

    // Close first level nav and verify second level title does not exist
    $I->click($topLevelTitle);
    $I->dontSeeElement($secondLevelTitle);

    // Open first level nav and then click on second level link
    $I->click($topLevelTitle);
    $I->see($secondLevelTitle, '.megamenu__link');
    $I->click($secondLevelTitle);

    // Turn off MegaMenu
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/config/site-options');
    $I->see('Enable New Mega Menu');

    $I->uncheckOption('Enable New Mega Menu');
    $I->click('Save');
    $I->runDrush('cache:rebuild');
  }
}
