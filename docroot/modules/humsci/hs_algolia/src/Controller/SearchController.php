<?php

namespace Drupal\hs_algolia\Controller;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\hs_algolia\SearchStatus;
use Drupal\views\Routing\ViewPageController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Serves the site search page from Algolia when Algolia search is active.
 *
 * @see \Drupal\hs_algolia\Routing\RouteSubscriber
 */
class SearchController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Route of the Drupal search page this controller takes over.
   */
  const VIEW_ROUTE = 'view.hs_default_search.search_page';

  /**
   * Results per page, matching the Drupal search view's pager.
   */
  const HITS_PER_PAGE = 10;

  public function __construct(
    protected SearchStatus $searchStatus,
    protected ClassResolverInterface $classResolver,
    protected ThemeManagerInterface $themeManager,
    protected LibraryDiscoveryInterface $libraryDiscovery,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hs_algolia.search_status'),
      $container->get('class_resolver'),
      $container->get('theme.manager'),
      $container->get('library.discovery'),
    );
  }

  /**
   * Render the Algolia search page, or the Drupal search view as a fallback.
   *
   * @param string $view_id
   *   The view ID from the route defaults.
   * @param string $display_id
   *   The view display ID from the route defaults.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   A render array.
   */
  public function page($view_id, $display_id, RouteMatchInterface $route_match) {
    if (!$this->searchStatus->isActive()) {
      /** @var \Drupal\views\Routing\ViewPageController $views_controller */
      $views_controller = $this->classResolver->getInstanceFromDefinition(ViewPageController::class);
      $build = $views_controller->handle($view_id, $display_id, $route_match);
      // The view's own cache tags do not change when Algolia search is turned
      // on, so add the tags that would.
      $this->searchStatus->getCacheableMetadata()->applyTo($build);
      return $build;
    }

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['hs-algolia-search']],
      'app' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['hs-algolia-search__app']],
      ],
      'noscript' => [
        '#type' => 'html_tag',
        '#tag' => 'noscript',
        '#value' => $this->t('Search requires JavaScript. Enable JavaScript in your browser to search this site.'),
      ],
      '#attached' => [
        'library' => $this->getLibraries(),
        'drupalSettings' => [
          'hsAlgoliaSearch' => $this->searchStatus->getClientSettings() + [
            'hitsPerPage' => self::HITS_PER_PAGE,
            'queryParam' => 'search',
            'strings' => [
              'label' => (string) $this->t('Search'),
              'submit' => (string) $this->t('Search'),
              'empty' => (string) $this->t('There are no results for your search. Please try again.'),
              'error' => (string) $this->t('Search is unavailable right now. Please try again later.'),
              'pagerLabel' => (string) $this->t('Pagination'),
              'first' => (string) $this->t('First page'),
              'previous' => (string) $this->t('Previous page'),
              'next' => (string) $this->t('Next page'),
              'last' => (string) $this->t('Last page'),
              'page' => (string) $this->t('Page'),
              'currentPage' => (string) $this->t('Current page'),
            ],
          ],
        ],
      ],
    ];
    $this->searchStatus->getCacheableMetadata()
      ->addCacheContexts(['theme'])
      ->applyTo($build);
    return $build;
  }

  /**
   * Libraries that give the results the same look as the Drupal search.
   *
   * @return string[]
   *   Library names.
   */
  protected function getLibraries(): array {
    $libraries = ['hs_algolia/search'];

    // The app renders card and pager markup in the browser, so nothing
    // attaches their styles the way the horizontal card pattern and pager
    // templates do. Each subtheme compiles its own, so use the active theme's
    // libraries.
    $theme = $this->themeManager->getActiveTheme()->getName();
    foreach (['card', 'pager'] as $name) {
      if ($this->libraryDiscovery->getLibraryByName($theme, $name)) {
        $libraries[] = "$theme/$name";
      }
    }
    return $libraries;
  }

}
