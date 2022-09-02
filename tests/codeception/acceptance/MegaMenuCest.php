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


    $I->haveVisible('.megamenu__expanded-container');

   //try {
    //$I->seeElement('.megamenu__list--main');
   // $I->click('.megamenu__toggle');
    // if($test) {
    //     echo 'Megamenu exists v2';
    //   } else {
    //     echo 'Megamenu DOES NOT exist v2';
    //   }
      //$I->click('.megamenu__toggle');
       //$I->haveVisible('dfg');
      // Continue to do this if it's present
      // ...
      //echo 'Megamenu exists';
    //} catch (Exception $e) {
      // Do this if it's not present.
      // ...

    //}

    //$I->seeElement('.megamenu__list--main');

    // foreach ($this->getLinksToCheck($I, '#header a') as $path) {
    //   $I->amOnPage($path);
    //   $I->canSeeResponseCodeIsBetween(200, 404);
    // }
  }

}
