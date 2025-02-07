<?php

namespace Drupal\su_humsci_profile;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class PostInstall service.
 *
 * @package Drupal\su_humsci_profile
 */
class PostInstall implements PostInstallInterface {

  /**
   * PostInstall constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   *   Route builder service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, protected RouteBuilderInterface $routeBuilder, protected ConfigFactoryInterface $configFactory, protected StateInterface $state) {}

  /**
   * {@inheritDoc}
   */
  public function runTasks() {
    $this->state->set('nobots', TRUE);

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
