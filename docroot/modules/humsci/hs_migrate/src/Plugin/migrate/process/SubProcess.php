<?php

namespace Drupal\hs_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\process\SubProcess as OriginalSubProcess;

/**
 * Override Drupal Core SubProcess plugin to allow SimpleXml processor.
 */
class SubProcess extends OriginalSubProcess {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $return = [];
    if (is_array($value) || $value instanceof \Traversable) {
      foreach ($value as $key => $new_value) {
        // This is the difference with the original process plugin. When using
        // SimpleXML parters, the `$new_value` is a SimpleXmlElement. So we have
        // to cast it to an array to construct the new row.
        $new_row = new Row((array) $new_value, []);
        $migrate_executable->processRow($new_row, $this->configuration['process']);
        $destination = $new_row->getDestination();
        if (array_key_exists('key', $this->configuration)) {
          $key = $this->transformKey($key, $migrate_executable, $new_row);
        }
        $return[$key] = $destination;
      }
    }
    return $return;
  }

}
