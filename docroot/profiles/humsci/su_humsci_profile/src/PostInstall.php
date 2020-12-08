<?php

namespace Drupal\su_humsci_profile;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;

/**
 * Class PostInstall service.
 *
 * @package Drupal\su_humsci_profile
 */
class PostInstall implements PostInstallInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PostInstall constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   Route builder service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteBuilderInterface $route_builder, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeBuilder = $route_builder;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritDoc}
   */
  public function runTasks() {
    $user = $this->entityTypeManager->getStorage('user')->load(1);
    $user->addRole('administrator');
    $user->setUsername('sws-developers');
    $user->save();

    if ($importer_service = self::getContentImporter()) {
      $importer_service->importContent('humsci_default_content');
    }

    node_access_rebuild();

    // We install some menu links, so we have to rebuild the router, to ensure
    // the menu links are valid.
    $this->routeBuilder->rebuildIfNeeded();

    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['uuid' => '287db095-35b1-4050-8d26-5d8332eeb6a6']);
    $this->configFactory->getEditable('system.site')
      ->set('page.front', '/node/' . key($nodes))
      ->save();

  }

  /**
   * Get the default content importer service if it exists.
   *
   * @return \Drupal\default_content\ImporterInterface|null
   *   Importer service if available.
   */
  protected static function getContentImporter() {
    if (\Drupal::hasService('default_content.importer')) {
      return \Drupal::service('default_content.importer');
    }
  }

}
