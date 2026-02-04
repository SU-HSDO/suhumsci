<?php

namespace Drupal\hs_config_partial\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to prevent config deletions during import.
 */
class PartialImportEventSubscriber implements EventSubscriberInterface {

  /**
   * The active config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active config storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(StorageInterface $active_storage, ConfigFactoryInterface $config_factory) {
    $this->activeStorage = $active_storage;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority -200: after config_ignore (-100) and config_split (default 0 or
    // negative).
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
    // Only run if the feature flag is enabled.
    $enabled = TRUE;
    if ($this->configFactory) {
      $enabled = (bool) $this->configFactory->get('hs_config_partial.settings')->get('enabled');
    }
    if (!$enabled) {
      return;
    }
    $import_storage = $event->getStorage();
    foreach ($this->activeStorage->listAll() as $config_name) {
      if (!$import_storage->exists($config_name)) {
        // If the import storage is missing configuration that is in the active
        // storage, it will delete the config from the active storage during
        // the import process. To prevent that, we restore the config from the
        // active storage back into the import storage.
        $import_storage->write($config_name, $this->activeStorage->read($config_name));
      }
    }
  }

}
