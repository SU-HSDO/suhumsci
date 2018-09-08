<?php

namespace Drupal\su_humsci_profile\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber
 *
 * @package Drupal\su_humsci_profile\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Module Handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if (($route = $collection->get('block.admin_display')) && $this->moduleHandler->moduleExists('block_content_permissions')) {
      $route->setRequirement('_permission', 'administer blocks+view restricted block content');
    }
  }

}
