<?php

namespace Drupal\hs_migrate\Plugin\migrate\source;

use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Overrides the parent method to add the active url into the configuration.
 *
 * @package Drupal\hs_migrate\Plugin\migrate\source
 */
class HsUrl extends Url {

  /**
   * {@inheritdoc}
   */
  public function next() {
    $data_parser = $this->getDataParserPlugin();
    if (method_exists($data_parser, 'getCurrentUrl')) {
      $this->configuration['active_url'] = $data_parser->getCurrentUrl();
    }
    parent::next();
  }

}
