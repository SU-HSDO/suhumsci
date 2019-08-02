<?php

namespace Drupal\hs_page_reports\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Removes 404 and 403 routes provided by dblog.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritDoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('dblog.page_not_found')) {
      $route->setPath('/admin/reports/dblog/page-not-found');
    }
    if ($route = $collection->get('dblog.access_denied')) {
      $route->setPath('/admin/reports/dblog/access-denied');
    }
  }

}
