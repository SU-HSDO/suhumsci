<?php

namespace Drupal\hs_field_helpers\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class HsFieldHelpersCommands extends DrushCommands {

  /**
   * Command constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Queue factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected QueueFactory $queueFactory, protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Queues up all entities to delete unusued items.
   *
   * @command humsci:queue-entity-cleanup
   */
  public function queueEntityCleanup() {
    $queue = $this->queueFactory->get('entity_cleanup');

    foreach ($this->getEntityTypes() as $entity_type) {
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);

      $entity_ids = $entity_storage->getQuery()
        ->accessCheck(FALSE)
        ->execute();

      foreach ($entity_ids as $id) {
        $queue->createItem([
          'entity_type' => $entity_type,
          'id' => $id,
        ]);
      }
    }
  }

  /**
   * Get the entity types to clean if there are no usages.
   *
   * @return string[]
   *   Entity type ids.
   */
  function getEntityTypes(): array {
    $cleanup_types = ['paragraph', 'hs_entity'];
    $definitions = $this->entityTypeManager->getDefinitions();

    /** @var \Drupal\Core\Entity\ContentEntityType $entity_type */
    foreach ($definitions as $entity_type_id => $entity_type) {
      // Find all the entity types ECK has available.
      if ($entity_type->getOriginalClass() == 'Drupal\eck\Entity\EckEntity') {
        $cleanup_types[] = $entity_type_id;
      }
    }
    return $cleanup_types;
  }
}
