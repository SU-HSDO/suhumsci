<?php

namespace Drupal\hs_migrate\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigPageLink.
 *
 * @package Drupal\hs_migrate\Plugin\Derivative
 */
class ConfigPageLink extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * ConfigPageLink constructor.
   *
   * @param string $base_plugin_id
   *   Base derivative id.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];
    $config_page_types = $this->entityTypeManager->getStorage('config_pages_type')
      ->loadMultiple();
    /** @var \Drupal\config_pages\ConfigPagesTypeInterface $config_page */
    foreach ($config_page_types as $config_page) {
      $menu_settings = $config_page->get('menu');

      if (strpos($menu_settings['path'], '/admin/structure/migrate/') === 0) {

        $config_page_url = $this->entityTypeManager->getStorage('config_pages')
          ->create([
            'type' => $config_page->id(),
            'context' => serialize([]),
          ])->toUrl('edit');

        $links[$config_page->id()] = [
          'title' => $config_page->label(),
          'route_name' => $config_page_url->getRouteName(),
          'route_parameters' => $config_page_url->getRouteParameters(),
          'parent' => 'migrate_tools.menu',
        ];
        $links[$config_page->id()] += $base_plugin_definition;
      }
    }

    return $links;
  }

}
