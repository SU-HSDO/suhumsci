<?php

class InstallStateCest {

  public function testDefaultContent(AcceptanceTester $I){
    $I->amOnPage('/');
    $I->canSeeResponseCodeIs(200);
    $I->canSeeElement('input[type="text"]');
    $I->canSeeElement('input[value="Search"]');
    $I->canSee('Class aptent taciti sociosqu ad litora torquent per conubia nostra');
    $I->canSee('About', 'h2');
    $I->canSee('People', 'h2');
    $I->canSee('Connect With Us', 'h2');
    $I->canSee('Contact Us', 'h2');
  }

  public function testVisibleAdminItems(AcceptanceTester $I){
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/content');
     $I->canSee('Content');
     $I->canSee('Files');
     $I->canSee('Media');
     $I->canSee('Add content');
     $I->canSee('Home Page');
    $I->amOnPage('/admin/users');
     $I->canSee('Howard');
     $I->canSee('Lindsey');
  }

  public function testContributorShortcuts(AcceptanceTester $I){
    $I->logInWithRole('contributor');
    $I->amOnPage('/');
    $I->canSeeNumberOfElements('#toolbar-item-shortcuts-tray a', 25);
  }

  public function testSiteManagerShortcuts(AcceptanceTester $I){
    $I->logInWithRole('site_manager');
 $I->amOnPage('/');
    $I->canSeeNumberOfElements('#toolbar-item-shortcuts-tray a', 29);
  }

  public function testDeveloperShortcuts(AcceptanceTester $I){
    $I->logInWithRole('administrator');
    $I->amOnPage('/');
    $I->canSeeNumberOfElements('#toolbar-item-shortcuts-tray a', 38);
  }

}
