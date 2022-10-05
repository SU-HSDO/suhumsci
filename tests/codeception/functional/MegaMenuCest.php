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
   * Every main menu item should not error.
   */
  public function testMegaMenu(FunctionalTester $I) {

    $config_page = \Drupal::service('config_pages.loader');
    if ($config_page->load('hs_site_options')) {
      $config_page->load('hs_site_options')->set('field_en_mega_menu', 1)->save();
      drupal_flush_all_caches();
      $I->amOnPage('/user/logout');
      $I->amOnPage('/');
      $I->click('.megamenu__toggle');
      $I->seeElement('.megamenu__expanded-container.is-expanded');
      $I->click('.megamenu__toggle');
      $I->dontSeeElement('.megamenu__expanded-container.is-expanded');
      $I->click('.megamenu__toggle');
      $I->click('.megamenu__link');
    }
  }
}
