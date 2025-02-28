<?php

namespace Drupal\hs_dashboard\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an interface for Importer Info plugins.
 */
interface ImporterInfoInterface extends PluginInspectionInterface {

  /**
   * Gets the table headers for the importer.
   *
   * @return array
   *   Returns an array of table headers.
   */
  public function getTableHeaders(): array;

  /**
   * Gets the table rows for the importer.
   *
   * @return array
   *   Returns an array of table rows.
   */
  public function getTableRows(): array;

  /**
   * Gets the table suffix for the importer.
   *
   * @return Drupal\Core\StringTranslation\TranslatableMarkup
   *   A table suffix.
   */
  public function getTableSuffix(): ?TranslatableMarkup;

  /**
   * Gets the caption for the importer.
   *
   * @return Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A caption or null.
   */
  public function getCaption(): TranslatableMarkup | null;

  /**
   * Gets the no data caption for the importer.
   *
   * @return Drupal\Core\StringTranslation\TranslatableMarkup
   *   A no data caption.
   */
  public function getNoDataCaption(): TranslatableMarkup;

}
