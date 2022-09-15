<?php

/**
 * Class PrivatePagePermissionCest.
 *
 * @group install
 */
class PrivatePagePermissionCest{

  private $rolesWithAccess = [
    'site_manager',
    'administrator',
    'contributor',
    'intranet_viewer',
  ];

  private $rolesWithoutAccess = [
    'author',
    'stanford_faculty',
    'stanford_staff',
    'stanford_student',
    'authenticated',
    'anonymous',
  ];

  /**
   * A private page should only be accessible to specific roles.
   */
  public function testPrivatePagePermissions(AcceptanceTester $I){

    // Create the private page
    $I->logInWithRole('site_manager');
    $I->amOnPage('/node/add/hs_private_page');
    $I->fillField('Title', 'Test Private Page');
    $I->click('Save');
    $I->canSee('Test Private Page','h1');
    $url = $I->grabFromCurrentUrl();
    $I->amOnPage('/user/logout');

    // Test roles with permission
    foreach ($this->rolesWithAccess as $role) {
      $I->logInWithRole($role);
      $I->amOnPage($url);
      $I->canSeeResponseCodeIs(200);
      $I->amOnPage('/user/logout');
    }

    // Test roles without permission
    foreach ($this->rolesWithoutAccess as $role) {
      $I->logInWithRole($role);
      $I->amOnPage($url);
      $I->canSeeResponseCodeIs(403);
      $I->amOnPage('/user/logout');
    }
  }
}
