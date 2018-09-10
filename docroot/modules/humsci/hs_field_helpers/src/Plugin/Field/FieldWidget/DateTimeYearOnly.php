<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Plugin implementation of the 'datetime_datelist' widget.
 *
 * @FieldWidget(
 *   id = "datetime_year_only",
 *   label = @Translation("Year Only"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeYearOnly extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only work on "Date Only" fields.
    $type = $field_definition->getFieldStorageDefinition()
      ->getSetting('datetime_type');
    if ($type != 'date') {
      return FALSE;
    }
    return parent::isApplicable($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'min' => 'now - 10 years',
      'max' => 'now + 10 years',
    ];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'select',
      '#options' => $this->getOptions(),
      '#empty_option' => $this->t('- None -'),
    ];
    $element = ['value' => $element];

    /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
    if ($date = $items->get($delta)->date) {
      // The date was created and verified during field_load(), so it is safe to
      // use without further inspection.
      $date->setTimezone(new \DateTimeZone(drupal_get_user_timezone()));
      $element['value']['#default_value'] = $this->createDefaultValue($date, drupal_get_user_timezone())
        ->format('Y');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if ($value['value']) {
        $value['value'] = $value['value'] . '-06-01';
      }
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * Get widget options for select list.
   *
   * @return array
   *   Range of years.
   */
  protected function getOptions() {
    try {
      $min = $this->getYear($this->getSetting('min'));
    }
    catch (\Exception $e) {
      $min = 2000;
    }

    try {
      $max = $this->getYear($this->getSetting('max'));
    }
    catch (\Exception $e) {
      $max = 2050;
    }
    $range = range($min, $max);
    return array_combine($range, $range);
  }

  /**
   * Get the year from a relative or absolute year.
   *
   * @param string|int $dateTime
   *   Relative or absolute year.
   *
   * @return false|string
   *   The year of the datetime.
   */
  protected function getYear($dateTime) {
    $dateTime = new \DateTime($dateTime);
    $timestamp = $dateTime->getTimestamp();
    return date('Y', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Year'),
      '#description' => $this->t('Enter an exact year or relative year.'),
      '#default_value' => $this->getSetting('min'),
    ];
    $element['max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Year'),
      '#description' => $this->t('Enter an exact year or relative year.'),
      '#default_value' => $this->getSetting('max'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('%min to %max', [
      '%min' => $this->getSetting('min'),
      '%max' => $this->getSetting('max'),
    ]);
    return $summary;
  }

  /**
   * Creates a date object for use as a default value.
   *
   * This will take a default value, apply the proper timezone for display in
   * a widget, and set the default time for date-only fields.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The UTC default date.
   * @param string $timezone
   *   The timezone to apply.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A date object for use as a default value in a field widget.
   */
  protected function createDefaultValue(DrupalDateTime $date, $timezone) {
    // The date was created and verified during field_load(), so it is safe to
    // use without further inspection.
    if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $date->setDefaultDateTime();
    }
    $date->setTimezone(new \DateTimeZone($timezone));
    return $date;
  }

}
