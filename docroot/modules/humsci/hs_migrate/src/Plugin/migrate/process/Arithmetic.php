<?php

namespace Drupal\hs_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

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
    $expression = new ExpressionLanguage();
    return $expression->evaluate(implode($this->configuration['operation'], $fields));
  }

}
