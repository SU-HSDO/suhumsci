<?php

namespace Drupal\hs_migrate\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class MigrateEventsSubscriber.
 */
class MigrateEventsSubscriber implements EventSubscriberInterface {

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
   */
  public function postImport(MigrateImportEvent $event) {

    /** @var \Drupal\hs_migrate\Plugin\migrate\source\HsUrl $source_plugin */
    $source_plugin = $event->getMigration()->getSourcePlugin();
    if (!method_exists($source_plugin, 'getAllIds')) {
      return;
    }

    $source_ids = $source_plugin->getAllIds();

    /** @var \Drupal\migrate\Plugin\migrate\id_map\Sql $id_map */
    $id_map = $event->getMigration()->getIdMap();

    $destination_config = $event->getMigration()->getDestinationConfiguration();
    list($plugin, $type) = explode(':', $destination_config['plugin']);

    $id_map->rewind();
    while ($id_map->current()) {
      $id_exists_in_source = FALSE;
      $source_id = $id_map->currentSource();

      foreach ($source_ids as $key => $ids) {
        if ($ids == $source_id) {
          unset($source_ids[$key]);
          $id_exists_in_source = TRUE;
          break;
        }
      }

      if (!$id_exists_in_source) {
        $destination_ids = $id_map->lookupDestinationIds($id_map->currentSource());
        $entities = $this->entityTypeManager->getStorage($type)
          ->loadMultiple(reset($destination_ids));
        $this->entityTypeManager->getStorage($type)->delete($entities);
        $id_map->delete($source_id);
      }

      $id_map->next();
    }
  }

}
