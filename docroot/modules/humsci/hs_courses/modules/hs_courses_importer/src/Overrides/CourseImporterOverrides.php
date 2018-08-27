<?php

namespace Drupal\hs_courses_importer\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class CourseImporterOverrides.
 *
 * @package Drupal\hs_courses_impoter
 */
class CourseImporterOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('migrate_plus.migration.hs_courses', $names)) {
      $importer_settings = \Drupal::configFactory()
        ->get('hs_courses_importer.importer_settings');

      $base_url = $importer_settings->getOriginal('base_url', FALSE);
      $urls = $importer_settings->getOriginal('urls', FALSE);

      // Escape if the config hasn't been set yet.
      if (!$base_url || !$urls) {
        return [];
      }

      // Build the local urls with the feed source as a query parameter.
      foreach ($urls as &$url) {
        $url = urlencode($url);
        $url = "$base_url/api/hs_courses?feed=$url";
      }

      // Point the migration to our local url where we process the feed into
      // usable data.
      $overrides['migrate_plus.migration.hs_courses'] = [
        'source' => [
          'urls' => $urls,
        ],
      ];
    }
    return $overrides;
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
