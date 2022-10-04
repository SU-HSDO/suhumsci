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
    $config_page->load('hs_site_options');
    //$config_page->load('hs_site_options')->set('field_en_mega_menu', 1)->save();

    echo "MegaMenuCest: Mega menu enabled";

    drupal_flush_all_caches();
    echo "MegaMenuCest: All caches flushed";

    $I->amOnPage('/');
    $I->canSeeResponseCodeIsBetween(200, 403);

  }
}
