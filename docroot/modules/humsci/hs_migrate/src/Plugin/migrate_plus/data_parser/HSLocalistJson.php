<?php

namespace Drupal\hs_migrate\Plugin\migrate_plus\data_parser;

use Drupal\stanford_migrate\Plugin\migrate_plus\data_parser\LocalistJson;

/**
 * Obtain JSON data for Capx Publications migrations.
 *
 * @DataParser(
 *   id = "hs_localist_json",
 *   title = @Translation("HSPubJson")
 * )
 */
class HSLocalistJson extends LocalistJson {

  /**
   * {@inheritDoc}
   */
  protected function getSourceData(string $url): array {
    $source_data = parent::getSourceData($url);

    $modified_data = [];
    foreach ($source_data as $item) {
      if (isset($modified_data[$item['event']['id']])) {
        $modified_data[$item['event']['id']]['event']['event_instances'][] = $item['event']['event_instances'][0];
      }
      else {
        $modified_data[$item['event']['id']] = $item;
      }
    }
    return array_values($modified_data);
  }

}
