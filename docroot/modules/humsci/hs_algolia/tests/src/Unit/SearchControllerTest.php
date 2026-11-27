<?php

namespace Drupal\Tests\hs_algolia\Unit;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Adding a cache context validates it against the container.
    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Build the controller.
   *
   * @param bool $active
   *   Whether Algolia search is active.
   * @param \Drupal\views\Routing\ViewPageController|null $views_controller
   *   The Views controller the class resolver returns.
   * @param bool $theme_has_libraries
   *   Whether the active theme defines card and pager libraries.
   */
  protected function buildController(bool $active, ?ViewPageController $views_controller = NULL, bool $theme_has_libraries = TRUE): SearchController {
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

    $active_theme = $this->createMock(ActiveTheme::class);
    $active_theme->method('getName')->willReturn('humsci_colorful');
    $theme_manager = $this->createMock(ThemeManagerInterface::class);
    $theme_manager->method('getActiveTheme')->willReturn($active_theme);

    $library_discovery = $this->createMock(LibraryDiscoveryInterface::class);
    $library_discovery->method('getLibraryByName')
      ->with('humsci_colorful', $this->anything())
      ->willReturn($theme_has_libraries ? ['css' => []] : FALSE);

    $controller = new SearchController($status, $class_resolver, $theme_manager, $library_discovery);
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
      'humsci_colorful/card',
      'humsci_colorful/pager',
    ], $build['#attached']['library']);
    $this->assertContains('theme', $build['#cache']['contexts']);

    $settings = $build['#attached']['drupalSettings']['hsAlgoliaSearch'];
    $this->assertSame('search-key', $settings['searchKey']);
    $this->assertSame('search', $settings['queryParam']);
    $this->assertSame(SearchController::HITS_PER_PAGE, $settings['hitsPerPage']);
    $this->assertArrayNotHasKey('api_key', $settings);
    $this->assertContains('config:hs_algolia.settings', $build['#cache']['tags']);
  }

  /**
   * An active theme without card or pager styles is not given missing ones.
   */
  public function testThemeWithoutLibraries(): void {
    $build = $this->buildController(TRUE, NULL, FALSE)->page('hs_default_search', 'search_page', $this->createMock(RouteMatchInterface::class));

    $this->assertSame(['hs_algolia/search'], $build['#attached']['library']);
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
