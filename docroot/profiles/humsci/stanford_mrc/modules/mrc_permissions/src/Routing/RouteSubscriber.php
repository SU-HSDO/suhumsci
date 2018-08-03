<?php

namespace Drupal\mrc_permissions\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    /** @var \Symfony\Component\Routing\Route $route */
    foreach ($collection as $route_name => &$route) {
      // Skip front, nolink, etc pages.
      if (strpos($route_name, '.') === FALSE) {
        continue;
      }

      list($module, $page) = explode('.', $route_name, 2);
      $method = $this->getModuleMethod($module);

      if (method_exists($this, $method)) {
        $this->{$method}($route, $page);
      }
    }

  }

  /**
   * Get a upper camel case method name from the module name.
   *
   * @param string $module_name
   *   Drupal module name.
   *
   * @return string
   *   Camel case method name.
   */
  protected function getModuleMethod($module_name) {
    $method = explode('_', $module_name);
    $method = array_map('ucfirst', $method);
    return 'alter' . implode('', $method) . 'Route';
  }

  /**
   * Alter dblog module routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   * @param $page
   */
  protected function alterDblogRoute(Route $route, $page) {
    switch ($page) {
      case 'page_not_found':
      case 'access_denied':
      case 'search':
        $route->setRequirement('_permission', 'dblog view top site reports');
        break;
    }
  }

  /**
   * Alter admin_toolbar_tools module routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   * @param $page
   */
  protected function alterAdminToolbarToolsRoute(Route $route, $page) {
    switch ($page) {
      case 'flush':
        $route->setRequirement('_permission', 'toolbar tools flush caches');
        break;
    }
  }

  /**
   * Alter system module routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   * @param $page
   */
  protected function alterSystemRoute(Route $route, $page) {
    switch ($page) {
      case 'site_information_settings':
        $route->setRequirement('_permission', 'system change site information');
        break;
      case 'admin_reports':
        $old_permission = $route->getRequirement('_permission');
        $new_permission = 'dblog view top site reports';
        $route->setRequirement('_permission', "$old_permission+$new_permission");
        break;
    }
  }

}
