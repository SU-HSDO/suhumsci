<?php

namespace Drupal\hs_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Calculate the difference between two time stamps in minutes.
 *
 * This is useful for the `duration` column of smart date fields. The `source`
 * should consist of a two Epoch timestamps.
 *
 * @code
 * process:
 *   field_event/duration:
 *     plugin: date_diff
 *     source:
 *       - start_value
 *       - end_value
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "date_diff"
 * )
 */
class DateDifference extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value) || count($value) != 2) {
      throw new MigrateException('Source value must be an array of two values');
    }

    // Make sure all values are numeric timetamps.
    foreach ($value as &$item) {
      if (!is_numeric($item)) {
        $item = strtotime($item);
      }
    }
    // Sort the array so the largest timestamp is last.
    asort($value);

    // Return the difference in values in minutes.
    return (end($value) - reset($value)) / 60;
  }

}
