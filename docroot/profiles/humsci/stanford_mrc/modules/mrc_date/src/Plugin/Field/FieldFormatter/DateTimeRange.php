<?php

namespace Drupal\mrc_date\Plugin\Field\FieldFormatter;

use Drupal\hs_field_helpers\Plugin\Field\FieldFormatter\DateTimeRange as NewDateTimeRange;

/**
 * Placeholder for formatter id, the code has been moved into hs_field_helpers.
 *
 * @FieldFormatter(
 *   id = "datetimerange_custom",
 *   label = @Translation("Custom Single"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateTimeRange extends NewDateTimeRange {

}
