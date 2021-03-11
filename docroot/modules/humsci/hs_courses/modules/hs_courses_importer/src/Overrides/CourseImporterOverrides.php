<?php

namespace Drupal\hs_courses_importer\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class CourseImporterOverrides.
 *
 * @package Drupal\hs_courses_impoter
 */
class CourseImporterOverrides implements ConfigFactoryOverrideInterface {

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
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state = NULL) {
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('migrate_plus.migration.hs_courses', $names)) {
      if ($urls = $this->getMigrationUrls()) {
        // Point the migration to our local url where we process the feed into
        // usable data.
        $overrides['migrate_plus.migration.hs_courses'] = [
          'source' => [
            'urls' => $urls,
          ],
        ];
      }
    }
    return $overrides;
  }

  /**
   * Get the local urls with the query parameter for the feed source.
   *
   * @return string[]
   *   Local urls array.
   */
  protected function getMigrationUrls() {
    if (!$this->state) {
      return [];
    }
    $importer_settings = $this->configFactory->get('hs_courses_importer.importer_settings');
    $base_url = $this->state->get('hs_courses_importer.base_url');
    $urls = $importer_settings->getOriginal('urls', FALSE) ?: [];

    // Build the local urls with the feed source as a query parameter.
    foreach ($urls as &$url) {
      $url = urlencode($url);
      $url = "$base_url/api/hs_courses?feed=$url";
    }
    return $urls;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
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
  public function getCacheSuffix() {
    return 'CourseImporterOverrides';
  }

}
