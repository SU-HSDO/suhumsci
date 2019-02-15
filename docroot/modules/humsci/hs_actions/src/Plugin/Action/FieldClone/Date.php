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
  public function alterFieldValue(FieldableEntityInterface $entity, $field_name, $config = []) {
    if (!$entity->hasField($field_name)) {
      return;
    }

    $values = $entity->get($field_name);
    for ($delta = 0; $delta < $values->count(); $delta++) {
      $item_value = $values->get($delta)->getValue();
      foreach ($item_value as &$column) {
        $column = $this->incrementDateValue($column, $config);
      }
      $values->set($delta, $item_value);
    }
    $entity->set($field_name, $values);
  }

  protected function incrementDateValue($value, $increment_config = []) {
     $new_value = new \DateTime($value);
     $interval = \DateInterval::createFromDateString($increment_config['increment'] . ' ' . $increment_config['unit']);
     $new_value->add($interval);

     $new_value->format($new_value->form);
  }

}
