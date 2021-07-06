<?php

use Drupal\Core\Url;

/**
 * Class MenuItemsCest.
 *
 * @group existingSite
 */
class MenuItemsCest {

  /**
   * Every main menu item should not error.
   */
  public function testMenuItems(AcceptanceTester $I) {
    $I->amOnPage('/');
    $I->canSeeResponseCodeIs(200);
    foreach ($this->getLinksToCheck($I, '#header a') as $path) {
      $I->amOnPage($path);
      $I->canSeeResponseCodeIsBetween(200, 404);
    }
  }

  /**
   * A site manager should be able to place a page under an unpublished page.
   */
  public function testUnpublishedMenuItems(AcceptanceTester $I){
    $I->logInWithRole('site_manager');
    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Unpublished Parent');
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', 'Unpublished Parent');
    $I->uncheckOption('Publish');
    $I->click('Save');
    $I->canSee('Unpublished Parent', 'h1');
    $I->canSee('Unpublished Parent', 'nav a[data-unpublished-node]');
    $I->canSee('Unpublished');

    $I->amOnPage('/node/add/hs_basic_page');
    $I->fillField('Title', 'Child Page');
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', 'Child Page');
    $I->selectOption('Parent link', '-- Unpublished Parent');
    $I->click('Change parent (update list of weights)');
    $I->uncheckOption('Publish');
    $I->click('Save');
    $I->canSee('Child Page', 'h1');
    $I->canSee('Child Page', 'nav a[data-unpublished-node]');
    $I->canSee('Unpublished');

    $I->click('Edit', '.tabs__tab');
    $I->click('Save');
    $I->assertEquals('/unpublished-parent/child-page', $I->grabFromCurrentUrl());
  }

  /**
   * Get all relative url paths to test.
   *
   * @param \AcceptanceTester $I
   *   Tester.
   * @param $selector
   *   Css selector.
   *
   * @return string[]
   *   Array of relative paths.
   */
  protected function getLinksToCheck(AcceptanceTester $I, string $selector): array {
    $link_urls = $I->grabMultiple($selector, 'href');

    $link_urls = array_filter($link_urls, function ($url) {
      if (preg_match('/(\/saml_login|\/user|^#)/', $url)) {
        return FALSE;
      }
      try {
        Url::fromUserInput($url);
        return TRUE;
      }
      catch (\Exception $e) {
        return FALSE;
      }
    });
    return $link_urls;
  }

}
