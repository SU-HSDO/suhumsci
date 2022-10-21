<?php

use Drupal\Core\Url;

/**
 * Class MegaMenuCest.
 *
 * @group install
 */
class MegaMenuCest {

  /**
   * Every main menu item should not error.
   */
  public function testMegaMenu(FunctionalTester $I) {

    $I->logInWithRole('administrator');
    $top_level = $I->createEntity([
      'title' => 'Top Level Page',
      'type' => 'hs_basic_page',
    ]);

    $I->amOnPage($top_level->toUrl('edit-form')->toString());
    $I->click('.menu-link-form summary');
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', 'Top Level Page');
    $I->scrollTo(['css' => '.form-submit']);
    $I->click('Save');

    $second_level = $I->createEntity([
      'title' => 'Second Level Page',
      'type' => 'hs_basic_page',
    ]);
    $I->amOnPage($second_level->toUrl('edit-form')->toString());
    $I->click('.menu-link-form summary');
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', 'Second Level Page');
    $I->scrollTo(['css' => '.form-item-menu-menu-parent'], 0, -100);
    $I->selectOption('Parent link', '-- Top Level Page');
    $I->wait(2);
    $I->click('Show row weights');
    $I->makeScreenshot('second_page');
    $I->scrollTo(['css' => '.form-submit']);
    $I->click('Save');

    $I->amOnPage('/admin/structure/menu/manage/main');
    $I->see('Top Level Page');
    $I->see('Second Level Page');
    $I->makeScreenshot('menu_page');

    $I->amOnPage('/admin/config/site-options');
    $I->see('Enable New Mega Menu');

    $this->megaMenuEnabled = (bool) $I->grabAttributeFrom('[name="field_en_mega_menu[value]"]', 'checked');
    if (!$this->megaMenuEnabled) {
      $I->checkOption('#edit-field-en-mega-menu-value');
      $I->click('Save');
      drupal_flush_all_caches();
    }

    $I->amOnPage('/user/logout');
    $I->amOnPage('/');
    $I->see('Top Level Page', '.js-megamenu__toggle');
    $I->click('Top Level Page');
    $I->wait(1);
    $I->see('Second Level Page', '.megamenu__link');
    $I->click('Top Level Page');
    $I->wait(1);
    $I->dontSeeElement('Second Level Page');
    $I->click('Top Level Page');
    $I->wait(1);
    $I->click('Second Level Page');

    // Mobile Testing
    $I->resizeWindow(800, 600);

    $I->click('.js-megamenu__mobile-btn');

    $I->wait(1);
    $I->scrollTo(['css' => '.js-megamenu__toggle']);

    $I->see('Top Level Page', '.js-megamenu__toggle');
    $I->scrollTo(['css' => '.js-megamenu']);
    $I->click('Top Level Page');
    $I->wait(1);

    $I->scrollTo(['css' => '.js-megamenu']);
    $I->see('Second Level Page', '.megamenu__link');
    $I->click('Top Level Page');
    $I->wait(1);
    $I->dontSeeElement('Second Level Page');
    $I->click('Top Level Page');
    $I->wait(1);
    $I->click('Second Level Page');

  }
}

