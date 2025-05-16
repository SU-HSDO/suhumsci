<?php

namespace Drupal\hs_dashboard\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a custom dynamic field for the content source.
 *
 * @ViewsField("hs_dashboard_source_field")
 */
class SourceField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
    $migration = \Drupal::service('stanford_migrate')->getNodesMigration($node);
    return ($migration) ? $this->t('Imported') : $this->t('Local');
  }

}
