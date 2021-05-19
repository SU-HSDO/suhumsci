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
 *   id = "url_to_term"
 * )
 */
class UrlToTerm extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return NULL;
  }

}
