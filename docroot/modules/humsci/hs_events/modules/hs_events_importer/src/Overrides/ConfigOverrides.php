<?php

namespace Drupal\hs_events_importer\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class ConfigOverrides.
 *
 * @package Drupal\hs_events_importer\Overrides
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ConfigOverrides constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * Override the path to the key for the encryption profile.
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (!(in_array('migrate_plus.migration.hs_events_image_importer', $names) || in_array('migrate_plus.migration.hs_events_importer', $names))) {
      return $overrides;
    }

    $config = $this->configFactory->get('hs_events_importer.settings');
    if ($urls = $config->get('urls')) {
      $overrides['migrate_plus.migration.hs_events_importer'] = [
        'source' => [
          'urls' => $urls,
        ],
      ];
      // Image importer will have the same overrides.
      $overrides['migrate_plus.migration.hs_events_image_importer'] = $overrides['migrate_plus.migration.hs_events_importer'];
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ConfigOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
