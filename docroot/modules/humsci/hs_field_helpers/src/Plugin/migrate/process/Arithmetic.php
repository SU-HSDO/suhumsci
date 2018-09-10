<?php

namespace Drupal\hs_field_helpers\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Do some math formula on a numerical value.
 *
 * Available configuration keys:
 * - operation: Mathematical operation.
 * - fields:
 *
 * Examples:
 *
 * @code
 * process:
 *   plugin: arithmetic
 *   operation: +
 *   source: some_numerical_field
 *   fields:
 *     - another_numerical_field
 *     - constants/some_number
 * @endcode
 *
 * This will perform the mathematical operation on the source fields.
 *
 * @MigrateProcessPlugin(
 *   id = "arithmetic"
 * )
 */
class Arithmetic extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['operation']) || empty($this->configuration['fields'])) {
      return $value;
    }

    $fields = is_array($this->configuration['fields']) ? $this->configuration['fields'] : [$this->configuration['fields']];
    array_unshift($fields, $value);

    foreach ($fields as &$item) {
      if (is_string($item) && $row->hasSourceProperty($item)) {
        $item = $row->getSourceProperty($item);
        $item = preg_replace('[^0-9\+-\*\/\(\) ]', '', $item);
      }
    }

    $result = '';
    switch ($this->configuration['operation']) {
      case '+';
        $result = $this->addFields($fields);
        break;

      case '-';
        $result = $this->subtractFields($fields);
        break;

      case '*';
        $result = $this->multiplyFields($fields);
        break;

      case '/';
        $result = $this->divideFields($fields);
        break;
    }
    return $result;
  }

  /**
   * Add all the fields.
   *
   * @param array $fields
   *   Numeric values.
   *
   * @return int
   *   Numeric result.
   */
  protected function addFields(array $fields) {
    $result = 0;
    foreach (array_filter($fields) as $field) {
      $result += $field;
    }
    return $result;
  }

  /**
   * Subtract the following fields from the first one.
   *
   * @param array $fields
   *   Numeric values.
   *
   * @return int
   *   Numeric result.
   */
  protected function subtractFields(array $fields) {
    $result = NULL;
    foreach (array_filter($fields) as $field) {
      if ($result === NULL) {
        $result = $field;
        continue;
      }
      $result += $field;
    }
    return $result;
  }

  /**
   * Multiply all the fields together.
   *
   * @param array $fields
   *   Numeric values.
   *
   * @return int
   *   Numeric result.
   */
  protected function multiplyFields(array $fields) {
    $result = NULL;
    foreach (array_filter($fields) as $field) {
      if ($result === NULL) {
        $result = $field;
        continue;
      }
      $result *= $field;
    }
    return $result;
  }

  /**
   * Divide the first field by the following fields.
   *
   * @param array $fields
   *   Numeric values.
   *
   * @return int
   *   Numeric result.
   */
  protected function divideFields(array $fields) {
    $result = NULL;
    foreach (array_filter($fields) as $field) {
      if ($result === NULL) {
        $result = $field;
        continue;
      }
      $result /= $field;
    }
    return $result;
  }

}
