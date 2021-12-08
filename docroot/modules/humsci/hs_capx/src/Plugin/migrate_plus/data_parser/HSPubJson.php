<?php

namespace Drupal\hs_capx\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Obtain JSON data for Capx Publications migrations.
 *
 * @DataParser(
 *   id = "hspubjson",
 *   title = @Translation("HSPubJson")
 * )
 */
class HSPubJson extends Json {

  /**
   * {@inheritDoc}
   */
  protected function getSourceData($url) {
    $source_data = parent::getSourceData($url);
    $modified_data = [];
    foreach ($source_data as $item) {
      if (!empty($item['publications'])) {
        $main_data = $item;
        unset($main_data['publications']);

        foreach ($item['publications'] as $publication) {
          $modified_data[] = $main_data + $publication;
        }
      }
    }
    return $modified_data;
  }

}
