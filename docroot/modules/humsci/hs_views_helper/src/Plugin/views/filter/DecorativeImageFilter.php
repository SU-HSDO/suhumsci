<?php

namespace Drupal\hs_views_helper\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\PluginBase;

/**
 * Class DecorativeImageFilter.
 *
 * Filters the "Decorative Image" checkbox value from Image media entities.
 *
 * @ViewsFilter("decorative_image_filter")
 */
class DecorativeImageFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Ensure the table for the "decorative" field is available.
    $this->ensureMyTable();

    // Check the filter value and add a WHERE clause.
    $decorative_value = '[decorative]';
    if ($this->value == 1) {
      // If 'Yes', filter where 'decorative' field is checked.
      $this->query->addWhereExpression(0, "{$this->tableAlias}.alt = :decorative", [':decorative' => $decorative_value]);
    }
    else {
      // If 'No', filter where 'decorative' field is unchecked.
      $this->query->addWhereExpression(0, "{$this->tableAlias}.alt != :decorative OR {$this->tableAlias}.alt IS NULL", [':decorative' => $decorative_value]);
    }
  }

}
