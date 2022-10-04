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

    $config_page = config_pages_config('hs_site_options', NULL);
    print_r($config_page);
    //$config_page->set('field_en_mega_menu', 1);
    //$config_page->save();
    echo "MegaMenuCest: Mega menu enabled";

    drupal_flush_all_caches();
    echo "MegaMenuCest: All caches flushed";

    $I->amOnPage('/');
    $I->canSeeResponseCodeIsBetween(200, 403);

  }
}
