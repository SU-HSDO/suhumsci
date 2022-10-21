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

    $config_page = \Drupal::service('config_pages.loader');

    $I->logInWithRole('administrator');
    $top_level = $I->createEntity([
      'title' => 'Top Level Page',
      'type' => 'hs_basic_page',
    ]);

    $I->amOnPage($top_level->toUrl('edit-form')->toString());
    $I->click('.menu-link-form summary');
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', 'Top Level Page');
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
    $I->click('Show row weights');
    $I->click('Save');

    $config_page->load('hs_site_options')->set('field_en_mega_menu', 1)->save();
    drupal_flush_all_caches();
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

  }
}

