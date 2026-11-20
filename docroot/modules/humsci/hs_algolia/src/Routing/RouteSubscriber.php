<?php

namespace Drupal\hs_algolia\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\hs_algolia\Controller\SearchController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Routes the Drupal search page through the Algolia search controller.
 *
 * The route is altered on every site, not only those with Algolia search
 * turned on, so switching it on or off takes effect without a router rebuild.
 * The controller hands the request back to Views when Algolia search is off.
 *
 * @see \Drupal\hs_algolia\Controller\SearchController::page()
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get(SearchController::VIEW_ROUTE);
    if ($route) {
      $route->setDefault('_controller', SearchController::class . '::page');
    }
  }

}
