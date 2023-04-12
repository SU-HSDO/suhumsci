<?php

use Drupal\Core\Url;
use Faker\Factory;

/**
 * Class MenuItemsCest.
 *
 * @group existingSite
 */
class MenuItemsCest {

  /**
   * Faker service.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * Test generated links.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface[]
   */
  protected $menuLinks = [];

  /**
   * Test constructor.
   */
  public function __construct() {
    $this->faker = Factory::create();
  }

  /**
   * Cleanup menu links after the test.
   */
  public function _after(AcceptanceTester $I) {
    foreach ($this->menuLinks as $link) {
      $link->delete();
    }
  }

  /**
   * Every main menu item should not error.
   */
  public function testMenuItems(AcceptanceTester $I) {
    $I->amOnPage('/');
    $I->canSeeResponseCodeIsBetween(200, 403);
    $I->setMaxRedirects(5);
    foreach ($this->getLinksToCheck($I, '#header a') as $path) {
      $I->amOnPage($path);
      $I->canSeeResponseCodeIsBetween(200, 404);
    }
  }

  /**
   * Path auto settings should work correctly.
   *
   * @group pathauto
   * @group install
   */
  public function testPathAuto(AcceptanceTester $I) {
    $manual_url = '';
    while (strlen($manual_url) < 5) {
      $url = parse_url($this->faker->url);
      $manual_url = substr($url['path'], 0, strpos($url['path'], '.'));
    }

    $I->logInWithRole('administrator');
    $auto_alias = $I->createEntity([
      'title' => $this->faker->words(2, TRUE),
      'type' => 'hs_basic_page',
    ]);
    $I->amOnPage($auto_alias->toUrl('edit-form')->toString());
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $auto_alias->label());
    $I->click('Save');
    $I->canSee($auto_alias->label(), 'h1');

    $manual_alias = $I->createEntity([
      'title' => $this->faker->words(1, TRUE),
      'type' => 'hs_basic_page',
    ]);
    $I->amOnPage($manual_alias->toUrl('edit-form')->toString());
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $manual_alias->label());
    $I->uncheckOption('Generate automatic URL alias');
    $I->fillField('URL alias', $manual_url);
    $I->click('Save');

    $nolink = $I->createEntity([
      'title' => 'Foo Bar',
      'link' => 'route:<nolink>',
      'weight' => 0,
      'menu_name' => 'main',
    ], 'menu_link_content');
    $this->menuLinks[] = $nolink;

    $node = $I->createEntity([
      'title' => $this->faker->words(2, TRUE),
      'type' => 'hs_basic_page',
    ]);
    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->checkOption('Provide a menu link');
    $I->fillField('Menu link title', $node->label());
    $I->selectOption('Parent link', '-- ' . $auto_alias->label());
    $I->click('Change parent (update list of weights)');
    $I->click('Save');
    $I->canSeeInCurrentUrl($auto_alias->toUrl()->toString() . '/');

    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->selectOption('Parent link', '-- ' . $manual_alias->label());
    $I->click('Change parent (update list of weights)');
    $I->click('Save');
    $I->canSeeInCurrentUrl($manual_alias->toUrl()->toString() . '/');

    $I->amOnPage($node->toUrl('edit-form')->toString());
    $I->selectOption('Parent link', '-- ' . $nolink->label());
    $I->click('Change parent (update list of weights)');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/foo-bar/');
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
      } catch (\Exception $e) {
        return FALSE;
      }
    });
    return $link_urls;
  }

}
