<?php

namespace Drupal\hs_capx\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "capx_json",
 *   title = @Translation("JSON")
 * )
 */
class CapxJson extends Json {

  /**
   * @return string
   */
  public function getCurrentUrl() {
    return $this->urls[$this->activeUrl];
  }

}
