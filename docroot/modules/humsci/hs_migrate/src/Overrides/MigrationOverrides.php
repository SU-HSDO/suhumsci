<?php

namespace Drupal\hs_migrate\Overrides;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class MigrationOverrides
 *
 * @package Drupal\hs_migrate\Overrides
 */
class MigrationOverrides implements ConfigFactoryOverrideInterface {

  /**
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * MigrationOverrides constructor.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $config_pages_loader
   */
  public function __construct(ConfigPagesLoaderServiceInterface $config_pages_loader) {
    $this->configPagesLoader = $config_pages_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('migrate_plus.migration.hs_d7_news', $names)) {
      if ($urls = $this->getNewsMigrationUrls()) {
        // Point the migration to our local url where we process the feed into
        // usable data.
        $overrides['migrate_plus.migration.hs_d7_news'] = [
          'status' => TRUE,
          'source' => [
            'urls' => $urls,
          ],
        ];
      }
    }
    return $overrides;
  }

  protected function getNewsMigrationUrls() {
    $field_values = $this->configPagesLoader->getValue('hs_migrate_news', 'field_news_xml_feed');
    $urls = [];
    foreach ($field_values as $value) {
      $urls[] = $value['uri'];
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
