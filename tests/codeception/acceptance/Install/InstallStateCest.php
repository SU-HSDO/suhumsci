<?php

/**
 * Class InstallStateCest.
 *
 * @group install
 */
class InstallStateCest {

  /**
   * Default content should be visible.
   */
  public function testDefaultContent(AcceptanceTester $I) {
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

  /**
   * I can see some links as an admin.
   */
  public function testVisibleAdminItems(AcceptanceTester $I) {
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

  /**
   * Contributor can see a certain number of shortcuts.
   */
  public function testContributorShortcuts(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/');
    $I->canSeeNumberOfElements('#toolbar-item-shortcuts-tray a', 25);
  }

  /**
   * Site Managers can see a certain number of shortcuts.
   */
  public function testSiteManagerShortcuts(AcceptanceTester $I) {
    $I->logInWithRole('site_manager');
    $I->amOnPage('/');
    $I->canSeeNumberOfElements('#toolbar-item-shortcuts-tray a', 29);
  }

  /**
   * Developers/Admins can see a certain number of shortcuts.
   */
  public function testDeveloperShortcuts(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/');
    $I->canSeeNumberOfElements('#toolbar-item-shortcuts-tray a', 38);
  }

}
