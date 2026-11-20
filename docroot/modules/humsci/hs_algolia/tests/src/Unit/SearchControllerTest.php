<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\hs_algolia\Controller\SearchController;
use Drupal\hs_algolia\Routing\RouteSubscriber;
use Drupal\hs_algolia\SearchStatus;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Routing\ViewPageController;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the search page switching between Algolia and the Drupal view.
 */
#[Group('hs_algolia')]
class SearchControllerTest extends UnitTestCase {

  /**
   * Build the controller.
   *
   * @param bool $active
   *   Whether Algolia search is active.
   * @param \Drupal\views\Routing\ViewPageController|null $views_controller
   *   The Views controller the class resolver returns.
   * @param bool $theme_has_card
   *   Whether the default theme defines a card library.
   */
  protected function buildController(bool $active, ?ViewPageController $views_controller = NULL, bool $theme_has_card = TRUE): SearchController {
    $status = $this->createMock(SearchStatus::class);
    $status->method('isActive')->willReturn($active);
    $status->method('getClientSettings')->willReturn([
      'appId' => 'APP',
      'searchKey' => 'search-key',
      'indexName' => 'archaeology',
    ]);
    $status->method('getCacheableMetadata')->willReturnCallback(
      fn() => (new CacheableMetadata())->addCacheTags(['config:hs_algolia.settings'])
    );

    $class_resolver = $this->createMock(ClassResolverInterface::class);
    $class_resolver->method('getInstanceFromDefinition')
      ->with(ViewPageController::class)
      ->willReturn($views_controller);

    $theme_config = $this->createMock(ImmutableConfig::class);
    $theme_config->method('get')->with('default')->willReturn('humsci_colorful');
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('system.theme')->willReturn($theme_config);

    $library_discovery = $this->createMock(LibraryDiscoveryInterface::class);
    $library_discovery->method('getLibraryByName')->willReturn($theme_has_card ? ['css' => []] : FALSE);

    $controller = new SearchController($status, $class_resolver, $config_factory, $library_discovery);
    $controller->setStringTranslation($this->getStringTranslationStub());
    return $controller;
  }

  /**
   * Without Algolia search, the Drupal view answers unchanged.
   */
  public function testInactiveDelegatesToView(): void {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $views_controller = $this->createMock(ViewPageController::class);
    $views_controller->expects($this->once())
      ->method('handle')
      ->with('hs_default_search', 'search_page', $route_match)
      ->willReturn(['#markup' => 'view']);

    $build = $this->buildController(FALSE, $views_controller)->page('hs_default_search', 'search_page', $route_match);

    $this->assertSame('view', $build['#markup']);
    $this->assertContains('config:hs_algolia.settings', $build['#cache']['tags']);
  }

  /**
   * With Algolia search, the page carries the app and only the search key.
   */
  public function testActiveRendersApp(): void {
    $build = $this->buildController(TRUE)->page('hs_default_search', 'search_page', $this->createMock(RouteMatchInterface::class));

    $this->assertContains('hs-algolia-search', $build['#attributes']['class']);
    $this->assertEqualsCanonicalizing([
      'hs_algolia/search',
      'ui_patterns/horizontal_card.horizontal_card',
      'humsci_colorful/card',
    ], $build['#attached']['library']);

    $settings = $build['#attached']['drupalSettings']['hsAlgoliaSearch'];
    $this->assertSame('search-key', $settings['searchKey']);
    $this->assertSame('search', $settings['queryParam']);
    $this->assertSame(SearchController::HITS_PER_PAGE, $settings['hitsPerPage']);
    $this->assertArrayNotHasKey('api_key', $settings);
    $this->assertContains('config:hs_algolia.settings', $build['#cache']['tags']);
  }

  /**
   * A default theme without card styles is not given a missing library.
   */
  public function testThemeWithoutCardLibrary(): void {
    $build = $this->buildController(TRUE, NULL, FALSE)->page('hs_default_search', 'search_page', $this->createMock(RouteMatchInterface::class));

    $this->assertNotContains('humsci_colorful/card', $build['#attached']['library']);
  }

  /**
   * The search view route is pointed at the controller, keeping its defaults.
   */
  public function testRouteIsAltered(): void {
    $collection = new RouteCollection();
    $collection->add(SearchController::VIEW_ROUTE, new Route('/search', [
      '_controller' => 'Drupal\views\Routing\ViewPageController::handle',
      'view_id' => 'hs_default_search',
      'display_id' => 'search_page',
    ]));

    $method = new \ReflectionMethod(RouteSubscriber::class, 'alterRoutes');
    $method->invoke(new RouteSubscriber(), $collection);

    $route = $collection->get(SearchController::VIEW_ROUTE);
    $this->assertSame(SearchController::class . '::page', $route->getDefault('_controller'));
    $this->assertSame('hs_default_search', $route->getDefault('view_id'));
    $this->assertSame('search_page', $route->getDefault('display_id'));
  }

}
