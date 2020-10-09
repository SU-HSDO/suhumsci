<?php

namespace Drupal\hs_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Migrate process plugin that will skip if an image isn't big enough.
 *
 * @code
 * process:
 *   field_image:
 *     plugin: image_dimension_skip
 *     method: row
 *     width: 100
 *     height: 100
 *   field_image_2:
 *     plugin: image_dimension_skip
 *     method: process
 *     width: 100
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "image_dimension_skip"
 * )
 */
class ImageDimensionSkip extends ProcessPluginBase {

  /**
   * Skips the current row if the given image url/path is not large enough.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return mixed
   *   The input value, $value, if it is not empty.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   Thrown if the source property is not set and the row should be skipped,
   *   records with STATUS_IGNORED status in the map.
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$this->isImageBigger($value)) {
      $message = 'Image is not big enough as defined by the limits.';
      throw new MigrateSkipRowException($message);
    }
    return $value;
  }

  /**
   * Stops processing the current property when value is not set.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return mixed
   *   The input value, $value, if it is not empty.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   *   Thrown if the source property is not set and rest of the process should
   *   be skipped.
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$this->isImageBigger($value)) {
      throw new MigrateSkipProcessException();
    }
    return $value;
  }

  /**
   * Check if the give url is bigger than the restrictions.
   *
   * @param mixed $value
   *   The input value, $value, if it is not empty.
   *
   * @return bool
   *   True if the image is bigger than the restrictions.
   */
  protected function isImageBigger($value): bool {
    if (!is_string($value)) {
      return FALSE;
    }
    try {
      [$width, $height] = getimagesize($value);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    $valid_width = empty($this->configuration['width']) ? TRUE : $this->configuration['width'] <= $width;
    $valid_height = empty($this->configuration['height']) ? TRUE : $this->configuration['height'] <= $height;
    return $valid_height && $valid_width;
  }

}
