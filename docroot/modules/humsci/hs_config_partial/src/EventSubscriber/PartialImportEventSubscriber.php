<?php

namespace Drupal\hs_config_partial\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to prevent config deletions during import (partial import behavior).
 */
class PartialImportEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority -200: after config_ignore (-100) and config_split (default 0 or negative).
    return [
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['onImportTransform', -200],
    ];
  }

  /**
   * Removes deletions from the config import transformation.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    var_dump('test');
    $import_storage = $event->getStorage();
    $active_storage = \Drupal::service('config.storage');

    foreach ($active_storage->listAll() as $config_name) {
      if (!$import_storage->exists($config_name)) {
        // Prevent deletion by restoring the config in the import storage.
        $import_storage->write($config_name, $active_storage->read($config_name));
      }
    }
  }

}
