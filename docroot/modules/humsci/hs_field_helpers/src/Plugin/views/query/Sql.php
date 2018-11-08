<?php

namespace Drupal\hs_field_helpers\Plugin\views\query;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Plugin\views\query\Sql as OriginalSql;
use Drupal\views\ViewExecutable;

/**
 * Adds extra functionality to views Sql to give greatest and least aggregation.
 *
 * @package Drupal\hs_field_helpers\Plugin\views\query
 */
class Sql extends OriginalSql {

  /**
   * {@inheritdoc}
   */
  public function getAggregationInfo() {
    $info = parent::getAggregationInfo();
    $info['greatest'] = [
      'title' => $this->t('Greatest'),
      'method' => 'aggregationMethodSimple',
      'handler' => [
        'argument' => 'groupby_numeric',
        'field' => 'numeric',
        'filter' => 'groupby_numeric',
        'sort' => 'groupby_numeric',
      ],
    ];
    $info['least'] = [
      'title' => $this->t('Least'),
      'method' => 'aggregationMethodSimple',
      'handler' => [
        'argument' => 'groupby_numeric',
        'field' => 'numeric',
        'filter' => 'groupby_numeric',
        'sort' => 'groupby_numeric',
      ],
    ];
    return $info;
  }

  /**
   * Alter the view query in consolidate multiple date field sorting.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object for the query.
   */
  public function alterQuery(ViewExecutable $view) {
    $date_fields = $this->getDateFields();

    // Not enough date fields to care about altering.
    if (!(count($date_fields) > 1)) {
      return;
    }

    $expression = [];
    $orderby_delta = -1;

    // Build the expression.
    foreach ($date_fields as $field_alias => $direction) {
      $field = $this->fields[$field_alias];

      // Set null values to 0.
      $expression[] = 'COALESCE(' . $field['table'] . '.' . $field['field'] . ', 0)';

      // Unset the original sorting.
      foreach ($this->orderby as $delta => $order_item) {
        if ($order_item['field'] == $field_alias) {
          $orderby_delta = $delta;
          unset($this->orderby[$delta]);
        }
      }
    }

    // Add a new field to the select query and use an expression.
    $this->fields['max_date'] = [
      'function' => 'greatest',
      'field' => implode(', ', $expression),
      'alias' => 'max_date',
    ];

    // New sorting with our expression above.
    $this->orderby[$orderby_delta] = [
      'field' => 'max_date',
      'direction' => $direction,
    ];

    // Fix the order weights based on the deltas.
    ksort($this->orderby);
    $this->orderby = array_values($this->orderby);
  }

  /**
   * Get an array of date fields being used as sorting.
   *
   * @return array
   *   Keyed array of field alias and sort direction.
   */
  protected function getDateFields() {
    $field_types = ['datetime', 'daterange'];
    $date_fields = [];

    foreach ($this->orderby as $order_item) {
      $field_alias = $order_item['field'];
      if (!isset($this->fields[$field_alias])) {
        continue;
      }
      $field_data = $this->fields[$field_alias];
      $table = $field_data['table'];

      // Only operate on CCK Fields. We dont care about base fields like entity
      // title, published etc.
      if (strpos($table, '__') === FALSE) {
        continue;
      }

      list($entity_type, $field_name) = explode('__', $table);
      $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);

      if ($field_storage && in_array($field_storage->getType(), $field_types)) {
        $date_fields[$field_alias] = $order_item['direction'];
      }
    }
    return $date_fields;
  }

}
