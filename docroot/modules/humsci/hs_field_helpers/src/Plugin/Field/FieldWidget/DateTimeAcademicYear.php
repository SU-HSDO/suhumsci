<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'datetime_datelist' widget.
 *
 * @FieldWidget(
 *   id = "datetime_academic_year",
 *   label = @Translation("Academic Year"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeAcademicYear extends DateTimeYearOnly {

  /**
   * {@inheritdoc}
   */
  protected function getOptions() {
    $options = parent::getOptions();
    foreach ($options as &$year) {
      $year = $year - 1 . " - $year";
    }
    return $options;
  }

}
