<?php

use Drupal\Core\Url;
use Faker\Factory;

/**
 * Class MegaMenuCest.
 *
 * @group megaMenu
 */
class MegaMenuCest {

  /**
   * Every main menu item should not error.
   */
  public function testMegaMenu(AcceptanceTester $I) {
    $I->amOnPage('/');
    $I->canSeeResponseCodeIsBetween(200, 403);
    $megaMenuExists = false;

    try {
       $I->click('Training'); // WORKS?
       //$I->click("//button[@class='megamenu__toggle']");
       $megaMenuExists = true;
    } catch (Exception $e) {
    }
    if ($megaMenuExists) {
      echo "Megamenu was found!";
      $I->seeElement(".is-expanded");
    } else {
      echo "Element NOT found!";
    }
  }
}
