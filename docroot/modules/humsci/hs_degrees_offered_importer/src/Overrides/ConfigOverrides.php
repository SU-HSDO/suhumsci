<?php

namespace Drupal\hs_degrees_offered_importer\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class ConfigOverrides.
 *
 * @package Drupal\hs_degrees_offered_importer\Overrides
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Core state service.
   *
   * @var \Drupal\Core\State\StateInterface|null
   */
  protected $state;

  /**
   * CourseImporterOverrides constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\State\StateInterface|null $state
   *   Core state interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state = NULL) {
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   *
   * Override the path to the key for the encryption profile.
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('migrate_plus.migration.hs_degrees_offered_importer', $names)) {
      $config = $this->configFactory->get('hs_degrees_offered_importer.settings');
      if ($urls = $config->get('urls')) {
        $overrides['migrate_plus.migration.hs_degrees_offered_importer'] = [
          'source' => [
            'urls' => array_values($urls),
          ],
        ];
      }
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
