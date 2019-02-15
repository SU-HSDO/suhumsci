<?php

namespace Drupal\hs_actions\Plugin\Action\FieldClone;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Date.
 *
 * @FieldClone(
 *   id = "date",
 *   label = @Translation("Webforms"),
 *   description = @Translation("Incrementally increase the date on the field
 *   for every cloned item."), fieldTypes = {
 *     "datetime",
 *     "datetime_range",
 *     "daterange"
 *   }
 * )
 */
class Date extends FieldCloneBase {

  protected $entityIds = [];

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $increment = range(0, 12);
    unset($increment[0]);

    $form['increment'] = [
      '#type' => 'select',
      '#title' => $this->t('Increment Amount'),
      '#options' => $increment,
      '#empty_option' => $this->t('- Do Not Change -'),
    ];

    $form['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => [
        'year' => $this->t('Year'),
        'month' => $this->t('Month'),
        'week' => $this->t('Week'),
        'hour' => $this->t('Hour'),
        'minute' => $this->t('Minute'),
        'second' => $this->t('Second'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldValue(FieldableEntityInterface $original_entity, FieldableEntityInterface $entity, $field_name, $config = []) {
    if (!$entity->hasField($field_name)) {
      return;
    }
    if (!isset($this->entityIds[$original_entity->id()])) {
      $this->entityIds[$original_entity->id()] = 0;
    }
    $this->entityIds[$original_entity->id()]++;
    $config['multiple'] = $this->entityIds[$original_entity->id()];

    $values = $entity->get($field_name);
    $new_values = [];
    for ($delta = 0; $delta < $values->count(); $delta++) {
      $item_value = $values->get($delta)->getValue();

      foreach ($item_value as $column_name => $column_value) {
        $new_values[$delta][$column_name] = $this->incrementDateValue($column_value, $config);
      }
    }
    $entity->set($field_name, $new_values);
  }

  protected function incrementDateValue($value, $increment_config = []) {
    $increment = $increment_config['multiple'] * $increment_config['increment'];

    $new_value = new \DateTime($value);
    $interval = \DateInterval::createFromDateString($increment . ' ' . $increment_config['unit']);
    $new_value->add($interval);
    return $new_value->format('Y-m-d\TH:i:s');
  }

}
