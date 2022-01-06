<?php

namespace Drupal\hs_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Adjust the give timestamp to work with the "All Day" in smart date module.
 *
 * @code
 * process:
 *   field_date/end_value:
 *     plugin: datetime_adjust
 *     source: end_value
 *     start_time: start_value
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "datetime_adjust"
 * )
 */
class DateTimeAdjust extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['start_time'])) {
      return $value;
    }
    $start = $row->get($this->configuration['start_time']);
    if (empty($start)) {
      return $value;
    }

    if (!is_numeric($start)) {
      $start = strtotime($start);
    }

    if (!is_numeric($value)) {
      $value = strtotime($value);
    }

    // If the start and end values are the same & the start is at 12:00 midnight
    // then increase the end value to be at the end of the day for the smart
    // date module to work correctly.
    if ((int) date('Gi', $start) == 0 && $start == $value) {
      return $value + (60 * 60 * 24) - 60;
    }

    // If either the start or the end date value are not at midnight, don't
    // adjust the end value at all since it won't be considered an "All Day"
    // date time.
    if ((int) date('Gi', $start) || (int) date('Gi', $value)) {
      return $value;
    }

    // Now that the start and the end must both be at midnight and different
    // days, reduce the end value by 1 minute to work correctly with the smart
    // date module.
    return $value - 60;
  }

}
