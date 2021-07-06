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
