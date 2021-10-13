<?php

namespace Drupal\su_humsci_profile\Routing;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\su_humsci_profile\Routing
 */
class RouteSubscriber extends RouteSubscriberBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Module Handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Publish Content module configs.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $publishContentConfig;

  /**
   * Site settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $siteSettingsConfig;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    $this->publishContentConfig = $config_factory->get('publishcontent.settings');
    $this->siteSettingsConfig = $config_factory->get('system.site');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if (($route = $collection->get('block.admin_display')) && $this->moduleHandler->moduleExists('block_content_permissions')) {
      $route->setRequirement('_permission', 'administer blocks+view restricted block content');
    }

    foreach ($collection as &$route) {
      if (strpos($route->getPath(), '/admin/people') === 0) {
        $route->setPath(str_replace('/admin/people', '/admin/users', $route->getPath()));
      }
    }

    $collection->get('entity.user.collection')->setDefault('_title', 'Users');

    if ($route = $collection->get('stanford_ssp.create_user')) {
      $route->setRequirement('_permission', 'add saml user');
    }

    if ($route = $collection->get('publishcontent.settings')) {
      $route->setRequirement('_permission', 'administer content types');
    }

    if ($route = $collection->get('entity.node.publish')) {
      $route->setRequirement('_custom_access', self::class . '::publishTabAccess');
    }
  }

  /**
   * Route access check for the publish_content module.
   *
   * @param int $node
   *   Node id parameter.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed or denied result.
   */
  public function publishTabAccess($node) {
    $node_paths = ["/node/$node"];
    $allowed = !empty($this->publishContentConfig->get('ui_localtask')) &&
      empty(array_intersect($node_paths, $this->siteSettingsConfig->get('page')));
    return AccessResult::allowedIf($allowed);
  }

}
