<?php

namespace Drupal\hs_field_helpers\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Uses the trim() method on a source string.
 *
 * @MigrateProcessPlugin(
 *   id = "trim"
 * )
 *
 * To do a simple hardcoded trim use the following:
 *
 * @code
 * field_text:
 *   plugin: trim
 *   source: text
 * @endcode
 *
 * Right Trim can be achieved using the following:
 * @code
 * field_text:
 *   plugin: trim
 *   side: right
 *   charlist: ' \n'
 *   search: foo
 * @endcode
 *
 * Left Trim can be achieved using the following:
 * @code
 * field_text:
 *   plugin: trim
 *   side: left
 *   search: foo
 * @endcode
 */
class Trim extends ProcessPluginBase {

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */
  protected $multiple;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->multiple = is_array($value);
    $this->configuration += [
      'side' => '',
      'charlist' => " \t\n\r\0\x0B",
    ];
    switch ($this->configuration['side']) {
      case 'right':
        $function = 'rtrim';
        break;

      case 'left':
        $function = 'ltrim';
        break;

      default:
        $function = 'trim';
        break;
    }
    if ($this->multiple()) {
      foreach ($value as &$item) {
        $function($item, $this->configuration['charlist']);
      }
      return $value;
    }
    return $function($value, $this->configuration['charlist']);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

}
