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
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/config/site-options');
    $I->see('Enable New Mega Menu');

    $this->megaMenuEnabled = (bool) $I->grabAttributeFrom('[name="field_en_mega_menu[value]"]', 'checked');
    if (!$this->megaMenuEnabled) {
      $I->checkOption('Enable New Mega Menu');
      $I->click('Save');
      drupal_flush_all_caches();
    }
  }

  /**
   * Every main menu item should not error.
   */
  public function testMegaMenu(FunctionalTester $I) {

    $topLevelTitle = $this->faker->words(3, TRUE);
    $secondLevelTitle = $this->faker->words(3, TRUE);

    $I->logInWithRole('administrator');
    $top_level = $I->createEntity([
      'title' => $topLevelTitle,
      'type' => 'hs_basic_page',
    ]);

    $I->amOnPage($top_level->toUrl('edit-form')->toString());
    $I->click('.menu-link-form summary');
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $topLevelTitle);
    $I->scrollTo(['css' => '.form-submit']);
    $I->click('Save');

    $second_level = $I->createEntity([
      'title' => $secondLevelTitle,
      'type' => 'hs_basic_page',
    ]);
    $I->amOnPage($second_level->toUrl('edit-form')->toString());
    $I->click('.menu-link-form summary');
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $secondLevelTitle);
    $I->scrollTo(['css' => '.form-item-menu-menu-parent'], 0, -100);
    $I->wait(2);
    $I->selectOption('Parent link', "-- {$topLevelTitle}");
    $I->waitForText('Show row weights');
    $I->click('Show row weights');
    $I->scrollTo(['css' => '.form-submit']);
    $I->click('Save');

    $I->amOnPage('/admin/structure/menu/manage/main');
    $I->see($topLevelTitle);
    $I->see($secondLevelTitle);

    // Desktop Testing
    $I->amOnPage('/user/logout');
    $I->amOnPage('/');
    $I->see($topLevelTitle, '.js-megamenu__toggle');
    $I->click($topLevelTitle);
    $I->see($secondLevelTitle, '.megamenu__link');
    $I->click($topLevelTitle);
    $I->dontSeeElement($secondLevelTitle);
    $I->click($topLevelTitle);
    $I->see($secondLevelTitle, '.megamenu__link');
    $I->click($secondLevelTitle);

    // Mobile Testing
    $I->resizeWindow(800, 600);
    $I->click('Menu', '.js-megamenu');
    $I->waitForText($topLevelTitle);

    $I->see($topLevelTitle, '.js-megamenu__toggle');
    $I->scrollTo(['css' => '.js-megamenu']);
    $I->click($topLevelTitle);

    $I->see($secondLevelTitle, '.megamenu__link');
    $I->click($topLevelTitle);

    $I->dontSeeElement($secondLevelTitle);
    $I->click($topLevelTitle);
    $I->click($secondLevelTitle);
  }

  public function _after(FunctionalTester $I) {
    // Turn off MegaMenu
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/config/site-options');
    $I->see('Enable New Mega Menu');

    $I->uncheckOption('Enable New Mega Menu');
    $I->click('Save');
    $I->resizeWindow(2000, 1400);
    drupal_flush_all_caches();
  }
}

