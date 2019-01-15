<?php

namespace Drupal\hs_capx\Plugin\migrate\source;

use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Source plugin for retrieving data via URLs.
 *
 * @MigrateSource(
 *   id = "capx_url"
 * )
 */
class CapxUrl extends Url {

  public function next() {
    $this->configuration['active_url'] = $this->getDataParserPlugin()
      ->getCurrentUrl();
    parent::next();
  }

}
