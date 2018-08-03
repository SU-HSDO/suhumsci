<?php

namespace Drupal\mrc_migrate_processors\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Download;
use Drupal\migrate\Row;

/**
 * Downloads a file from a HTTP(S) remote location into the local file system.
 *
 * The source value is an array of two values:
 * - source URL, e.g. 'http://www.example.com/img/foo.img'
 * - destination URI, e.g. 'public://images/foo.img'
 *
 * Available configuration keys:
 * - rename: (optional) If set, a unique destination URI is generated. If not
 *   set, the destination URI will be overwritten if it exists.
 * - guzzle_options: (optional)
 *
 * @link http://docs.guzzlephp.org/en/latest/request-options.html Array of
 *   request options for Guzzle. @endlink
 *
 * Examples:
 *
 * @code
 * process:
 *   plugin: download
 *   source:
 *     - source_url
 *     - destination_directory
 * @endcode
 *
 * This will download source_url to destination_uri.
 *
 * @code
 * process:
 *   plugin: download
 *   source:
 *     - source_url
 *     - destination_directory
 *   rename: true
 * @endcode
 *
 * This will download source_url to destination_uri and ensure that the
 * destination URI is unique. If a file with the same name exists at the
 * destination, a numbered suffix like '_0' will be appended to make it unique.
 *
 * @MigrateProcessPlugin(
 *   id = "download_file"
 * )
 */
class DownloadFile extends Download {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a uri of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }
    list($source, $dest_directory) = $value;

    $file_name = basename($source);
    $destination = "$dest_directory/$file_name";
    $value = [$source, $destination];
    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
