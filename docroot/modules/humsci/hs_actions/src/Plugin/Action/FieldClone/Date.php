<?php

namespace Drupal\hs_actions\Plugin\Action\FieldClone;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Date to increment date fields.
 *
 * @FieldClone(
 *   id = "date",
 *   label = @Translation("Date"),
 *   description = @Translation("Incrementally increase the date on the field for every cloned item."),
 *   fieldTypes = {
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
    $form = parent::buildConfigurationForm($form, $form_state);

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
        'day' => $this->t('Day'),
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
  public function alterFieldValue(FieldableEntityInterface $original_entity, FieldableEntityInterface $new_entity, $field_name, array $config = []) {
    if (!$new_entity->hasField($field_name) || empty($config['increment'])) {
      return;
    }

    // To allow us to increase the date value for each subsequent clone, keep
    // track of how many times we've seen this original entity.
    if (!isset($this->entityIds[$original_entity->id()])) {
      $this->entityIds[$original_entity->id()] = 0;
    }
    $this->entityIds[$original_entity->id()]++;

    // Use the multiple to multiply how much to increment from the original
    // entity.
    $config['multiple'] = $this->entityIds[$original_entity->id()];

    $values = $original_entity->get($field_name);
    $new_values = [];

    // Loop through all field values and increment them, then set the new values
    // back to the cloned entity.
    for ($delta = 0; $delta < $values->count(); $delta++) {
      $item_value = $values->get($delta)->getValue();

      foreach ($item_value as $column_name => $column_value) {
        if (!in_array($column_name, ['value', 'end_value'])) {
          $new_values[$delta][$column_name] = $column_value;
          continue;
        }
        $new_values[$delta][$column_name] = $this->incrementDateValue($column_value, $config);
      }
    }
    $new_entity->set($field_name, $new_values);
  }

  /**
   * Increase the given date value by the configured amount.
   *
   * @param string $value
   *   Original date value.
   * @param array $increment_config
   *   Keyed array of increment settings.
   *
   * @return string
   *   The new increased value.
   *
   * @throws \Exception
   */
  protected function incrementDateValue($value, array $increment_config = []) {
    $increment = $increment_config['multiple'] * $increment_config['increment'];

    $new_value = new \DateTime($value);
    $daylight_savings = date('I', $new_value->getTimestamp());

    // Add the interval that is in the form of "2 days" or "6 hours".
    $interval = \DateInterval::createFromDateString($increment . ' ' . $increment_config['unit']);
    $new_value->add($interval);

    // Date fields that don't collect the time use a different date format. We
    // check if the date length is the same length as an example format.
    if (strlen($value) == strlen('2019-02-21')) {
      return $new_value->format('Y-m-d');
    }

    // Adjust the time of the string if the new value skips over the daylight
    // savings time.
    if (date('I', $new_value->getTimestamp()) != $daylight_savings) {
      // Accommodate both going into and out of daylight savings time.
      $interval = $daylight_savings ? '1 hour' : '-1 hour';
      $interval = \DateInterval::createFromDateString($interval);
      $new_value->add($interval);
    }

    return $new_value->format('Y-m-d\TH:i:s');
  }

}
