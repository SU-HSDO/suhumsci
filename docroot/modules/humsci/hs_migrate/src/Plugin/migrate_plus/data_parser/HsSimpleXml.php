<?php

namespace Drupal\hs_migrate\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\SimpleXml;

/**
 * Overrides the parent plugin to provide a method to get the active url.
 *
 * @package Drupal\hs_migrate\Plugin\migrate_plus\data_parser
 */
class HsSimpleXml extends SimpleXml {

  /**
   * Get the current active url string.
   *
   * @return string
   *   Url.
   */
  public function getCurrentUrl() {
    return $this->urls[$this->activeUrl];
  }

}
