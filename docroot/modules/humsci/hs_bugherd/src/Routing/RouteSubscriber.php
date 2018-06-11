<?php

namespace Drupal\hs_bugherd\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class HsBugherdRouteSubscriber
 *
 * @package Drupal\hs_bugherd\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
   $route = $collection->get('bugherdapi.bugherd_configuration_form');
   $route->setDefault('_form', '\Drupal\hs_bugherd\Form\HsBugherdForm');
  }

}
