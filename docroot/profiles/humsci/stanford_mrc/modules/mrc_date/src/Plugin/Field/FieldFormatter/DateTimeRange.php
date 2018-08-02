<?php

namespace Drupal\mrc_date\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'Custom' formatter for 'daterange' fields.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "datetimerange_custom",
 *   label = @Translation("Custom Single"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateTimeRange extends DateTimeCustomFormatter {

  use DateTimeRangeTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'display' => 'start_date',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    $display = $this->getSetting('display');
    foreach ($items as $delta => $item) {
      if (!empty($item->{$display})) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item->{$display};
        $elements[$delta] = $this->buildDate($date);
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    unset($form['separator']);
    $form['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display'),
      '#default_value' => $this->getSetting('display'),
      '#options' => [
        'start_date' => $this->t('Start date only'),
        'end_date' => $this->t('End date only'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($display = $this->getSetting('display')) {
      $summary[] = $this->t('Display: %display', ['%display' => $display]);
    }
    return $summary;
  }

}
