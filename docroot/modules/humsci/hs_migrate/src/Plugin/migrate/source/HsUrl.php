<?php

namespace Drupal\hs_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Overrides the parent method to add the active url into the configuration.
 *
 * @package Drupal\hs_migrate\Plugin\migrate\source
 */
class HsUrl extends Url {

  /**
   * Get all ids that exist in the current source.
   *
   * @return array
   *   Array of id data from the source.
   */
  public function getAllIds() {
    $this->highWaterProperty = [
      'name' => 'guid',
    ];
    $this->rewind();
    $ids = [];
    while ($this->current()) {
      $ids[] = $this->currentSourceIds;
      $this->next();
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   *
   * Modify the parent method by adding the current feed url into the source
   * data. This can then be used by process plugins.
   */
  public function next() {

    $this->currentSourceIds = NULL;
    $this->currentRow = NULL;

    // In order to find the next row we want to process, we ask the source
    // plugin for the next possible row.
    while (!isset($this->currentRow) && $this->getIterator()->valid()) {

      $row_data = $this->getIterator()->current() + $this->configuration;

      // Add current url to row data for use in process plugins.
      $plugin = $this->getDataParserPlugin();
      if (method_exists($plugin, "getCurrentUrl")) {
        $row_data['current_feed_url'] = $plugin->getCurrentUrl();
      }
      // End new custom code.

      $this->fetchNextRow();
      $row = new Row($row_data, $this->getIds());

      // Populate the source key for this row.
      $this->currentSourceIds = $row->getSourceIdValues();

      // Pick up the existing map row, if any, unless fetchNextRow() did it.
      if (!$this->mapRowAdded && ($id_map = $this->idMap->getRowBySource($this->currentSourceIds))) {
        $row->setIdMap($id_map);
      }

      // Clear any previous messages for this row before potentially adding
      // new ones.
      if (!empty($this->currentSourceIds)) {
        $this->idMap->delete($this->currentSourceIds, TRUE);
      }

      // Preparing the row gives source plugins the chance to skip.
      if ($this->prepareRow($row) === FALSE) {
        continue;
      }

      // Check whether the row needs processing.
      // 1. This row has not been imported yet.
      // 2. Explicitly set to update.
      // 3. The row is newer than the current highwater mark.
      // 4. If no such property exists then try by checking the hash of the row.
      if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
        $this->currentRow = $row->freezeSource();
      }

      if ($this->getHighWaterProperty()) {
        $this->saveHighWater($row->getSourceProperty($this->highWaterProperty['name']));
      }
    }
  }

}
