<?php

namespace Drupal\hs_migrate\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class EventsSubscriber.
 */
class EventsSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MigrateEventsSubscriber object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT] = ['postImport'];
    return $events;
  }

  /**
   * This method is called when the migrate.post_import is dispatched.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The dispatched event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\migrate\MigrateException
   */
  public function postImport(MigrateImportEvent $event) {
    if (!$this->doDeleteOrphans($event->getMigration())) {
      return;
    }

    /** @var \Drupal\hs_migrate\Plugin\migrate\source\HsUrl $source_plugin */
    $source_plugin = $event->getMigration()->getSourcePlugin();
    $current_source_ids = $source_plugin->getAllIds();

    /** @var \Drupal\migrate\Plugin\migrate\id_map\Sql $id_map */
    $id_map = $event->getMigration()->getIdMap();

    // Get the entity storage handler for the destination entity.
    $destination_config = $event->getMigration()->getDestinationConfiguration();
    list(, $type) = explode(':', $destination_config['plugin']);
    $entity_storage = $this->entityTypeManager->getStorage($type);

    $id_map->rewind();
    // Loop through already imported items, find out if they are in the current
    // source, then delete if appropriate.
    while ($id_map->current()) {
      $id_exists_in_source = FALSE;
      // Source key array of the already imported item.
      $source_id = $id_map->currentSource();

      // Look through the current source to see if we can find a match to the
      // existing item.
      foreach ($current_source_ids as $key => $ids) {
        if ($ids == $source_id) {
          // The existing item is in the source, flag it as found and we can
          // reduce the current source ids to make subsequent lookups faster.
          unset($current_source_ids[$key]);
          $id_exists_in_source = TRUE;
          break;
        }
      }

      // The current item was not found in the current source, time to delete
      // it.
      if (!$id_exists_in_source) {
        // Find the entity id from the id map.
        $destination_ids = $id_map->lookupDestinationIds($id_map->currentSource());
        // $destination_ids should be a single item.
        $entities = $entity_storage->loadMultiple(reset($destination_ids));

        // Delete the entity, then the record in the id map.
        $entity_storage->delete($entities);
        $id_map->delete($source_id);
      }

      // Move on to the next existing item.
      $id_map->next();
    }
  }

  /**
   * Find out if the orphans should be deleted.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration object that finished.
   *
   * @return bool
   *   Delete orphans or not.
   */
  protected function doDeleteOrphans(MigrationInterface $migration) {
    $source_config = $migration->getSourceConfiguration();
    // The migration entity should have a `delete_orphans` setting in the
    // source config.
    if (isset($source_config['delete_orphans']) && $source_config['delete_orphans'] == TRUE) {

      // @see \Drupal\hs_migrate\Plugin\migrate\source\HsUrl::getAllIds()
      if (method_exists($migration->getSourcePlugin(), 'getAllIds')) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
