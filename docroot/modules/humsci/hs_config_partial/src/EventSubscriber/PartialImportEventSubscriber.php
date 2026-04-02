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
   * List of config names allowed to be deleted on import.
   *
   * @var array
   */
  protected array $allowDelete = [
    'acquia_connector.',
    'purge.',
    'purge_queuer_coretags.',
    'ultimate_cron.job.',
  ];

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
    return [
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['onImportTransform', 100],
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
      // If the import storage is missing configuration that is in the active
      // storage, it will delete the config from the active storage during
      // the import process. To prevent that, we restore the config from the
      // active storage back into the import storage.
      // We do need to allow specific configuration to be deleted as part of the
      // import process, especially when modules get uninstalled during a site
      // sync. We don't need to preserve everything.
      $allow_delete = FALSE;
      foreach ($this->allowDelete as $prefix) {
        if (strpos($config_name, $prefix) === 0) {
          $allow_delete = TRUE;
          break;
        }
      }
      
      if (!$allow_delete && !$import_storage->exists($config_name)) {
        $import_storage->write($config_name, $this->activeStorage->read($config_name));
      }
    }
  }

}
