<?php

namespace Drupal\hs_field_helpers\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_usage\EntityUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity clean up queue worker.
 *
 * @QueueWorker(
 *    id = "entity_cleanup",
 *    title = @Translation("Entity Clean Up"),
 *    cron = {"time" = 60}
 *  )
 */
class EntityCleanupQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_usage.usage')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityTypeManagerInterface $entityTypeManager, protected EntityUsageInterface $entityUsage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {
    if (!$this->entityTypeManager->hasDefinition($data['entity_type'])) {
      return;
    }
    $entity_storage = $this->entityTypeManager->getStorage($data['entity_type']);
    $entity = $entity_storage->load($data['id']);

    if ($entity && empty($this->entityUsage->listSources($entity))) {
      $entity->delete();
    }
  }

}
