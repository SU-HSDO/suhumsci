<?php

namespace Drupal\hs_migrate\Overrides;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Migration configuration overrides from config pages values.
 *
 * @package Drupal\hs_migrate\Overrides
 */
class MigrationOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('migrate_plus.migration.hs_d7_news', $names)) {
      if ($urls = self::getNewsMigrationUrls()) {
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

  /**
   * Get the news urls that are configured in the config pages.
   *
   * @return array
   *   Array of urls.
   */
  protected static function getNewsMigrationUrls() {
    if (!\Drupal::hasService('config_pages.loader')) {
      return [];
    }

    $config_pages = \Drupal::service('config_pages.loader');
    $field_values = $config_pages->getValue('hs_migrate_news', 'field_news_xml_feed');
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
