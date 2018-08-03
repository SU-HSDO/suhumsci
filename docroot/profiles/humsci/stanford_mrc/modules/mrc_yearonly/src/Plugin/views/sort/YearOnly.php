<?php

namespace Drupal\mrc_yearonly\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\Standard;

/**
 * Better sort implemention for yearonly field.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("yearonly")
 */
class YearOnly extends Standard {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field with a max function to aggregate them all.
    $this->query->addOrderBy($this->tableAlias, $this->realField, $this->options['order'], '', ['function' => 'max']);
  }

}
