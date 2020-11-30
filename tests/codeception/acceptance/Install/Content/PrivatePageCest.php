<?php

class PrivatePageCest{

  public function testHero(AcceptanceTester $I){
    $I->logInWithRole('site_manager');
    $I->amOnPage('/node/add/hs_private_page');
    $I->fillField('Title', 'Test Private Page');
    $I->click('Save');
    $I->canSee('Test Private Page','h1');
    $url = $I->grabFromCurrentUrl();
    $I->amOnPage('/user/logout');
    $I->amOnPage($url);
    $I->canSeeResponseCodeIs(403);
  }

}
